<?php
namespace App\Discounts\Product;

use App\Discounts\FlatProductDiscount;

class ProductDiscountStrategyResolver
{
    public function resolve(array $item): ProductDiscountStrategyInterface
    {
        $type = $item['prodyct_discount']['type'] ?? null;

        return match ($type) {
            'flat' => new FlatProductDiscount(),
            'percentage' => new PercentageProductDiscount(),
            'override_price' => new OverrideProductPriceDiscount(),
            default => new NoProductDiscount(),
        };
    }
}
