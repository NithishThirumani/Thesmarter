<?php

namespace App\Services;

use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Exception;

class AuthService
{
    protected $authRepository;
    protected $userRepository;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->authRepository = $authRepository;
        $this->userRepository = $userRepository;
    }
    public function verifyPhone(string $mobile)
    {

        $user = $this->userRepository->findUserByMobile($mobile);
        if (!$user) {
            return false;
        }
        return $user;
    }


    public function verifyPinAndGenerateTokens(string $mobile, string $pin)
    {

        $userLogin = $this->userRepository->findUserByMobile($mobile);
        if (!$this->authRepository->verifyPin($userLogin, $pin)) {
            throw new Exception('Invalid PIN provided');
        }

        $canAccess = (new UserService($this->userRepository))->checkUserAccess($userLogin->user_id);

        if (!$canAccess) {
            throw new Exception('Access denied to user contact admin');
        }
        // $tokens = $this->authRepository->generateTokens($userLogin);
        $user_id = $userLogin->details->user_id;
        // Create Sanctum token for API authentication
        $token = $userLogin->createToken('api_token')->plainTextToken;
        // Manually hash and save the token to the database
        $personalAccessToken = new \Laravel\Sanctum\PersonalAccessToken();
        $personalAccessToken->tokenable_type = get_class($userLogin);
        $personalAccessToken->tokenable_id = $user_id;
        $personalAccessToken->name = 'api_token';
        $personalAccessToken->token = hash('sha256', $token); // Manually hash the token
        $personalAccessToken->abilities = ['*'];
        $personalAccessToken->save();
        \Log::info('API Token', ['token' => $token, 'hashed_token' => hash('sha256', $token)]); // Log the user
        // Store the token in the session
        // session(['api_token' => $token]);
        // print_r($user_id);exit;
        return ['user_identifier' => $user_id, 'api_token' => $token];
    }
    public function verifyAndDeactivateAccount(object $user,string $pin)
    {
        if (!$this->authRepository->verifyPin($user, $pin)) {
            throw new Exception('Invalid PIN provided');
        }
        $this->userRepository->deactivateUser($user->user_id);
        return ['success' => true, 'message' => 'Account deactivated and user logged out successfully.'];
    }
} 
