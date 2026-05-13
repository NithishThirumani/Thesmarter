<?php 
namespace App\Services\Discount\Strategies\Product;

interface ProductDiscountStrategyInterface
{
    public function calculate(array $item): float;
}
