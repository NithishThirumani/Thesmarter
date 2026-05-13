<?php 
namespace App\Discounts;

class NoProductDiscount implements ProductDiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        return 0.00;
    }
}
