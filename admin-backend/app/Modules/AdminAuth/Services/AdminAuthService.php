<?php

namespace App\Modules\AdminAuth\Services;

use App\Modules\AdminAuth\Models\AdminOtp;
use App\Modules\AdminAuth\Models\AdminRefreshToken;
use App\Modules\AdminAuth\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminAuthService
{
    private JwtService $jwt;
    private OtpSenderInterface $otpSender;

    public function __construct(JwtService $jwt, OtpSenderInterface $otpSender)
    {
        $this->jwt = $jwt;
        $this->otpSender = $otpSender;
    }

    /**
     * Step 1: Validate email, create/find admin user, generate OTP, send it.
     */
    public function login(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }

        $admin = AdminUser::where('email', $email)->first();
        if (!$admin) {
            throw new RuntimeException('No admin account found for this email.');
        }
        if (!$admin->is_active) {
            throw new RuntimeException('Account is inactive.');
        }

        // Invalidate any existing unverified OTPs for this admin
        AdminOtp::where('admin_id', $admin->id)->where('is_verified', false)->delete();

        $otpCode = $this->generateOtpCode();
        $otpHash = Hash::make($otpCode);
        $expiresAt = now()->addMinutes(AdminOtp::EXPIRY_MINUTES);

        AdminOtp::create([
            'admin_id' => $admin->id,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'is_verified' => false,
            'attempt_count' => 0,
        ]);

        $this->otpSender->send($admin->email, $otpCode);

        return [
            'message' => 'OTP sent to your email.',
            'email' => $email,
        ];
    }

    /**
     * Step 2: Verify PIN for the given email (must match stored pin_hash).
     */
    public function verifyPin(string $email, string $pin): array
    {
        $email = strtolower(trim($email));
        $admin = AdminUser::where('email', $email)->first();
        if (!$admin || !$admin->is_active) {
            throw new RuntimeException('Invalid credentials.');
        }
        if (!Hash::check($pin, $admin->pin_hash)) {
            throw new RuntimeException('Invalid PIN.');
        }
        return [
            'success' => true,
            'email' => $email,
        ];
    }

    /**
     * Step 3: Verify OTP and issue JWT access + refresh tokens.
     */
    public function verifyOtp(string $email, string $otp): array
    {
        $email = strtolower(trim($email));
        $admin = AdminUser::where('email', $email)->first();
        if (!$admin || !$admin->is_active) {
            throw new RuntimeException('Invalid credentials.');
        }

        $otpRecord = AdminOtp::where('admin_id', $admin->id)
            ->where('is_verified', false)
            ->orderByDesc('created_at')
            ->first();

        if (!$otpRecord) {
            throw new RuntimeException('No OTP found. Please request a new one.');
        }
        if ($otpRecord->isExpired()) {
            throw new RuntimeException('OTP has expired. Please request a new one.');
        }
        if ($otpRecord->isAttemptLimitReached()) {
            throw new RuntimeException('Too many failed attempts. Please request a new OTP.');
        }

        $otpRecord->increment('attempt_count');
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            throw new RuntimeException('Invalid OTP.');
        }

        $otpRecord->update(['is_verified' => true]);

        $accessToken = $this->jwt->issueAccessToken($admin);
        $refreshToken = $this->jwt->issueRefreshToken($admin);

        $refreshHash = hash('sha256', $refreshToken);
        $expiresAt = now()->addSeconds($this->jwt->getRefreshTokenTtl());
        AdminRefreshToken::create([
            'admin_id' => $admin->id,
            'token_hash' => $refreshHash,
            'expires_at' => $expiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => config('admin-auth.jwt_access_ttl', 900),
            'user' => $this->userPayload($admin),
        ];
    }

    /**
     * @return array{id: string, email: string, name: string, role: string}
     */
    private function userPayload(AdminUser $admin): array
    {
        $name = trim((string) ($admin->name ?? ''));
        if ($name === '') {
            $name = $admin->email;
        }

        return [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $name,
            'role' => $admin->role ?? AdminUser::ROLE_SUPER_ADMIN,
        ];
    }

    /**
     * Exchange refresh token for new access (and optionally refresh) token.
     */
    public function refreshToken(string $refreshToken): array
    {
        $payload = $this->jwt->decode($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'admin_refresh') {
            throw new RuntimeException('Invalid refresh token.');
        }

        $admin = AdminUser::find($payload['sub']);
        if (!$admin || !$admin->is_active) {
            throw new RuntimeException('Invalid refresh token.');
        }

        $tokenHash = hash('sha256', $refreshToken);
        $record = AdminRefreshToken::where('admin_id', $admin->id)
            ->where('token_hash', $tokenHash)
            ->first();
        if (!$record || $record->isExpired()) {
            throw new RuntimeException('Refresh token expired or invalid.');
        }

        $record->delete();

        $newAccessToken = $this->jwt->issueAccessToken($admin);
        $newRefreshToken = $this->jwt->issueRefreshToken($admin);
        $newHash = hash('sha256', $newRefreshToken);
        AdminRefreshToken::create([
            'admin_id' => $admin->id,
            'token_hash' => $newHash,
            'expires_at' => now()->addSeconds($this->jwt->getRefreshTokenTtl()),
        ]);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => config('admin-auth.jwt_access_ttl', 900),
            'user' => $this->userPayload($admin),
        ];
    }

    /**
     * Logout: invalidate refresh token if provided.
     */
    public function logout(string $refreshToken): void
    {
        $payload = $this->jwt->decode($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'admin_refresh') {
            return;
        }
        $tokenHash = hash('sha256', $refreshToken);
        AdminRefreshToken::where('admin_id', $payload['sub'])->where('token_hash', $tokenHash)->delete();
    }

    public function findAdminById(string $id): ?AdminUser
    {
        return AdminUser::find($id);
    }

    /**
     * Forgot PIN: send OTP to email (same as login; user then uses OTP on reset-pin with new PIN).
     */
    public function forgotPin(string $email): array
    {
        return $this->login($email);
    }

    /**
     * Reset PIN: verify OTP then set new PIN. No login required.
     */
    public function resetPin(string $email, string $otp, string $newPin): array
    {
        $email = strtolower(trim($email));
        $admin = AdminUser::where('email', $email)->first();
        if (!$admin || !$admin->is_active) {
            throw new RuntimeException('Invalid credentials.');
        }

        $otpRecord = AdminOtp::where('admin_id', $admin->id)
            ->where('is_verified', false)
            ->orderByDesc('created_at')
            ->first();

        if (!$otpRecord) {
            throw new RuntimeException('No OTP found. Please request a new one.');
        }
        if ($otpRecord->isExpired()) {
            throw new RuntimeException('OTP has expired. Please request a new one.');
        }
        if ($otpRecord->isAttemptLimitReached()) {
            throw new RuntimeException('Too many failed attempts. Please request a new OTP.');
        }

        $otpRecord->increment('attempt_count');
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            throw new RuntimeException('Invalid OTP.');
        }

        $otpRecord->update(['is_verified' => true]);
        $admin->update(['pin_hash' => Hash::make($newPin)]);

        return ['success' => true, 'message' => 'PIN has been reset. You can sign in with your new PIN.'];
    }

    /**
     * Change PIN: for authenticated admin, verify current PIN and set new PIN.
     */
    public function changePin(AdminUser $admin, string $currentPin, string $newPin): array
    {
        if (!Hash::check($currentPin, $admin->pin_hash)) {
            throw new RuntimeException('Current PIN is incorrect.');
        }
        $admin->update(['pin_hash' => Hash::make($newPin)]);
        return ['success' => true, 'message' => 'PIN has been changed.'];
    }

    private function generateOtpCode(): string
    {
        if (config('admin-auth.use_fixed_otp')) {
            return (string) config('admin-auth.fixed_otp');
        }

        return (string) random_int(100000, 999999);
    }
}
