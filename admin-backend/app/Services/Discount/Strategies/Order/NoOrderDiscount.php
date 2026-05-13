<?php 
namespace App\Services\Discount\Strategies\Order;

class NoOrderDiscount implements OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float
    {
        return 0.00;
    }
}
