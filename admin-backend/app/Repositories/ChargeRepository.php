<?php

namespace App\Repositories;

use App\Charges;
use App\OrderCharges;

class ChargeRepository
{
    public function addOrderCharge(string $orderId, array $charges): void
    {
        foreach ($charges as $chargeId => $charge) {
            OrderCharges::updateOrCreate(
                [
                    'order_id' => $orderId,
                    'charge_id' => $chargeId,
                    'name' => $charge['name'],
                    // 'value' => $charge['value'],
                    // 'type' => $charge['type'],
                    'amount' => $charge['amount']
                ],
                [
                    'order_id' => $orderId
                ]
            );
        }
    }
    public function getChargesByIds(array $ids)
    {
        return Charges::whereIn('misc_id', $ids)
            ->where('charge_status', 'A')
            ->get()->keyBy('misc_id');
    }
}
