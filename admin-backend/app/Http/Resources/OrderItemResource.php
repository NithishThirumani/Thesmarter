<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        $productImages[0] = (object) array(
            'id' => $this->product->product_id,
            'url' => $this->product->product_logo,
            'thumbnail' => $this->product->product_logo,
            'alt_text' => $this->product->product_name
        );
        return [
            'id' => $this->product_id,
            'name' => $this->product->product_name ?? 'Unnamed Product',
            'catalogue_id' => $this->product->catalogue_id,
            'details' => [
                'product_type' => $this->product->product_type,
                'description' => "", //$additionalDetails->description ?? '',
                'sku' => "", //$additionalDetails->sku ?? '',
                'brand' => $this->product->product_brand ?? '',
                'product_code' => $this->product->product_code ?? '',
                'manufacturer' => "", //$additionalDetails->manufacturer ?? '',
                'price' => [
                    'mpp_id' => '', //$product->price->mpp_id,
                    'unit_price' => $this->unit_price ?? 0.00,
                    'currency' => 'INR', //$additionalDetails->currency ?? 'USD',
                    'base_amount' => round($this->base_price / $this->product_quantity, 2) ?? 0.00
                ],
                'images' => array_map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->url,
                        'thumbnail' => $image->thumbnail,
                        'alt_text' => $image->alt_text
                    ];
                }, $productImages),
            ],
            // 'is_dynamically_priced' => $this->is_dynamically_priced,
            'quantity' => $this->product_quantity,
            'discount' => $this->discount,
            'taxes' => $this->tax,
            'charges' => $this->charge,
            "net_amount" => $this->net_amount,
            "tax_amount" => $this->tax_amount,
            "charge_amount" => $this->charge_amount,
            "discount_amount" => $this->discount_amount,
            "total_amount" => $this->total_amount,

        ];
    }
}
