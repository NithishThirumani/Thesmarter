<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $token = $request->cookie('api_token');
        $hashedToken = hash('sha256', $token);

        // Manually find the token and user
        $personalAccessToken = PersonalAccessToken::where('token', $hashedToken)->first();
        $user = $personalAccessToken?->tokenable;

        // \Log::info('Authenticate middleware hit', [
        //     'user' => $user,
        //     'tokenFromCookie' => $token,
        //     'hashedTokenFromCookie' => $hashedToken,
        //     'tokenable' => $personalAccessToken?->tokenable,
        // ]);

        // Check if the token exists, is not expired, is not revoked, and has the required abilities
        if (!$user || $personalAccessToken->expires_at && $personalAccessToken->expires_at->isPast() || $personalAccessToken->revoked) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated, token expired, or token revoked.',
            ], 401);
        }

        // Manually authenticate the user
        Auth::login($user);

        return $next($request);
    }
}