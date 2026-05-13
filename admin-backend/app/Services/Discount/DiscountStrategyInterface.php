<?php 
namespace App\Discounts;

interface DiscountStrategyInterface
{
    public function calculate(array $item): float;
}
