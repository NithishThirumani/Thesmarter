<?php

namespace App\Repositories;

use App\CompanyAppointments;
use App\OrderDetail;
use App\OrderItemDetail;
use App\OrderEntity;
use App\OrderProductDiscounts;
use App\OrderProductCharges;
// use App\ProformaProductDiscounts;
use App\OrderProductTaxes;
// use App\OrderSequence;
use App\OrderShipping;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;


class OrderRepository implements OrderRepositoryInterface
{


    public function confirmOrder(array $orderData): array
    {
        $order = OrderDetail::updateOrCreate(
            ['order_id' => $orderData['order_id']],
            $this->mapOrderData($orderData)
        );
        return $order->toArray();
    }
    public function mapOrderData(array $orderData): array
    {
        $timezone = 'Asia/Kolkata'; // Covers Hyderabad and all of India
        $currentDate = Carbon::now($timezone)->toDateString();   // e.g., "2025-06-10"
        $currentTime = Carbon::now($timezone)->toTimeString();   // e.g., "17:45:30"
        return [
            'executive_id' => $orderData['executive_id'],
            'branch_id' => $orderData['branch_id'],
            'net_amount' => $orderData['net_amount'],
            'discount_amount' => $orderData['discount_amount'],
            'charge_amount' => $orderData['charge_amount'],
            'tax_amount' => $orderData['tax_amount'],
            'total_amount' => $orderData['total_amount'],
            'order_date' => $currentDate,
            'order_time' => $currentTime,
            'order_status' => 'CP',
        ];
    }
    public function updateOrderItems(string $orderId, array $product): void
    {
        // Log::info(json_encode( $this->mapItemData($product)));
        // \DB::enableQueryLog();
        OrderItemDetail::updateOrCreate(
            ['order_id' => $orderId, 'product_id' => $product['product_id']],
            $this->mapItemData($product)
        );
        // dd(\DB::getQueryLog());
    }
    public function mapItemData(array $product): array
    {
        return [
            'mpp_id' => $product['detail']['price']['mpp_id'],
            'product_quantity' => $product['quantity'],
            'discount_id' => $product['discount']['discount_id'] ?? null,
            'unit_price' => $product['detail']['price']['unit_price'] ?? 0.00,
            'base_price' => $product['base_amount'] ?? 0.00,
            'net_amount' => $product['net_amount'],
            'discount_amount' => $product['discount_amount'] ?? 0,
            'tax_amount' => $product['tax_amount'],
            'charge_amount' => $product['charge_amount'],
            'total_amount' => $product['total_amount'],
            'custom_price_flag' => $product['flag']['is_dynmically_priced'] ?? 'N'
        ];
    }
    public function addProductTaxes(string $orderId, int $productId, int $taxDetailId, array $tax): void
    {
        OrderProductTaxes::updateOrCreate(
            [
                'order_id' => $orderId,
                'product_id' => $productId,
                'td_id' => $taxDetailId,
                'value' => $tax['value'],
                'amount' => $tax['amount'],
                'name' => $tax['name']
            ],
            [
                'order_id' => $orderId,
                'product_id' => $productId,
                'td_id' => $taxDetailId
            ]
        );
    }
    public function addProductDiscount(string $orderId, int $productId, array $discount): void
    {

        OrderProductDiscounts::updateOrCreate(
            [
                'order_id' => $orderId,
                'product_id' => $productId,
                'discount_detail_id' => $discount['dd_id'],
                'name' => $discount['name'],
                'type' => $discount['type'],
                'value' => $discount['value'],
                'amount' => $discount['amount'],
                'level' => $discount['level'] == "order" ? "O" : "P"
            ],
            [
                'order_id' => $orderId,
                'product_id' => $productId,
                'discount_detail_id' => $discount['dd_id'],
            ]
        );
    }
    public function addProductCharge(string $orderId, int $productId, array $charges): void
    {
        foreach ($charges as $chargeId => $charge) {
            OrderProductCharges::updateOrCreate(
                [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'charge_id' => $chargeId,
                    'name' => $charge['name'],
                    'type' => $charge['type'],
                    'value' => $charge['value'],
                    'amount' => $charge['amount']
                ],
                [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'charge_id' => $chargeId,
                ]
            );
        }
    }
    public function changeOrderEntityStatus(string $orderId, int $productId, string $status): void
    {
        // Assuming you have OrderEntity and OrderItemDetail models
        // Or for upsert:
        $orderItem = OrderItemDetail::where('product_id', $productId)
            ->where('order_id', $orderId)
            ->first();

        if ($orderItem) {
            OrderEntity::updateOrCreate(
                ['order_item_id' => $orderItem->order_item_id],
                ['order_item_status' => $status]
            );
        }
    }
    public function addOrderShipping(string $orderId, array $shipping): void
    {
        OrderShipping::updateOrCreate(
            [
                'order_id' => $orderId,
                'address' => $shipping['address1'],
                'area' => $shipping['area'],
                'city' => $shipping['city'],
                'state' => $shipping['state'],
                'country' => $shipping['country'],
                'coordinates' => null,
                'email' => $shipping['email']
            ],
            [
                'order_id' => $orderId
            ]
        );
    }
    public function releaseEntity(int $appointmentId, $status, $dateTime): void
    {
        CompanyAppointments::update(
            [
                'status' => $status,
                'end' => $dateTime
            ]
        )->where('appointment_id', $appointmentId);
    }

    public function getOrderDetails(string $orderId): OrderDetail
    {
        $query = OrderDetail::with([
            'company',
            'items.product',
            'customer.login',
            'executive.login',
            'branch.contact',
            'payment.paymentMode',
            'charges',
            'discount'
        ]); 
        // Apply the appropriate condition based on the available parameter
        if (!is_null($orderId)) {
            $query->where('order_id', $orderId);
        } else {
            //  return null; // Return null or handle the case where neither parameter is provided
        }
        $order = $query->first();

        if (!$order) {
            // return null; // Handle case where no proforma is found
        }
        $order->items->each(function ($item) {
            $item->load(['tax' => function ($query) use ($item) {
                $query->where('product_id', $item->product_id);
            }]);
            // Load discounts for the item
            $item->load(['discount' => function ($query) use ($item) {
                $query->where('product_id', $item->product_id); // Adjust this condition if needed
            }]);
            // Load discounts for the item
            $item->load(['charge' => function ($query) use ($item) {
                $query->where('product_id', $item->product_id); // Adjust this condition if needed
            }]);
        });
        // Load additional relationships with filtering
        $order->load([
            'customer.defaultContactUser.contactDetails'
        ]);
        $order->load('customerAdditionalDetail'); 
         
        return $order;
    } 
}
