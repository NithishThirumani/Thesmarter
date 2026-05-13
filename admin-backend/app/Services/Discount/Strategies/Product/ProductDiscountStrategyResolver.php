<?php
namespace App\Services\Discount\Strategies\Product;

class ProductDiscountStrategyResolver
{
    public function resolve(array $item): ProductDiscountStrategyInterface
    {
        $type = $item['discount']['type'] ?? null;

        return match ($type) {
            'F' => new FlatDiscount(),
            'P' => new PercentageProductDiscount(),
            'D' => new OverrideProductPriceDiscount(),
            default => new NoProductDiscount(),
        };
    }
}
