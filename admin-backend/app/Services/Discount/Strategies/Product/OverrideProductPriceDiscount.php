<?php 

namespace App\Services\Discount\Strategies\Product;

class OverrideProductPriceDiscount implements ProductDiscountStrategyInterface
{
    public function calculate(array $item): float
    {
        $baseAmount = $item['base_amount'] ?? 0;
        $quantity = $item['quantity'] ?? 1;
        $value = $item['discount']['value'] ?? 0;

        $overrideTotal = $value * $quantity;
        return max(0, $baseAmount - $overrideTotal);
    }
}
