<?php 

namespace App\Discounts;

class PercentageProductDiscount implements ProductDiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        $baseAmount = $item['base_amount'] ?? 0;
        $value = $item['product_discount']['value'] ?? 0;

        return round(($baseAmount * $value) / 100, 2);
    }
}
