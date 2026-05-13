<?php 
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProformaDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // $customerDetails = json_decode($this->customer->login->user_mobile);
        // $executiveDetails = json_decode($this->executive->login);
        // print_r($customerDetails);exit;
        return [
            'proforma_no' => $this->proforma_no,
            'company_id' => $this->company_id,
            'net_amount' => $this->net_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'charge_amount' => $this->charge_amount,
            'total_amount' => $this->total_amount,
            'proforma_date_time' => $this->proforma_date_time,
            'proforma_status' => $this->proforma_status,
            'customer'=>array(
                'id'=>$this->customer->user_id,
                'name'=>$this->customer->first_name.' '.$this->customer->last_name,
                'mobile' => $this->customer?->login?->user_mobile ?? null, 
            ),
            'executive'=>array(
                'id'=>$this->executive->user_id,
                'name'=>$this->executive->first_name.' '.$this->executive->last_name,
                'mobile' => $this->executive?->login?->user_mobile ?? null, 
            ),
            'branch'=>array(
                'id'=>$this->branch->branch_id,
                'branch_type'=>$this->branch->branch_type,
                'area'=>$this->branch?->contact?->area ?? null
            )
        ];
    }
}
