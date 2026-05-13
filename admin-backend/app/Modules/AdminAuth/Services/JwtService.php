<?php

namespace App\Modules\AdminAuth\Services;

use App\Modules\AdminAuth\Models\AdminUser;
use Exception;

class JwtService
{
    /** Access token TTL in seconds (short-lived). */
    private int $accessTtl;

    /** Refresh token TTL in seconds. */
    private int $refreshTtl;

    private string $secret;

    public function __construct()
    {
        $this->secret = config('admin-auth.jwt_secret', config('app.key'));
        $this->accessTtl = (int) config('admin-auth.jwt_access_ttl', 900);   // 15 min
        $this->refreshTtl = (int) config('admin-auth.jwt_refresh_ttl', 604800); // 7 days
    }

    public function issueAccessToken(AdminUser $admin): string
    {
        $now = time();
        $payload = [
            'sub' => $admin->id,
            'email' => $admin->email,
            'role' => $admin->role ?? AdminUser::ROLE_SUPER_ADMIN,
            'iat' => $now,
            'exp' => $now + $this->accessTtl,
            'type' => 'admin_access',
        ];
        return $this->encode($payload);
    }

    public function issueRefreshToken(AdminUser $admin): string
    {
        $now = time();
        $payload = [
            'sub' => $admin->id,
            'iat' => $now,
            'exp' => $now + $this->refreshTtl,
            'type' => 'admin_refresh',
            'jti' => bin2hex(random_bytes(16)),
        ];
        return $this->encode($payload);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!$payload || !$this->verifySignature($parts[0], $parts[1], $parts[2])) {
            return null;
        }
        if (!empty($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    public function getRefreshTokenTtl(): int
    {
        return $this->refreshTtl;
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h = $this->base64UrlEncode(json_encode($header));
        $p = $this->base64UrlEncode(json_encode($payload));
        $sig = $this->base64UrlEncode($this->sign($h . '.' . $p));
        return $h . '.' . $p . '.' . $sig;
    }

    private function verifySignature(string $headerB64, string $payloadB64, string $sigB64): bool
    {
        $message = $headerB64 . '.' . $payloadB64;
        $expected = $this->base64UrlEncode($this->sign($message));
        return hash_equals($expected, $sigB64);
    }

    private function sign(string $message): string
    {
        return hash_hmac('sha256', $message, $this->secret, true);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
