<?php
namespace App\Repositories\Contracts;

use App\OrderPayments;

interface PaymentRepositoryInterface
{
    public function addOrderPayment(string $orderId,float $amount,array $payment):OrderPayments;
    public function addOrUpdateCashRegister(int $paymentId,float $amount ,string $type,?int $executiveId=null,?int $branchId=null): void ;
}