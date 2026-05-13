<?php 

namespace App\Discounts;

class FlatDiscount implements DiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        $baseAmount = $item['base_amount'] ?? 0;
        $value = $item['product_discount']['value'] ?? 0;

        return min($value, $baseAmount);
    }
}
