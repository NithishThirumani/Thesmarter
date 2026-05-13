<?php

namespace App\Services\Auth;

use App\EmailOtp;
use App\UserDetail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\OtpRepository;
use App\Jobs\SendOtpEmailJob;
use App\Mail\WelcomeMail;
use App\Mail\CredentialsMail;

class OtpService
{
    protected $otpTtlMinutes = 10;
    protected $otpLength = 6;
    protected $maxAttempts = 5;
    protected $maxResends = 3;
    protected $otpRepository;
    protected $userRepository;


    public function __construct(OtpRepository $otpRepository, UserRepositoryInterface $userRepository)
    {
        $this->otpRepository = $otpRepository;
        $this->userRepository = $userRepository;
    }

    // generate and send OTP for registration flow
    public function sendRegistrationOtp(string $email, $user_id = null, string $ip): EmailOtp
    {
        // Always generate dynamic OTP (no hardcoded fallback).
        $otp = $this->generateNumericOtp($this->otpLength);
        // Need to validate is

        // store hashed
        $otpHash = Hash::make($otp);

        $expiresAt = Carbon::now()->addMinutes($this->otpTtlMinutes);

        $emailOtp = $this->otpRepository->create($user_id ?? null, $email, $otpHash, 'registration', $expiresAt, $ip);

        // queue the mail (so sending doesn't block)
        // Mail::to($email)->queue(new \App\Mail\OtpMail($otp, 'registration'));
        SendOtpEmailJob::dispatch($email, $otp, 'registration');

        return $emailOtp;
    }

    protected function generateNumericOtp($length = 6): string
    {
        $min = (int) str_pad('1', $length, '0'); // eg 100000
        $max = (int) str_repeat('9', $length); // eg 999999
        return (string) random_int($min, $max);
    }

    // verify OTP: return activated user or boolean
    public function verifyRegistrationOtp(string $email, string $otp): UserDetail
    {
        // fetch latest OTP record for this email/purpose that is not consumed
        $otpRecord = $this->otpRepository->findLatestUnconsumedByEmailAndPurpose($email, 'registration');

        if (!$otpRecord) {
            throw ValidationException::withMessages(['otp' => ['No OTP request found. Please request a new code.']]);
        }

        if ($otpRecord->isExpired()) {
            throw ValidationException::withMessages(['otp' => ['The verification code has expired. Please request a new code.']]);
        }

        if ($otpRecord->attempts >= $this->maxAttempts) {
            throw ValidationException::withMessages(['otp' => ['Too many attempts. Please request a new code.']]);
        }

        $otpRecord->attempts++;
        $otpRecord->save();

        // verify hash
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            throw ValidationException::withMessages(['otp' => ['Invalid verification code.']]);
        }

        // mark consumed
        $otpRecord->consumed_at = Carbon::now();
        $otpRecord->save();

        // Activate or create the user as needed
        $user = null;

        if ($otpRecord->user_id) {
            $user = $this->userRepository->findUserByIdSimple($otpRecord->user_id);
        } else {
            // Option A: if you created a pending user earlier, link via user_id.
            // Option B: you might create user at OTP verification time (if you didn't earlier).
            // Example: find by email login and mark active
            $user = $this->userRepository->findUserByEmail($email); // implement in repo
        }

        if (!$user) {
            throw ValidationException::withMessages(['email' => ['User record not found.']]);
        }

        // activate user and set active status (whatever column you use)
        $user->user_status = 1; // active
        $user->save();

        // === Generate PIN, store hashed, set expiry ===
        $pinLength = 4; // change if you want 4/5/6
        $plainPin = $this->generateNumericOtp($pinLength);

        // Hash & save to user (never store plain)
        $user->login->user_pin = Hash::make($plainPin);

        // Set pin expiry (e.g., 24 hours from now) — adjust as needed
        // $pinTtlHours = 24;
        // $user->pin_expires_at = Carbon::now()->addHours($pinTtlHours);
        $user->login->save();


        // === Queue emails ===
        // 1) Welcome mail (no sensitive info)
        Mail::to($user->login->email)->queue(new WelcomeMail($user->first_name ?? ''));

        // 2) Credentials mail (contains mobile and the plain PIN for first use)
        // Determine mobile value (adapt property name if different)
        $mobile = $user->login->user_mobile ?? null; // ensure mobile is present or adjust
        Mail::to($user->login->email)->queue(new CredentialsMail($user->first_name, $mobile, $plainPin));

        return $user;
    }

    public function resendRegistrationOtp(string $email, string $ip): EmailOtp
    {
        // Find last OTP and check resend_count
        $last = $this->otpRepository->findLatestUnconsumedByEmailAndPurpose($email, 'registration');

        if ($last && $last->resend_count >= $this->maxResends) {
            throw ValidationException::withMessages(['otp' => ['Resend limit reached.']]);
        }

        // Optionally reuse last OTP until expiry (but better to create a new one)
        $emailOtp = $this->sendRegistrationOtp($email, $last->user_id, $ip);

        // increment resend_count on previous record for auditing (optional)
        if ($last) {
            $last->resend_count++;
            $last->save();
        }

        return $emailOtp;
    }
}
