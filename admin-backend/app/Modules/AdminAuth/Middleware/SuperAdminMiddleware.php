<?php

namespace App\Modules\AdminAuth\Middleware;

use App\Modules\AdminAuth\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;

/**
 * Requires authenticated admin JWT and role {@see AdminUser::ROLE_SUPER_ADMIN}.
 */
class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->attributes->get('admin_user');
        if (!$admin instanceof AdminUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $role = $admin->role ?: AdminUser::ROLE_SUPER_ADMIN;
        if ($role !== AdminUser::ROLE_SUPER_ADMIN) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
