<?php

namespace App\Services\Payment;

use App\OrderPayments;
use App\Services\Due\DueService;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentService
{
    protected $paymentRepository;
    protected $dueService;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        DueService $dueService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->dueService = $dueService;
    }
    public function processPayment(string $orderId, float $amount, array $data): void
    {
        $payment = $this->paymentRepository->addOrderPayment($orderId, $amount, $data['payment']);

        $type = 'OSALE';
        if ($payment->payment_mode_id == 1) {
            $type = 'CSALE';
        } else if ($payment->payment_mode_id == 3) {
            $type = 'DSALE';
            $this->dueService->processOrderDue(
                $orderId,
                $data['customer_id'],
                $data['company_id'],
                $amount,
                $data['executive_id']
            );
        }
        $this->paymentRepository->addOrUpdateCashRegister($payment->op_id, $payment->amount_paid, $type, $data['executive_id'], $data['branch_id']);
    }
}
