<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    //
    protected $table = 'company_detail';
    protected $primaryKey = 'company_id';
    protected $fillable = [
        'company_name',
        'legal_name',
        'tag_line',
        'description',
        'phone_number',
        'email',
        'company_logo',
        'company_business_id',
        'company_revenue_id',
        'company_website',
        'company_gstin',
        'company_pan',
        'company_status',
        'feedback_flag',
        'company_dawn',
        'company_dusk',
        'company_timeslice',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'discount_tax_inclusive',
        'company_marketing_message',
        'country_id',
        'latitude',
        'longitude',
        'radius',
        'customer_app',
        'appointment_auto_confirm',
    ];

    protected $casts = [
        'customer_app' => 'boolean',
        'appointment_auto_confirm' => 'boolean',
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function country()
    {
        return $this->belongsTo(Country::class,'country_id','country_id');
    }

    public function lineOfBusiness()
    {
        return $this->belongsTo(LineOfBusiness::class, 'company_business_id', 'lob_id');
    }
    public function branches()
    {
        return $this->hasMany(BranchDetail::class,'company_id','company_id');
    }

    public function companyPayments()
    {
        return $this->hasMany(CompanyPayment::class, 'company_id', 'company_id');
    }

    public function companyFeatures()
    {
        return $this->hasMany(CompanyFeatures::class, 'company_id', 'company_id');
    }

    public function taxMasters()
    {
        return $this->hasMany(TaxMaster::class, 'company_id', 'company_id');
    }

    public function businessHours()
    {
        return $this->hasMany(CompanyBusinessHour::class, 'company_id', 'company_id')->orderBy('day_of_week')->orderBy('slot_index');
    }

    public function settings()
    {
        return $this->hasOne(CompanySetting::class, 'company_id', 'company_id');
    }
}
