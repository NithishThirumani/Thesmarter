<?php 
namespace App\OrderDiscounts;

class OrderDiscountStrategyResolver
{
    public function resolve(array $cart): OrderDiscountStrategyInterface
    {
        $type = $cart['discount']['type'] ?? null;

        return match ($type) {
            'F' => new FlatOrderDiscount(),
            'P' => new PercentageOrderDiscount(),
            default => new NoOrderDiscount(),
        };
    }
}
