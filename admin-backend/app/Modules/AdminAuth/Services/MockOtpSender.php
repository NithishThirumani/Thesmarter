<?php

namespace App\Modules\AdminAuth\Services;

use Illuminate\Support\Facades\Log;

/**
 * Mock OTP sender: log OTP for development. Replace with real mail/SMS in production.
 */
class MockOtpSender implements OtpSenderInterface
{
    public function send(string $email, string $otp): void
    {
        Log::channel('stack')->info('AdminAuth Mock OTP', [
            'email' => $email,
            'otp' => $otp,
            'message' => 'In production, send this OTP via email/SMS.',
        ]);
    }
}
