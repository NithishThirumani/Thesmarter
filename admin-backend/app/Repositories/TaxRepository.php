<?php

namespace App\Repositories;

use App\TaxMaster;
use App\TaxComponents;
use App\TaxDetail;
use Carbon\Carbon;

class TaxRepository
{

    public function findTaxDetails(int $taxId)
    {
        $currentDate = Carbon::today()->toDateString();
        return TaxMaster::with(['components.details' => function ($query) use ($currentDate) {
            $query->whereDate('tax_start_date', '<=', $currentDate)
                ->where(function ($q) use ($currentDate) {
                    $q->whereDate('tax_end_date', '>=', $currentDate)
                        ->orWhereNull('tax_end_date');
                })
                ->orderBy('tax_start_date', 'desc');
        }])->where('tax_id', $taxId)->get()->pluck('components')->flatten();
    }
    public function sumTaxValue($taxComponentsWithValue)
    {
        return $taxComponentsWithValue->flatMap(function ($component) {
            return $component->details->pluck('tax_value');
        })->sum();
    }
}