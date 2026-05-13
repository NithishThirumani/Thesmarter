<?php

namespace App\Repositories;

use App\OrderDetail;
use App\OrderItemDetail;

class CartRepository
{

    public function getUserCart(int $userId,int $companyId,int $branchId)
    {
        return OrderDetail::with(['items:order_id,product_id,product_quantity as quantity'])
            ->where('customer_id', $userId)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('order_status', 'PG')
            ->first();
    }
}
