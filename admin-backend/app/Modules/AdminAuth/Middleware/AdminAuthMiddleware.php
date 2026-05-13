<?php

namespace App\Modules\AdminAuth\Middleware;

use App\Modules\AdminAuth\Models\AdminUser;
use App\Modules\AdminAuth\Services\JwtService;
use Closure;
use Illuminate\Http\Request;

class AdminAuthMiddleware
{
    private JwtService $jwt;

    public function __construct(JwtService $jwt)
    {
        $this->jwt = $jwt;
    }

    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $token = trim($m[1]);
        $payload = $this->jwt->decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'admin_access') {
            return response()->json(['success' => false, 'message' => 'Invalid or expired token.'], 401);
        }
        $admin = AdminUser::find($payload['sub']);
        if (!$admin || !$admin->is_active) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $request->attributes->set('admin_user', $admin);
        return $next($request);
    }
}
