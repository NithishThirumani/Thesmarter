<?php

namespace App\Modules\AdminAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminAuth\Services\AdminAuthService;
use App\Modules\AdminAuth\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    private AdminAuthService $authService;
    private JwtService $jwtService;

    public function __construct(AdminAuthService $authService, JwtService $jwtService)
    {
        $this->authService = $authService;
        $this->jwtService = $jwtService;
    }

    /**
     * POST /admin/auth/login — validate email, generate and send OTP.
     */
    public function login(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email.'], 422);
        }
        try {
            $result = $this->authService->login($request->input('email'));
            $payload = ['success' => true, 'message' => $result['message'], 'email' => $result['email']];
            $payload = array_merge($payload, $this->otpDevHintPayload());

            return response()->json($payload, 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /admin/auth/verify-pin — validate PIN for email.
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'pin' => 'required|string|min:4|max:12',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }
        try {
            $this->authService->verifyPin($request->input('email'), $request->input('pin'));
            return response()->json(['success' => true, 'message' => 'PIN verified.', 'email' => $request->input('email')], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /admin/auth/verify-otp — validate OTP, issue JWT access + refresh.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }
        try {
            $result = $this->authService->verifyOtp($request->input('email'), $request->input('otp'));
            return response()->json(array_merge(['success' => true], $result), 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /admin/auth/refresh-token — exchange refresh token for new tokens.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Refresh token required.'], 422);
        }
        try {
            $result = $this->authService->refreshToken($request->input('refresh_token'));
            return response()->json(array_merge(['success' => true], $result), 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /admin/auth/logout — invalidate refresh token.
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');
        if ($refreshToken) {
            try {
                $this->authService->logout($refreshToken);
            } catch (\Throwable $e) {
                // best-effort
            }
        }
        return response()->json(['success' => true, 'message' => 'Logged out.'], 200);
    }

    /**
     * GET /admin/auth/me — return current admin from JWT (protected by adminAuth middleware).
     */
    public function me(Request $request): JsonResponse
    {
        $admin = $request->attributes->get('admin_user');
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'name' => trim((string) ($admin->name ?? '')) !== '' ? trim((string) $admin->name) : $admin->email,
                'phone_number' => $admin->phone_number,
                'role' => $admin->role ?? \App\Modules\AdminAuth\Models\AdminUser::ROLE_SUPER_ADMIN,
            ],
        ], 200);
    }

    /**
     * POST /admin/auth/forgot-pin — send OTP to email for PIN reset.
     */
    public function forgotPin(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), ['email' => 'required|email']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid email.'], 422);
        }
        try {
            $result = $this->authService->forgotPin($request->input('email'));
            $payload = [
                'success' => true,
                'message' => 'OTP sent. Use it with your new PIN on the reset form.',
                'email' => $result['email'],
            ];
            $payload = array_merge($payload, $this->otpDevHintPayload());

            return response()->json($payload, 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * @return array<string, string>
     */
    private function otpDevHintPayload(): array
    {
        if (! config('admin-auth.use_fixed_otp')) {
            return [];
        }
        $hint = 'Development: OTP is '.config('admin-auth.fixed_otp').'.';
        if (! config('admin-auth.otp_via_mail')) {
            $hint .= ' Set ADMIN_OTP_VIA_MAIL=true (and configure mail) to deliver this code by email; otherwise it is only logged server-side.';
        }

        return ['otp_dev_hint' => $hint];
    }

    /**
     * POST /admin/auth/reset-pin — verify OTP and set new PIN (no auth required).
     */
    public function resetPin(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'new_pin' => 'required|string|min:4|max:12',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }
        try {
            $this->authService->resetPin(
                $request->input('email'),
                $request->input('otp'),
                $request->input('new_pin')
            );
            return response()->json(['success' => true, 'message' => 'PIN has been reset. Sign in with your new PIN.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * POST /admin/auth/change-pin — change PIN (requires adminAuth).
     */
    public function changePin(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'current_pin' => 'required|string|min:4|max:12',
            'new_pin' => 'required|string|min:4|max:12',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }
        $admin = $request->attributes->get('admin_user');
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        try {
            $this->authService->changePin($admin, $request->input('current_pin'), $request->input('new_pin'));
            return response()->json(['success' => true, 'message' => 'PIN has been changed.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }
}
