<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        $discounts = collect($this->discount)->map(function ($discount) {
            return [
                'dd_id'  => $discount['dd_id'],
                'name'   => $discount['name'],
                'type'   => $discount['type'] == 'F' ? 'Flat' : 'Percentage',
                'level' => $discount['level'],
                'value'  => $discount['value'],
                'amount' => $discount['amount'],
            ];
        });
        $charges = collect($this->charges)->map(function ($charge) {
            return [
                'charge_id'  => $charge['charge_id'],
                'name'   => $charge['name'],
                'type'   => $charge['type'] == 'F' ? 'Flat' : 'Percentage',
                'value'  => $charge['value'],
                'amount' => $charge['amount'],
            ];
        });

        $payments = collect($this->payment)->map(function ($payments) {

            return [
                'payment_mode_id'  => $payments['payment_mode_id'],
                'type' => ucfirst(strtolower($payments['paymentMode']['payment_name'])),
                'status' => $payments['payment_mode_id'] == 3 ? 'Due' : 'Paid',
                'reference'   => $payments['payment_reference'],
                'paid_amount'   => $payments['amount_paid'],
                'balance_amount'  => $payments['amount_balance'],
                'returned_amount' => $payments['amount_returned'],
            ];
        });

        $executive_name = $this->executive ? $this->executive->first_name . ' ' . $this->executive->last_name : "";
        $order = [
            'order_id' => $this->order_id,
            'company_id' => $this->company_id,
            'items' => $this->items_count,
            'net_amount' => $this->net_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'charge_amount' => $this->charge_amount,
            'total_amount' => $this->total_amount,
            'order_date_time' => $this->order_date . '' . $this->order_time,
            'order_status' => $this->order_status,
            'discount' => $discounts,
            'charges' =>    $charges,
            'payment' => $payments,
            'customer' => array(
                'id' => $this->customer?->user_id ?? "",
                'name' => $this->customer?->first_name ?? "" . ' ' . $this->customer?->last_name ?? "",
                'mobile' => $this->customer?->login?->user_mobile ?? null,
            ),
            'executive' => array(
                'id' => $this->executive->user_id ?? null,
                'name' => $executive_name,
                'mobile' => $this->executive?->login?->user_mobile ?? null,
            ),
            'branch' => array(
                'id' => $this->branch->branch_id,
                'branch_type' => $this->branch->branch_type,
                'area' => $this->branch?->contact?->area ?? null
            )
        ];

        if ($request->routeIs('order.create') || $request->routeIs('order.show')) {
            $order['items'] = OrderItemResource::collection($this->items);
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
            $order['taxes'] = $taxes;
        }

        return $order;
    }
}
