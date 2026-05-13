<?php

namespace App\Services\Charges;

use App\Repositories\ChargeRepository;
// use App\Services\Charges\Strategies\ChargeStrategyInterface;
// use App\Services\Charges\Strategies\DeliveryChargeStrategy;
// use App\Services\Charges\Strategies\PackagingChargeStrategy;
use App\Services\Charges\Strategies\ChargeStrategyResolver;
use App\Services\Charges\Strategies\GenericChargeStrategy;

class ChargeService
{
    protected ChargeRepository $chargeRepository;
    protected ChargeStrategyResolver $strategyResolver;

    public function __construct(ChargeRepository $chargeRepository, ChargeStrategyResolver $strategyResolver)
    {
        $this->chargeRepository = $chargeRepository;
        $this->strategyResolver = $strategyResolver;
    }
    public function handle(array $cart): array
    {
        $selectedIds = $cart['charges'] ?? [];

       
        if (empty($selectedIds)) {
            $cart['charge_amount'] = 0;
            return $cart;
        }

        $charges = $this->chargeRepository->getChargesByIds($selectedIds);

        if ($charges->isEmpty()) {
            $cart['charge_amount'] = 0;
            return $cart;
        }

        $totalNet = array_sum(array_column($cart['items'], 'net_amount'));
        $strategies = [];
        $cartChargeBreakdown = [];
        foreach ($charges as $charge) {
            $strategy = $this->strategyResolver->resolve($charge, $totalNet);
            $strategies[] = $strategy;
        }

        $totalCharge = 0;
        
        foreach ($cart['items'] as $index => &$item) {
            $itemCharge = 0;
            $breakdown = [];
            

            foreach ($strategies as $strategy) {
                if (!$strategy->isApplicable($cart, $item)) continue;

                $chargeModel = $charges[$strategy->getId()] ?? null;
                if (!$chargeModel) continue;

                $amount = $strategy->apply($item, $chargeModel);
                $breakdown[$chargeModel->misc_id]['amount'] = $amount;
                $breakdown[$chargeModel->misc_id]['name'] = $chargeModel->charge_name;
                $breakdown[$chargeModel->misc_id]['value'] = $chargeModel->value;
                $breakdown[$chargeModel->misc_id]['type'] = $chargeModel->charge_type;
                $itemCharge += $amount;

                // Cart-level accumulation
                if (!isset($cartChargeBreakdown[$chargeModel->misc_id])) {
                    $cartChargeBreakdown[$chargeModel->misc_id]['amount'] = 0;
                    $cartChargeBreakdown[$chargeModel->misc_id]['name'] = $chargeModel->charge_name;
                }
                $cartChargeBreakdown[$chargeModel->misc_id]['amount'] += $amount;
            }

            $item['charge_amount'] = $itemCharge;
            $item['charge'] = $breakdown;
            $totalCharge += $itemCharge;
        }
        $cart['charge_amount'] = round($totalCharge, 2);
        $cart['charges']=$cartChargeBreakdown;
        return $cart;
    }

    public function handleOld(array $cart): array
    {
        $selectedIds = $cart['charges'] ?? [];

        if (empty($selectedIds)) {
            $cart['total_charge'] = 0;
            return $cart;
        }
        // Step 1: Fetch Charges from DB
        $charges = $this->chargeRepository->getChargesByIds($selectedIds);

        if ($charges->isEmpty()) {
            $cart['total_charge'] = 0;
            return $cart;
        }
        // Step 2: Build Strategy List (hardcoded + dynamic)
        // $strategies = [
        //     new DeliveryChargeStrategy(),
        //     new PackagingChargeStrategy(),
        // ];

        // Add GenericChargeStrategy for any not already handled
        foreach ($charges as $charge) {
            $alreadyHandled = false;/*collect($strategies)->contains(
                fn($strategy) => method_exists($strategy, 'getId') && $strategy->getId() === $charge->id
            );*/

            if (!$alreadyHandled) {
                $strategies[] = new GenericChargeStrategy($charge->misc_id, $charge->charge_name);
            }
        }

        // Step 3: Apply Charges to Items
        $totalCharge = 0;
        $totalNetAmount = array_sum(array_column($cart['items'], 'net_amount'));

        foreach ($cart['items'] as $index => $item) {
            $itemCharge = 0;
            $breakdown = [];

            foreach ($strategies as $strategy) {
                if (!method_exists($strategy, 'getId')) {
                    continue;
                }

                $chargeId = $strategy->getId();

                if (!isset($charges[$chargeId])) {
                    continue;
                }

                $chargeModel = $charges[$chargeId];

                if ($strategy->isApplicable($cart, $item)) {
                    $amount = $strategy->apply($item, $chargeModel);
                    $breakdown[$chargeModel->charge_name] = $amount;
                    $itemCharge += $amount;
                }
            }

            $cart['items'][$index]['charge_amount'] = $itemCharge;
            $cart['items'][$index]['charge']['charge_breakdown'] = $breakdown;
            $totalCharge += $itemCharge;
        }

        $cart['charge_amount'] = $totalCharge;

        return $cart;
    }
    public function processOrderCharge(string $orderId, array $charge): void
    {
        if ((!is_null($charge)) && (!empty($charge))) {
            $this->chargeRepository->addOrderCharge($orderId, $charge);
        }
    }
}
