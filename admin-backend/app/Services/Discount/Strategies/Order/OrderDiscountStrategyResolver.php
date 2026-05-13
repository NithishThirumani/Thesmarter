<?php 
namespace App\Services\Discount\Strategies\Order;

class OrderDiscountStrategyResolver
{
    public function resolve(array $discount): OrderDiscountStrategyInterface
    {
        $type = $discount['type'] ?? null;
        return match ($type) {
            'F' => new FlatOrderDiscount(),
            'P' => new PercentageOrderDiscount(),
            default => new NoOrderDiscount(),
        };
    }
}
