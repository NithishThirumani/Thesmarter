<?php 
namespace App\OrderDiscounts;

class PercentageOrderDiscount implements OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float
    {
        $percent = $cart['discount']['value'] ?? 0;
        $eligibleBase = $this->getEligibleOrderDiscountBase($cart['items']);
        return round(($eligibleBase * $percent) / 100, 2);
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
