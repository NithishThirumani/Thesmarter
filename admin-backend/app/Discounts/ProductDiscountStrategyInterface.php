<?php 
namespace App\Discounts;

interface ProductDiscountStrategyInterface
{
    public function calculate(array $item): float;
}
