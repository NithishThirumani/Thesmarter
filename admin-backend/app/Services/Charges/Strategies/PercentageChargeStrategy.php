<?php 
namespace App\Services\Charges\Strategies;

class PercentageChargeStrategy implements ChargeStrategyInterface
{
    protected int $id;
    protected string $name;
    protected float $value;

    public function __construct($chargeModel)
    {
        $this->id = $chargeModel->id;
        $this->name = $chargeModel->charge_name;
        $this->value = $chargeModel->value;
    }

    public function isApplicable(array $cart, array $item): bool
    {
        return true;
    }

    public function apply(array $item, $chargeModel): float
    {
        $baseAmount = $item['net_amount'] ?? 0;
        return round($baseAmount * ($this->value / 100), 2);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
