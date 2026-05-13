<?php 
namespace App\Discounts\Product;

interface ProductDiscountStrategyInterface
{
    public function calculate(array $item): float;
}
