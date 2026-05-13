<?php

namespace App\Repositories;

use App\DiscountMaster;
use App\DiscountDetail;
use App\OrderDiscounts;
use Carbon\Carbon;

class DiscountRepository
{
    public function findDiscountDetails(int $discountId)
    {
        $currentDate = Carbon::today()->toDateString();
        return DiscountMaster::with(['details' => function ($query) use ($currentDate) {
            $query->where('discount_start_date', '<=', $currentDate)
                ->where(function ($q) use ($currentDate) {
                    $q->where('discount_end_date', '>=', $currentDate)
                        ->orWhereNull('discount_end_date');
                });
        }])
            ->where('discount_id', $discountId)
            ->where('discount_status', 'A')
            ->limit(1)
            ->first();
    }
    public function addOrderDiscount(string $orderId,array $discount):void
    {
        OrderDiscounts::updateOrCreate(
            [
                'order_id'=>$orderId,
                'dd_id'=>$discount['dd_id'],
                'name'=>$discount['name'],
                'value'=>$discount['value'],
                'type'=>$discount['type'],
                'amount'=>$discount['amount'],
            ],
            [
                'order_id'=>$orderId
            ]
        );
    }
    public function getActiveProductDiscountByProductId(int $productId):array
    {
        return [];
    }
}
