<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'user_dob' => $this->user_dob,
            'gender' => $this->user_gender,
            'marital_status' => $this->marital_status,
            'user_status' => $this->user_status,
            'created_at' => $this->created_dtm,
            'updated_at' => $this->updated_dtm,

            'login' => $this->whenLoaded('login', function () {
                return [
                    'user_mobile' => $this->login->user_mobile,
                    'email' => $this->login->email,
                    'is_temp_mpin' => (bool) $this->login->is_temp_mpin,
                    'last_active_company' => array_key_exists('last_active_company', $this->login->getAttributes())
                        ? (int) $this->login->last_active_company
                        : null,
                ];
            }),

            'additional_details' => $this->whenLoaded('additionalDetails', function () {
                return $this->additionalDetails->details;
            }),

            'contacts' => $this->whenLoaded('contacts', function () {
                return $this->contacts->map(function ($contact) {
                    return [
                        'type' => $contact->contact_type,
                        'is_primary' => (bool) $contact->is_primary,
                        'details' => [
                            'address' => $contact->contactDetails->address ?? null,
                            'area' => $contact->contactDetails->area ?? null,
                            'city' => $contact->contactDetails->city ?? null,
                            'state' => $contact->contactDetails->state ?? null,
                            'country' => $contact->contactDetails->country ?? null,
                            'pin_code' => $contact->contactDetails->pincode ?? null,
                            'lat' => $contact->contactDetails->lat ?? null,
                            'lng' => $contact->contactDetails->lng ?? null,
                        ],
                    ];
                });
            }),

            'companies' => $this->whenLoaded('userCompanies', function () {
                return $this->userCompanies->map(function ($company) {
                    if ($company->user_type === 4) {
                        $company->user_type = 'executive';
                    } elseif ($company->user_type === 5) {
                        $company->user_type = 'customer';
                    } elseif ($company->user_type === 3) {
                        $company->user_type = 'owner';
                    } elseif ($company->user_type === 1) {
                        $company->user_type = 'administrator';
                    }
                    return [
                        'company_id' => $company->company_id,
                        'user_type' => $company->user_type,
                        'status' => $company->status,
                    ];
                });
            }),
        ];
    }
}
