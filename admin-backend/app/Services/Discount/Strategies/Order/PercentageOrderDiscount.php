<?php

namespace App\Services\Discount\Strategies\Order;

class PercentageOrderDiscount implements OrderDiscountStrategyInterface
{
    public function calculate(array &$cart): float
    {
        $percent = $cart['discount']['value'] ?? 0;
        $eligibleBase = $this->getEligibleOrderDiscountBase($cart['items']);
        $discountAmount = round(($eligibleBase * $percent) / 100, 2);
        return $discountAmount;
    }

    protected function getEligibleOrderDiscountBase(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            $isDiscountApplicable = $item['detail']['flags']['is_discount_applicable'] ?? false;
            $orderItemBaseAmount = ($item['base_amount']) ?? 0;
            return $carry + (
                ($isDiscountApplicable === true) ? ($orderItemBaseAmount ?? 0) : 0
            );
        }, 0.00);
    }
}
