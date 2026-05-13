<?php

namespace App\Services\Order;

use App\Repositories\OrderRepository;

class OrderDetailService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(int $orderNo)
    {
        return $this->orderRepository->getOrderDetails($orderNo);
    }
}