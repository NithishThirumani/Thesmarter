<?php
namespace App\OrderDiscounts;

class FlatOrderDiscount implements OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float
    {
        $value = $cart['discount']['value'] ?? 0;
        $eligibleBase = $this->getEligibleOrderDiscountBase($cart['items']);
        return min($value, $eligibleBase);
    }

    protected function getEligibleOrderDiscountBase(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            return $carry + (
                empty($item['exclude_order_discount']) ? ($item['base_amount'] ?? 0) : 0
            );
        }, 0.00);
    }
}
