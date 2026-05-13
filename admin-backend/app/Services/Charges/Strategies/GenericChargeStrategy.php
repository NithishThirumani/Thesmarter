<?php 
namespace App\Services\Charges\Strategies;

use App\Charges;
use App\Services\Charges\Strategies\ChargeStrategyInterface;


class GenericChargeStrategy implements ChargeStrategyInterface
{
    protected int $id;
    protected string $code;

    public function __construct(int $id, string $code)
    {
        $this->id = $id;
        $this->code = $code;
    }

    public function isApplicable(array $cart, array $item): bool
    {
        return in_array($this->id, $cart['charges'] ?? []);
    }

    public function apply(array $item, Charges $charge): float
    {
        $base = $item['net_amount'] ?? 0;
        return match ($charge->charge_type) {
            'P' => round($base * ($charge->value / 100), 2),
            'F' => $charge->value,
            default => 0,
        };
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
