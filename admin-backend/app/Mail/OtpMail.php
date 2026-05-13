<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // important
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $purpose;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp, string $purpose = 'registration')
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
        // do NOT log OTP here
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->subject('theSmartr - One Time Code (OTP)')
            ->markdown('mails.otp')
            ->with([
                'otp' => $this->otp,
                'purpose' => $this->purpose,
            ]);
    }
}
