<?php

namespace App\Repositories\Contracts;

use App\OrderDetail;

interface OrderRepositoryInterface
{
    public function confirmOrder(array $orderData): array;
    public function updateOrderItems(string $orderId, array $orderData): void;
    public function getOrderDetails(string $orderId): OrderDetail;
    public function addProductTaxes(string $orderId,int $productId,int $taxDetailId,array $tax):void;
    public function addProductDiscount(string $orderId,int $productId,array $discount):void;
    public function changeOrderEntityStatus(string $orderId,int $productId,string $status):void;
} 
