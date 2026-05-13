<?php

namespace App\Modules\AdminAuth\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Sends admin login / forgot-PIN OTP via Laravel mail (respects platform mail settings in boot).
 */
class MailOtpSender implements OtpSenderInterface
{
    public function send(string $email, string $otp): void
    {
        $appName = (string) config('app.name', 'Admin');
        $subject = "{$appName} verification code";
        $body = "Your one-time verification code is: {$otp}\r\n\r\n"
            ."If you did not request this, you can ignore this message.\r\n\r\n"
            .'This code expires in a few minutes.';

        try {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (Throwable $e) {
            Log::error('Admin OTP mail send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(
                'Could not send the verification email. Configure mail under Admin → Platform mail settings (or .env), then try again.',
                0,
                $e
            );
        }
    }
}
