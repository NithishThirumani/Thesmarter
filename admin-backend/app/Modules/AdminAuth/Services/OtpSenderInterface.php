<?php

namespace App\Modules\AdminAuth\Services;

interface OtpSenderInterface
{
    public function send(string $email, string $otp): void;
}
