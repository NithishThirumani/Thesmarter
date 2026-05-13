<?php 
namespace App\Services\Discount\Strategies\Product;

class NoProductDiscount implements ProductDiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        return 0.00;
    }
}
