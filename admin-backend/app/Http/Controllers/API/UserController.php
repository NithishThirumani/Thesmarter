<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\OTP;
use App\UserLogin;
use App\Services\UserService;
use App\CompanyDetail;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\User\UpdateUserInfoRequest;
use App\Http\Requests\User\LookupUserRequest;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Resources\LookupResource;
use App\Http\Resources\UserListsResource;
use App\Http\Resources\UserResource;
use App\User;
use App\UserDetail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Log;
use Nette\Utils\Json;

class UserController extends Controller
{
    private $otpStatus = 0;
    //
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function getUser(Request $request)
    {
        $user = $request->user();
        $userDetails = $this->userService->getUserProfile($user->user_id);
        return response()->json($userDetails);
    }
    public function register(RegisterUserRequest $request): JsonResponse
    {
        try {
            $result = $this->userService->registerUser($request->validated());

            // Uniform guard: if user object is present, wrap with Resource,
            // else return payload message
            $userResource = isset($result['user']) && $result['user'] ? new UserResource($result['user']) : null;

            switch ($result['status']) {
                case 'mapped':
                    return response()->json([
                        'error_flag' => false,
                        'message' => $result['message'] ?? 'User mapped to company.',
                        'data' => $userResource,
                    ], 200);

                case 'created':
                    // company-created active user
                    return response()->json([
                        'error_flag' => false,
                        'message' => $result['message'] ?? 'User registered successfully.',
                        'data' => $userResource,
                    ], 201);

                case 'pending':
                    // pending verification: OTP sent
                    return response()->json([
                        'error_flag' => false,
                        'message' => $result['message'] ?? 'Verification required. OTP sent.',
                        'otp_sent' => $result['otp_sent'] ?? false,
                        'data' => $userResource,
                    ], 201);

                case 'existing':
                    return response()->json([
                        'error_flag' => true,
                        'message' => $result['message'] ?? 'User already exists.',
                        'data' => $userResource,
                    ], 422);

                default:
                    // fallback
                    return response()->json([
                        'error_flag' => true,
                        'message' => 'Unable to complete registration.',
                    ], 500);
            }
        } catch (BusinessRuleException $e) {
            \Log::warning('User Registration Business Rule', ['error' => $e->getMessage()]);
            return response()->json([
                'error_flag' => true,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('User Registration Failed', ['error' => $e->getMessage()], ['trace' => $e->getTraceAsString()]);
            report($e);
            return response()->json([
                'error_flag' => true,
                'message' => 'Registration failed.',
            ], 500);
        }
    }

    // public function verifyOtp(Request $request): JsonResponse
    // {
    //     $data = $request->validate([
    //         'emal' => 'required|integer|exists:user_login,email',
    //         'phone' => 'required|integer|exists:user_login,user_mobile',
    //         'otp' => 'required|string|max:6',
    //     ]);

    //     try {

    //         if ($request->input('otp') != '123456') {
    //             throw new \Exception('Invalid OTP provided.');
    //         }

    //         $user = $this->userService->findByPhoneOrEmail($data);

    //         return response()->json([
    //             'error_flag' => false,
    //             'message' => 'OTP verified successfully.',
    //             'data' => new UserResource($user)
    //         ], 200);
    //     } catch (BusinessRuleException $e) {
    //         \Log::warning('OTP Verification Business Rule', ['error' => $e->getMessage()]);

    //         return response()->json([
    //             'error_flag' => true,
    //             'message'    => $e->getMessage(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         \Log::error('OTP Verification Failed', ['error' => $e->getMessage()]);
    //         report($e);

    //         return response()->json([
    //             'error_flag' => true,
    //             'message'    => 'OTP verification failed.',
    //         ], 500);
    //     }
    // }
}
