<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\V2\AuthServiceV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthControllerV2 extends Controller
{
    public function login(Request $request, AuthServiceV2 $authServiceV2): JsonResponse
    {
        $data = $request->validate([
            'login' => 'required|string|max:191',
            'pin' => 'required|string|max:255',
            'company_id' => 'nullable|integer|min:1',
        ]);
        try {
            $out = $authServiceV2->login(
                $data['login'],
                $data['pin'],
                $data['company_id'] ?? null
            );
            if (!empty($out['needs_company_selection']) && $out['needs_company_selection']) {
                return response()->json(['success' => true] + $out, 200);
            }
            return response()->json(['success' => true] + $out, 200);
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Login failed.';

            return response()->json(['success' => false, 'message' => $message], 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth('jwt')->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            $payload = auth('jwt')->payload();

            return response()->json([
                'success' => true,
                'user_id' => (int) $user->user_id,
                'company_id' => $payload->get('company_id'),
                'role_id' => $payload->get('role_id'),
                'actor' => $payload->get('actor'),
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }
}
