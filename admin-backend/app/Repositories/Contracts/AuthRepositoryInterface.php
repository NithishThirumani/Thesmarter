<?php

namespace App\Repositories\Contracts;

interface AuthRepositoryInterface
{
    public function verifyPin($userLogin, string $pin);
    public function generateTokens($userLogin);
    public function revokeTokens($userLogin);
}
