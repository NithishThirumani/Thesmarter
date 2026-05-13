<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $mobile;
    public $pin; // plain PIN only for the email body

    public function __construct($user, string $mobile, string $pin)
    {
        $this->user = $user;
        $this->mobile = $mobile;
        $this->pin = $pin;
    }

    public function build()
    {
        return $this
            ->subject('Your login credentials')
            ->markdown('mails.credentials')
            ->with([
                'name' => $this->user,
                'phone' => $this->mobile,
                'pin' => $this->pin,
            ]);
    }
}
