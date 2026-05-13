<?php 
namespace App\OrderDiscounts;

class NoOrderDiscount implements OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float
    {
        return 0.00;
    }
}
