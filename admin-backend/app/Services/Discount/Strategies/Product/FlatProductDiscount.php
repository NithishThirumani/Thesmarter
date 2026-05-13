<?php 

namespace App\Services\Discount\Strategies\Product;

class FlatDiscount implements ProductDiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        $baseAmount = $item['base_amount'] ?? 0;
        $value = $item['discount']['value'] ?? 0;

        return min($value, $baseAmount);
    }
}
