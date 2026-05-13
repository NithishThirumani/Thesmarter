<?php

namespace App\Services\Proforma;


use App\Repositories\ProformaRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TaxRepository;
use App\Services\Charges\ChargeService;
use App\Services\Discount\DiscountService;

use stdClass;

/**
 * Service responsible for calculating proforma invoice summaries
 * Optimized for memory efficiency and performance
 */
class CalculateSummaryService
{
    /** @var ProformaRepository */
    private $proformaRepository;

    /** @var ProductRepository */
    private $productRepository;

    /** @var Discount Service  */
    protected $discountService;

    /** @var Charge Service  */
    private $chargeService;

    /** @var TaxRepository */
    private $taxRepository;

    /** @var array Stores accumulated tax information for the entire order */
    private $taxes = [];

    /**
     * Constructor to inject required repositories
     *
     * @param ProformaRepository $proformaRepository
     * @param ProductRepository $productRepository
     * @param DiscountRepository $discountRepository
     * @param TaxRepository $taxRepository
     */
    public function __construct(
        ProformaRepository $proformaRepository,
        ProductRepository $productRepository,
        DiscountService $discountService,

        TaxRepository $taxRepository,
        ChargeService $chargeService
    ) {
        $this->proformaRepository = $proformaRepository;
        $this->productRepository = $productRepository;
        $this->taxRepository = $taxRepository;
        $this->discountService = $discountService;
        $this->chargeService = $chargeService;
    }

    /**
     * Calculate the complete summary for a proforma invoice
     * Optimized for memory efficiency
     *
     * @param array $data Cart data containing items and details
     * @return array Calculated cart summary with totals and breakdown
     */
    // public function calculateProformaSummaryOld(array $data): array
    // {
    //     // Reset taxes to prevent memory leaks from previous calculations
    //     $this->taxes = [];

    //     // Use array instead of object for better memory management
    //     $cart = [
    //         'order_id' => $data['order_id'] ?? null,
    //         'executive_id' => $data['executive_id'] ?? null,
    //         'branch_id' => $data['branch_id'] ?? null,
    //         'customer_id' => $data['customer_id'] ?? null,
    //         'total_amount' => 0.00,
    //         'net_amount' => 0.00,
    //         'tax_amount' => 0.00,
    //         'discount_amount' => 0.00,
    //         'charge_amount' => 0.00,

    //         // 'company_id'=>$data['company_id'],
    //         // 'branch_id'=>$data['branch_id'],
    //         // 'customer_id'=>$data['customer_id'],
    //         // 'executive_id'=>$data['executive_id'],

    //         'items' => $data['items'] ?? [],
    //         'discount' => $data['discount'] ?? new stdClass(),
    //         'charge' =>  new stdClass(),
    //         'taxes' => [],
    //         'meta' => [
    //             'total_unique_products' => 0,
    //             'total_quantity' => 0
    //         ]
    //     ];


    //     // Process each item in the cart
    //     foreach ($cart['items'] as &$item) {
    //         // Retrieve detailed product information
    //         $product = $this->productRepository->findProductDetails($item['product_id']);

    //         // Fetch comprehensive product details
    //         $productDetails = $this->getComprehensiveProductDetails($product, $item['product_id']);

    //         // Calculate base price, discounts, and taxes for the product
    //         $baseAmount = $this->calculateProductBaseAmount($product);

    //         $this->applyProductLevelAdjustments($cart, $product, $productDetails, $item, $baseAmount);
    //     }
    //     unset($item); // Break reference to avoid potential memory issues

    //     // Calculate order-level discount
    //     $orderDiscount = $this->getDiscount($cart['discount']);
    //     if (!empty($orderDiscount)) {
    //         $this->processOrderLevelDiscount($cart, $orderDiscount);

    //         $cart['discount']['name'] =  $orderDiscount['name'];
    //         $cart['discount']['level'] =  $orderDiscount['level'];
    //         $cart['discount']['amount'] =  $cart['discount_amount'];
    //     } else {
    //         $cart['discount'] =  new stdClass();
    //     }


    //     // Add taxes to cart and free up memory
    //     $cart['taxes'] = $this->taxes;
    //     $this->taxes = []; // Clear for next calculation

    //     return $cart;
    // }


    public function calculateSummary(array $data): array
    {
        $this->resetState();
        $cart = $this->initializeCart($data);
        $cart = $this->processCartItems($cart);
        $cart = $this->discountService->handleOrderLevel($cart);
        $cart = $this->chargeService->handle($cart); // One-liner to apply selected charges
        $this->recalculateProductInfoAfterOrderDiscount($cart);
        // $cart['order_discount'] = $this->discountService->applyOrderLevelDiscount($cart,$discount=[]);      
        $cart['taxes'] = $this->finalizeTaxes();
        return $cart;
    }

    protected function resetState(): void
    {
        $this->taxes = [];
    }
    protected function initializeCart(array $data): array
    {
        // Use array instead of object for better memory management
        return [
            'order_id' => $data['order_id'] ?? null,
            'executive_id' => $data['executive_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'total_amount' => 0.00,
            'net_amount' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'charge_amount' => 0.00,

            // 'company_id'=>$data['company_id'],
            // 'branch_id'=>$data['branch_id'],
            // 'customer_id'=>$data['customer_id'],
            // 'executive_id'=>$data['executive_id'],

            'items' => $data['items'] ?? [],
            'discount' => $data['discount'] ?? new \stdClass(),
            'charges' =>  $data['charges'] ?? new \stdClass(),
            'taxes' => [],
            'meta' => [
                'total_unique_products' => 0,
                'total_quantity' => 0
            ]
        ];
    }
    protected function processCartItems(array $cart): array
    {

        foreach ($cart['items'] as &$item) {
            $product = $this->productRepository->findProductDetails($item['product_id']);
            $item['detail'] = $this->getComprehensiveProductDetails($product, $item['product_id']);
            $item['base_amount'] = $this->calculateProductBaseAmount($product);
            $item['detail']['price']['base_amount'] = round($item['base_amount'], 2);
            $item['base_amount'] = round($item['base_amount'] * $item['quantity'], 2);
            $item['net_amount'] = $item['base_amount'];
            $this->discountService->applyProductLevelDisocunt($product, $item); 
            // Calculate taxes if applicable
            $this->calculateTaxes($item);
            // Update item details
            $this->updateItemDetails($item);
            // Accumulate cart-level totals
            $this->accumulateTotals($cart, $item);
        }
        return $cart;
    }


    protected function getEligibleOrderDiscountBase(array $items): float
    {
        $base = 0.00;
        foreach ($items as $item) {
            if (!$item['exclude_order_discount']) {
                $base += $item['base_amount'];
            }
        }
        return $base;
    }
    protected function calculateOrderDiscountAmount(array $discount, float $eligibleBase): float
    {
        if ($discount['type'] === 'percentage') {
            return ($eligibleBase * $discount['value']) / 100;
        }
        if ($discount['type'] === 'flat') {
            return min($discount['value'], $eligibleBase);
        }
        return 0.00;
    }
    protected function recalculateProductInfoAfterOrderDiscount(array &$cart): void
    {

        $discountAmount = $cart['discount']['amount'] ?? 0;
        $chargeAmount = $cart['total_charge'] ?? 0;
        if ($discountAmount == 0 && $chargeAmount == 0) {
            return;
        }
        $this->resetState();
        $this->resetTotals($cart);
        foreach ($cart['items'] as &$item) {
            $this->calculateTaxes($item);
            $this->updateItemDetails($item);
            // Accumulate cart-level totals
            $this->accumulateTotals($cart, $item);
        }
    }

    protected function finalizeTaxes(): array
    {
        $taxes = $this->taxes;
        $this->taxes = [];
        return $taxes;
    }


    // private function applyProductLevelAdjustments(array &$cart, object $product, array $details, array &$item, float $baseAmount)
    // {
    //     $discount = $this->calculateProductDiscount($product, $baseAmount, $cart);

    //     // Calculate taxes if applicable
    //     $taxDetails = !empty($product->tax)
    //         ? $this->calculateTaxes($product, $baseAmount, $discount, $item['quantity'])
    //         : ['taxes' => [], 'totalTaxComponentWise' => [], 'total_tax_amount' => 0.00];

    //     // Update item details
    //     // Update item details
    //     $this->updateItemDetails(
    //         $item,
    //         $product,
    //         $details,
    //         $baseAmount,
    //         $discount,
    //         $taxDetails
    //     );

    //     // Accumulate cart-level totals
    //     $this->accumulateTotals($cart, $item);
    // }
    /**
     * Retrieve comprehensive product details
     * 
     * @param object $product Base product object
     * @param int $productId Product identifier
     * @return array Comprehensive product details
     */

    private function getComprehensiveProductDetails(object $product): array
    {
        // Fetch additional product details
        // $additionalDetails = $this->productRepository->getAdditionalProductDetails($productId);

        // Fetch product images
        // $productImages = $this->productImageRepository->getProductImages($productId);
        $productImages[0] = (object) array(
            'id' => $product->product_id,
            'url' => $product->product_logo,
            'thumbnail' => $product->product_logo,
            'alt_text' => $product->product_name
        );

        // Fetch product flags and attributes
        // $productFlags = $this->productRepository->getProductFlags($productId);
        $productFlags = array(
            'is_bulk_quantity_enabled' => ($product->quantity_based_price_flag == 'Y' ? true : false),
            'is_service_charge_applicable' => ($product->product_service_charge_flag == 'Y' ? true : false),
            'is_discount_applicable' => ($product->product_discount_flag == 'Y' ? true : false),
            'is_inventoriable' => ($product->product_count_stock == 'Y' ? true : false),
            'is_tax_inclusive' => ($product->taxInclusive != NULL && $product->taxInclusive->inclusive_flag == "Y" && $product->taxInclusive->current_status == "A") ? true : false,
        );

        // Fetch product categories
        // $productCategories = $this->productRepository->getProductCategories($productId);

        return [
            'id' => $product->product_id,
            'catalogue_id' => $product->catalogue_id,
            'tax_id' => $product->tax?->tax_id,
            'product_type' => $product->product_type,
            'name' => $product->product_name ?? 'Unnamed Product',
            'description' => $additionalDetails->description ?? '',
            'sku' => $additionalDetails->sku ?? '',
            'brand' => $product->product_brand ?? '',
            'product_code' => $product->product_code ?? '',
            'manufacturer' => $additionalDetails->manufacturer ?? '',
            'price' => [
                'mpp_id' => $product->price->mpp_id,
                'unit_price' => $product->price->unit_price ?? 0.00,
                'currency' => 'INR', //$additionalDetails->currency ?? 'USD'
            ],
            'images' => array_map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                    'thumbnail' => $image->thumbnail,
                    'alt_text' => $image->alt_text
                ];
            }, $productImages),
            'flags' => $productFlags
        ];
    }
    /**
     * Calculate base amount for a product, considering tax inclusivity
     * Minimizes object creation and memory usage
     *
     * @param object $product Product details
     * @return float Calculated base amount
     */
    private function calculateProductBaseAmount(object $product): float
    {
        $unitPrice = (float) ($product->price->unit_price ?? 0.00);

        // Handle tax-inclusive pricing
        if (
            !empty($product->taxInclusive) &&
            $product->taxInclusive->inclusive_flag === 'Y' &&
            $unitPrice !== 0.00
        ) {
            $taxComponentsWithValue = $this->taxRepository->findTaxDetails($product->tax->tax_id);
            $taxValueSum = $this->taxRepository->sumTaxValue($taxComponentsWithValue);

            // Calculate base price by removing tax component
            return round($unitPrice / (1 + ($taxValueSum / 100)), 2);
        }

        return $unitPrice;
    }
    /**
     * Calculate taxes for a product
     * Optimized for memory efficiency
     *
     * @param object $product Product details
     * @param float $baseAmount Base amount
     * @param array $discount Discount details
     * @param int $quantity Quantity of product
     * @return array Tax components and total tax amount
     */
    private function calculateTaxes(array &$item): void
    {

        if (empty($item['detail']['tax_id'])) {
            $item['detail']['tax'] = [];
            $item['taxes'] = [];
            $item['tax_amount'] = 0.00;
            return;
        }

        $taxId = $item['detail']['tax_id'];
        $quantity = $item['quantity'];
        $baseAmount = $item['base_amount'];

        // Get tax details and sum of tax components
        $taxComponentsWithValue = $this->taxRepository->findTaxDetails($taxId);
        $taxValueSum = $this->taxRepository->sumTaxValue($taxComponentsWithValue);
        // Calculate discount amount
        $discountAmount = 0.00;
        $discountAmount += $item['product_level_discount'] ?? 0.00;
        $discountAmount += $item['order_level_discount'] ?? 0.00;
        // Calculate charge amount
        $chargeAmount = $item['charge_amount'] ?? 0;

        // Calculate discounted price
        $productPrice = round(($baseAmount + $chargeAmount - $discountAmount), 2);

        // Calculate total product tax amount
        $totalProductTaxAmount = round((($productPrice * $taxValueSum) / 100), 2);

        // Calculate tax for each tax component
        $taxes = [];
        $totalTaxComponentWise = [];
        foreach ($taxComponentsWithValue as $taxComponent) {
            $taxValue = $taxComponent->details[0]->tax_value;
            $taxComponentName = $taxComponent->component_name;
            $taxComponentId = $taxComponent->tc_id;

            // Calculate tax amount for this component
            $taxAmount = round(($productPrice * $taxValue / 100), 2);

            // Accumulate tax amounts and details
            $taxes[$taxComponentId]['amount'] =
                isset($taxes[$taxComponentId]['amount'])
                ? $taxes[$taxComponentId]['amount'] + round($taxAmount, 2)
                : round($taxAmount, 2);

            $taxes[$taxComponentId]['value'] = $taxValue;
            $taxes[$taxComponentId]['name'] = $taxComponentName;

            // Need to re-evaluate this 
            $taxes[$taxComponentId]['value'] = $taxValue;
            $taxes[$taxComponentId]['name'] = $taxComponentName;

            // Update order-level taxes
            $orderTaxAmount = round((($productPrice) * $taxValue / 100), 2);
            $this->updateOrderTaxes($taxComponentId, $taxComponentName, $taxValue, $orderTaxAmount);
        }
        foreach ($taxes as $component => $tax) {
            $totalTaxComponentWise[$component]['value'] = $tax['value'];
            $totalTaxComponentWise[$component]['name'] = $tax['name'];
            $totalTaxComponentWise[$component]['amount'] = $tax['amount'];
        }
        $item['detail']['tax'] = $taxes;
        $item['taxes'] = $totalTaxComponentWise;
        $item['tax_amount'] = round(($totalProductTaxAmount), 2);


        // return [
        //     'taxes' => $taxes,
        //     'totalTaxComponentWise' => $totalTaxComponentWise,
        //     'total_tax_amount' =>  $totalProductTaxAmount
        // ];
    }

    /**
     * Update accumulated order-level taxes
     *
     * @param int $componentId Tax component identifier
     * @param string $name Tax component name
     * @param float $value Tax percentage
     * @param float $amount Tax amount
     */
    private function updateOrderTaxes(int $componentId, string $name, float $value, float $amount): void
    {
        // Accumulate tax amounts by component
        $this->taxes[$componentId]['amount'] =
            isset($this->taxes[$componentId]['amount'])
            ? round($this->taxes[$componentId]['amount'] + $amount, 2)
            : round($amount, 2);

        $this->taxes[$componentId]['value'] = $value;
        $this->taxes[$componentId]['name'] = $name;
    }

    /**
     * Update individual item details with calculated values
     *
     * @param array &$item Cart item (passed by reference)
     * @param object $product Product details
     * @param float $baseAmount Base product amount
     * @param array $discount Discount details
     * @param array $taxDetails Tax calculation results
     */
    private function  updateItemDetails(
        array &$item
    ): void {
        $quantity = $item['quantity'] ?? 1;
        $baseAmount = $item['base_amount'] ?? 0;

        $chargeAmount = $item['charge_amount'] ?? 0;

        // Calculate base


        // --- Product Level Discount
        $productDiscount = $item['product_discount'] ?? [];
        $productDiscountAmount = round(($productDiscount['amount'] ?? 0), 2);

        $item['discounts']['product_level'] = [
            'type' => $productDiscount['type'] ?? null,
            'value' => $productDiscount['value'] ?? 0,
            'level' => 'product',
            'amount' => $productDiscountAmount
        ];

        // --- Order Level Discount
        $orderDiscountAmount = round(($item['order_level_discount'] ?? 0), 2);

        // Sum of all discounts
        $totalDiscountAmount = $productDiscountAmount + $orderDiscountAmount;

        // Calculate amounts
        $taxAmount = $item['tax_amount'] ?? 0;
        $item['discount_amount'] = round($totalDiscountAmount, 2);
        $item['charge_amount'] = round($chargeAmount, 2);
        $item['total_amount'] = round(($item['net_amount'] + $taxAmount + $chargeAmount), 2);

        // $quantity = $item['quantity'];
        // $item['detail']['price']['base_amount'] = round($item['base_amount'], 2);
        // $item['detail']['product_leve_discount'] = $item['product_discount'];
        // $item['discount'] = $item['product_discount'];
        // $item['discount']['amount'] = round(($item['product_discount']['amount']) * $quantity, 2);
        // $item['net_amount'] = round($item['base_amount'] * $quantity, 2);
        // $item['charge_amount'] = 0.00;
        // $item['discount_amount'] = round($item['product_discount']['amount'] * $quantity, 2);
        // $item['total_amount'] = $item['net_amount'] + $item['tax_amount'] + $item['charge_amount'] - $item['discount_amount'];
    }

    /**
     * Accumulate cart-level totals
     *
     * @param array &$cart Cart array (passed by reference)
     * @param array $item Processed cart item
     */
    private function accumulateTotals(array &$cart, array $item): void
    {
        $cart['total_amount'] = round($cart['total_amount'] + $item['total_amount'], 2);
        $cart['net_amount'] = round($cart['net_amount'] + $item['net_amount'], 2);
        $cart['discount_amount'] = round($cart['discount_amount'] + $item['discount_amount'], 2);
        $cart['tax_amount'] = round($cart['tax_amount'] + $item['tax_amount'], 2);
    }
    private function resetTotals(array &$cart): void
    {
        $cart['total_amount'] = 0;
        $cart['net_amount'] = 0;
        $cart['discount_amount'] = 0;
        $cart['tax_amount'] = 0;
    }
}
