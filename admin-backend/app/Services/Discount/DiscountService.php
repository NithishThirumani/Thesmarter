<?php

namespace App\Services\Discount;


use App\Repositories\DiscountRepository;
use App\Services\Discount\Strategies\Order\OrderDiscountStrategyResolver;
use App\Services\Discount\Strategies\Product\ProductDiscountStrategyResolver;

class DiscountService
{
    protected $discountRespository;
    protected $productDiscountResolver;
    protected $orderDiscountResolver;

    public function __construct(
        ProductDiscountStrategyResolver $productDiscountResolver,
        OrderDiscountStrategyResolver $orderDiscountResolver,
        DiscountRepository $discountRepository
    ) {
        $this->discountRespository = $discountRepository;
        $this->productDiscountResolver = $productDiscountResolver;
        $this->orderDiscountResolver = $orderDiscountResolver;
    }
    public function processDiscount(string $orderId, array $discount): void
    {
        if ((!is_null($discount)) && (!empty($discount) && ($discount['amount'] > 0))) {
            $this->discountRespository->addOrderDiscount($orderId, $discount);
        }
    }
    public function applyProductLevelDisocunt(object $product, array &$item): void
    {

        if (is_null($product->discount)) {
            $item['discount'] =  [];
            return;
        }
        // echo json_encode($product->discount->discount_id);exit;
        $discount = $this->discountRespository->findDiscountDetails($product->discount->discount_id);
        if (!$discount) {
            $item['discount'] = new \stdClass(); // Return empty if no discount found
        }
        $item['discount']['type'] = $discount['discount_type'];
        $item['discount']['level'] = $discount['discount_level'];
        $item['discount']['type'] = $discount['discount_type'];
        $item['discount']['dd_id'] = $discount['details'][0]['discount_detail_id'];
        $item['discount']['name'] = $discount['details'][0]['discount_name'];
        $item['discount']['value'] = $discount['details'][0]['discount_value'];
        $item['discount']['qualifying_value'] = $discount['details'][0]['qualifying_value'];

        $strategy = $this->productDiscountResolver->resolve($item);
        $discountAmount  = $strategy->calculate($item);

        $item['product_level_discount'] = $item['discount']['amount'] = $discountAmount;
        $item['net_amount'] = $item['net_amount'] - $discountAmount;
        $item['detail']['flags']['is_discount_applicable'] = false;
    }
    public function handleOrderLevel(array $cart): array
    {
        $discount = null;
        $discountId = $cart['discount']['discount_id'] ?? null;
        $discountValue = $cart['discount']['value'] ?? 0;
        $discountType = $cart['discount']['type'] ?? 'P';
        $discountName = $cart['discount']['name'] ??  'Custom Discount';
        // 1. Handle DB-based discount
        if (!empty($discountId)) {
            $discount = $this->discountRespository->findDiscountDetails($discountId);
            $discount = [
                'type' => $discount->discount_type == 'F' ? 'F' : 'P',  // 'percentage' or 'fixed'
                'level' => 'order',
                'value' => $discount->details[0]['discount_value'] ?? 0,
                'name' => $discount->details[0]['discount_name'] ?? null,
                'dd_id' => $discount->details[0]['discount_detail_id'],
                'discount_id' => $discount->discount_id,
            ];
        }

        // 2. Handle On-the-fly Discount (custom_discount passed from frontend)
        if (empty($discountId) && ($discountValue != 0)) {
            $discount = [
                'type' => $discountType ?? 'P',  // 'percentage' or 'fixed'
                'level' => 'order',
                'value' => $discountValue ?? 0,
                'name' => 'Custom Discount',
                'dd_id' => null,
                'discount_id' => null,
            ];
        }

        // If no discount at all, return unchanged cart
        if (empty($discount)) {
            $cart['discount']['amount'] = 0;
            return $cart;
        }
        $cart['discount'] = $discount;
        // 3. Let the resolver pick the strategy and calculate
        $strategy = $this->orderDiscountResolver->resolve($discount);
        $discountAmount = $strategy->calculate($cart, $discount);

        // 4. Set discount detail in cart
        $discountDetail = [
            'type' => $discount['type'],
            'level' => $discount['level'],
            'discount_id' => $discount['discount_id'],
            'name' => $discount['name'],
            'value' => $discount['value'],
            'amount' => $discountAmount,
        ];
        $cart['discount']['amount'] = $discountAmount;

        $eligibleBase = array_reduce($cart['items'], function ($carry, $item) {
            $isDiscountApplicable = $item['detail']['flags']['is_discount_applicable'] ?? false;
            $orderItemBaseAmount = ($item['base_amount']) ?? 0;
            return $carry + (
                $isDiscountApplicable  === true ? ($orderItemBaseAmount ?? 0) : 0
            );
        }, 0.00);
        $totalDiscount = 0;
        // Item Wise discount
        foreach ($cart['items'] as &$item) {
            $isDiscountApplicable = $item['detail']['flags']['is_discount_applicable'] ?? false;
            $orderItemBaseAmount = ($item['base_amount']) ?? 0;

            if ($isDiscountApplicable === true) {
                $item['discount_amount'] = $item['order_level_discount'] = $this->calculateItemLevelDiscountShare($orderItemBaseAmount, $eligibleBase, $discountAmount);
                $item['net_amount'] = $item['net_amount'] - $item['order_level_discount'];
                $item['discount'] = array(
                    'type' => $discount['type'],
                    'level' => $discount['level'],
                    'discount_id' => $discount['discount_id'],
                    'name' => $discount['name'],
                    'dd_id' => $discount['dd_id'],
                    'value' => $discount['value'],
                    'amount' =>  $item['discount_amount'],
                );
                $totalDiscount += $item['discount_amount'];
            }
        }
        $cart['discount_amount'] = $totalDiscount;
        return $cart;
    }
    public function calculateItemLevelDiscountShare(float $itemBase, float $totalEligibleBase, float $totalDiscount): float
    {
        if ($totalEligibleBase == 0) return 0.00;
        $share = $itemBase / $totalEligibleBase;
        return round($share * $totalDiscount, 2);
    }
    // public function handleOrderLevel(array $cart): array
    // {
    //     // if(!is_null($discount['discount_id'])){
    //     //     $discount = $this->discountRespository->findDiscountDetails($discount['discount_id']);
    //     // }

    //     $strategy = $this->orderDiscountResolver->resolve($cart);
    //     $discountAmount = $strategy->calculate($cart);

    //     $discountDetail['type'] = $discount['discount_type'];
    //     $discountDetail['level'] = $discount['discount_level'];
    //     $discountDetail['type'] = $discount['discount_type'];
    //     $discountDetail['discount_id'] = $discount['discount_id'] ?? null;
    //     $discountDetail['name'] = $discount['name'];
    //     $discountDetail['value'] = $discount['value'];
    //     $discountDetail['amount'] = $discountAmount;
    //     $cart['order_discount'] = $discountDetail;
    //     return $cart;
    // }
}
