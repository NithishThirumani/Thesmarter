<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Welcome email sent to a tenant company’s email when the company is first created (admin wizard). */
class CompanyTenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $companyName;

    /** @var string|null */
    public $legalName;

    public function __construct(string $companyName, ?string $legalName)
    {
        $this->companyName = $companyName;
        $this->legalName = $legalName !== null && trim($legalName) !== '' ? trim($legalName) : null;
    }

    public function build()
    {
        return $this
            ->subject('Welcome to '.$this->companyName.' on '.config('app.name'))
            ->view('mails.company_tenant_welcome');
    }
}
