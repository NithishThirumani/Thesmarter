<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformAdminPinResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $displayName;

    public string $loginEmail;

    public string $plainPin;

    public string $loginUrl;

    public function __construct(string $displayName, string $loginEmail, string $plainPin, string $loginUrl)
    {
        $this->displayName = $displayName;
        $this->loginEmail = $loginEmail;
        $this->plainPin = $plainPin;
        $this->loginUrl = $loginUrl;
    }

    public function build()
    {
        return $this
            ->subject('Platform admin PIN reset — '.$this->loginEmail)
            ->view('mails.platform_admin_pin_reset');
    }
}
