<?php

namespace App\Http\Middleware;

use App\UserCompanyRoleV2;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtV2ValidateCompany
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = auth('jwt')->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            $payload = auth('jwt')->payload();
            $companyId = $payload->get('company_id');
            $roleId = $payload->get('role_id');
            if ($companyId === null || $roleId === null) {
                return response()->json(['success' => false, 'message' => 'Invalid token context.'], 401);
            }
            $ok = UserCompanyRoleV2::query()
                ->where('user_id', $user->user_id)
                ->where('company_id', (int) $companyId)
                ->where('role_id', (int) $roleId)
                ->where('status', 1)
                ->exists();
            if (!$ok) {
                return response()->json(['success' => false, 'message' => 'User is not active for this company.'], 403);
            }
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
