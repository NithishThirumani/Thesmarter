<?php

return [
    'jwt_secret' => env('ADMIN_JWT_SECRET', env('APP_KEY')),
    'jwt_access_ttl' => (int) env('ADMIN_JWT_ACCESS_TTL', 900),      // 15 min
    'jwt_refresh_ttl' => (int) env('ADMIN_JWT_REFRESH_TTL', 604800),  // 7 days

    /** Sign-in URL referenced in platform-admin invitation emails */
    'admin_portal_url' => env('ADMIN_PORTAL_URL', env('APP_URL', 'http://localhost')),

    /** Login / forgot-pin OTP: fixed value instead of random. Set ADMIN_USE_FIXED_OTP=false in production. */
    'use_fixed_otp' => filter_var(env('ADMIN_USE_FIXED_OTP', true), FILTER_VALIDATE_BOOLEAN),
    'fixed_otp' => env('ADMIN_FIXED_OTP', '123456'),

    /**
     * When true, OTP is sent with Laravel Mail (smtp, etc.). When false, OTP is only logged (MockOtpSender).
     * Set ADMIN_OTP_VIA_MAIL=true in production after mail is configured.
     */
    'otp_via_mail' => filter_var(env('ADMIN_OTP_VIA_MAIL', false), FILTER_VALIDATE_BOOLEAN),
];
