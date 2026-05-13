<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureV2AuthEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('auth_v2.use_v2_auth', false)) {
            return response()->json([
                'success' => false,
                'message' => 'V2 authentication is disabled. Set USE_V2_AUTH=true to enable.',
            ], 403);
        }

        return $next($request);
    }
}
