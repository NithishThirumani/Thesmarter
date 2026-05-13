<?php
namespace App\OrderDiscounts;

interface OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float;
}
