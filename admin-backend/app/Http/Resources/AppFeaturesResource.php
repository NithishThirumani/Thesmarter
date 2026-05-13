<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppFeaturesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->feature_id,
            'name' => $this->resource->feature_name,
            'description' => $this->resource->feature_description,
            'isAcive' => ($this->resource->feature_status == 'A') ? true : false
        ];
    }
}
