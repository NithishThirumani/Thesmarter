<?php

namespace App\Repositories;

use App\EmailOtp;
use App\User;
use Illuminate\Support\Facades\Hash;

class OtpRepository
{
    public function create(?int $userId, string $email, string $otpHash, string $purpose, \DateTime $expiresAt, string $ip): EmailOtp
    {
        return EmailOtp::create([
            'user_id' => $userId,
            'email' => $email,
            'otp_hash' => $otpHash,
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'ip' => $ip
        ]);
    }
    public function findLatestUnconsumedByEmailAndPurpose(string $email, string $purpose): ?EmailOtp
    {
        return EmailOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();
    }

}
