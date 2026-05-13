<?php 
namespace App\Charges;

use App\Models\Charge;

class GenericChargeStrategy implements ChargeStrategyInterface
{
    protected string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function isApplicable(array $cart, array $item): bool
    {
        return in_array($this->code, $cart['selected_charges'] ?? []);
    }

    public function apply(array $item, Charge $charge): float
    {
        $base = $item['net_price'] ?? 0;

        return match ($charge->type) {
            'percentage' => round($base * ($charge->value / 100), 2),
            'fixed' => $charge->value,
            default => 0
        };
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
