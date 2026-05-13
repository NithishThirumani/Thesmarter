<?php 
namespace App\Services\Charges\Strategies;

use App\Charges;

interface ChargeStrategyInterface
{
    public function isApplicable(array $cart,array $item):bool;
    public function apply(array $item, Charges $charge): float;
    public function getId(): int;
} 