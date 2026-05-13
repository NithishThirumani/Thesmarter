<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaResource extends JsonResource
{
    public function toArray($request)
    {

        $proforma = [
            'proforma_no' => $this->proforma_no,
            'company_id' => $this->company_id,
            'items' => $this->items_count,
            'net_amount' => $this->net_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'charge_amount' => $this->charge_amount,
            'total_amount' => $this->total_amount,
            'proforma_date_time' => $this->proforma_date_time,
            'proforma_status' => $this->proforma_status,
            'customer' => array(
                'id' => $this->customer?->user_id ?? null,
                'name' => $this->customer?->first_name ?? null . ' ' . $this->customer?->last_name ?? null,
                'mobile' => $this->customer?->login?->user_mobile ?? null,
            ),
            'executive' => array(
                'id' => $this->executive->user_id,
                'name' => $this->executive->first_name . ' ' . $this->executive->last_name,
                'mobile' => $this->executive?->login?->user_mobile ?? null,
            ),
            'branch' => array(
                'id' => $this->branch->branch_id,
                'branch_type' => $this->branch->branch_type,
                'area' => $this->branch?->contact?->area ?? null
            )
        ];

        if ($request->routeIs('proforma.create') || $request->routeIs('proforma.show')) {
            $proforma['items'] = ProformaItemResource::collection($this->items);
            // Assuming $order is the decoded JSON
            $taxes = collect($this->items)
                ->pluck('tax')
                ->flatten(1)
                ->groupBy(function ($tax) {
                    return "{$tax['name']} @ {$tax['value']}%";
                })
                ->map(function ($taxGroup) {
                    return round($taxGroup->sum('amount'), 2);
                });
          
            $proforma['taxes'] = $taxes;
        }

        return $proforma;
    }
}
