<?php

namespace App\Repositories;

use App\MerchantCatalogueProducts;

class ProductRepository
{

    public function findProductDetails(int $productId): MerchantCatalogueProducts
    {
        return MerchantCatalogueProducts::with([
            'price:product_id,mpp_id,product_cost_price as cost_price,product_sell_price as unit_price',
            'tax',
            'discount',
            'taxInclusive:product_id,inclusive_flag,current_status'
        ])
            ->where('product_id', $productId)
            ->first();
    }
}
