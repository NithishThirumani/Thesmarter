<?php
namespace App\Services\Charges\Strategies;

use App\Services\Charges\Strategies\FlatChargeStrategy;
use App\Services\Charges\Strategies\PercentageChargeStrategy;
use App\Services\Charges\Strategies\GenericChargeStrategy;
 
class ChargeStrategyResolver 
{

    public function resolve($charge, float $totalBaseAmount)
    {
        switch ($charge->type) {
            case 'flat':
                return new FlatChargeStrategy($charge, $totalBaseAmount);

            case 'percentage':
                return new PercentageChargeStrategy($charge);

            default:
                return new GenericChargeStrategy($charge->misc_id, $charge->charge_name);
        }
    }
}
