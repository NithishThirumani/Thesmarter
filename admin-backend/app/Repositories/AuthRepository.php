<?php

namespace App\Repositories;

use App\UserDetail;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    public function verifyPin($userLogin, string $pin) 
    {
      
        return $userLogin && Hash::check($pin, $userLogin->user_pin);
    }

    public function generateTokens($user)
    {
        // $user = UserDetail::where('user_id',$userId)->dd();
        // Step 2: Check for active tokens
        $activeToken = $user->tokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($activeToken) {
            return [
                'access_token' => $activeToken->id,
                'refresh_token' => $activeToken->id,
            ];
        }
        // Revoke all old tokens
        $user->tokens()->update(['revoked' => true]);

        $tokenResult = $user->createToken('User Access Token');
        return [
            'access_token' => $tokenResult->accessToken,
            'refresh_token' => $tokenResult->token->id,
        ];
    }

    public function revokeTokens($userLogin)
    {
        $userLogin->tokens()->delete();
    }
}
