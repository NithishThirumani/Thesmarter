<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Company–payment method mapping. Used by admin company module.
 */
class CompanyPayment extends Model
{
    protected $table = 'company_payments';

    protected $fillable = [
        'company_id',
        'payment_id',
        'payment_method_id',
        'merchant_id',
        'secret_key_encrypted',
    ];

    protected $hidden = [
        'secret_key_encrypted',
    ];

    protected $appends = [
        'has_secret_key',
    ];

    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethods::class, 'payment_method_id', 'payment_id');
    }

    public function getPaymentMethodIdAttribute()
    {
        return $this->attributes['payment_method_id'] ?? $this->attributes['payment_id'] ?? null;
    }

    public function getHasSecretKeyAttribute(): bool
    {
        return !empty($this->attributes['secret_key_encrypted']);
    }
}
