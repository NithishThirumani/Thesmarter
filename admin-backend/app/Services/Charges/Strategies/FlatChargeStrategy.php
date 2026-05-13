<?php 
namespace App\Services\Charges\Strategies;

class FlatChargeStrategy implements ChargeStrategyInterface
{
    protected int $id;
    protected string $name;
    protected float $value;
    protected float $totalBaseAmount;

    public function __construct($chargeModel, float $totalBaseAmount)
    {
        $this->id = $chargeModel->id;
        $this->name = $chargeModel->charge_name;
        $this->value = $chargeModel->value;
        $this->totalBaseAmount = $totalBaseAmount;
    }

    public function isApplicable(array $cart, array $item): bool
    {
        return true; // Always applicable, we control proportion logic
    }

    public function apply(array $item, $chargeModel): float
    {
        $itemBase = $item['net_amount'] ?? 0;
        if ($this->totalBaseAmount == 0) return 0;
        $share = $itemBase / $this->totalBaseAmount;
        return round($this->value * $share, 2);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
