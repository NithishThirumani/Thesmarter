<?php

namespace App\Services;

use App\Repositories\ChargeRepository;

class ChargeService
{
    protected $chargeRepository;
    public function __construct(ChargeRepository $chargeRepository)
    {
        $this->chargeRepository = $chargeRepository;
    }
    public function processOrderCharge(string $orderId, array $charge): void
    {
        if ((!is_null($charge)) && (!empty($charge))) {
            $this->chargeRepository->addOrderCharge($orderId, $charge);
        }
    }
}
