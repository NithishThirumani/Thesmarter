<?php
namespace App\Services\Discount\Strategies\Order;

interface OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float;
}
