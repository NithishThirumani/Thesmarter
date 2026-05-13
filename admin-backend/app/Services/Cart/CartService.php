<?php

namespace App\Services\Cart;

use App\Repositories\CartRepository;

class CartService
{
    protected $cartReposiotry;
    public function __construct(CartRepository $cartReposiotry)
    {
        $this->cartReposiotry = $cartReposiotry;
    }

    public function getUserCart(array $data): array
    {
        $customerId = ($data['customer_id'] == 0) ? 2 : $data['customer_id'];
        $companyId = $data['company_id']; 
        $branchId = $data['branch_id'];
        $cart = $this->cartReposiotry->getUserCart($customerId, $companyId, $branchId);
        return $cart ? $cart->toArray() : [];
    }
}
