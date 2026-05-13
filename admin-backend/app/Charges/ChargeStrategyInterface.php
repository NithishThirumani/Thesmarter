<?php 
namespace App\Charges;

interface ChargeStrategyInterface
{
    public function isApplicable(array $cart,array $item):bool;
    public function apply(array $item, \App\Models\Charge $charge): float;
    public function getCode(): string;
}