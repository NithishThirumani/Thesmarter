<?php

namespace App\Jobs;

use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60; // seconds
    public string $email;
    public string $otp;
    public string $purpose;

    public function __construct(string $email, string $otp, string $purpose = 'registration')
    {
        $this->email = $email;
        $this->otp = $otp;
        $this->purpose = $purpose;
    }

    public function handle()
    {
        Mail::to($this->email)->send(new OtpMail($this->otp, $this->purpose));
    }
}
