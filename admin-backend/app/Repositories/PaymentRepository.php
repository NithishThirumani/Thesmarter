<?php

namespace App\Repositories;

use App\OrderPayments;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\TransactionDetail;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct() {}
    public function addOrderPayment(string $orderId, float $amount,array $payment): OrderPayments
    {
        return OrderPayments::updateOrCreate(
            [
                'order_id' => $orderId,
                'payment_mode_id' => $payment['payment_mode_id'],
                'amount_paid' => $amount,
                'details' => json_encode($payment)
            ],
            [
                'order_id' => $orderId
            ]
        );
    }
    public function addOrUpdateCashRegister(int $paymentId,float $amount ,string $type,?int $executiveId=null,?int $branchId=null): void 
    {
        TransactionDetail::updateOrCreate(
            [
                'executive_id'=>$executiveId,
                'branch_id'=>$branchId,
                'trans_type'=>$type,
                'trans_amount'=>$amount,
                'trans_op_id'=>$paymentId
            ],
            [
                'executive_id'=>$executiveId,
                'branch_id'=>$branchId,
                'trans_op_id'=>$paymentId
            ]
        );
    }
}
