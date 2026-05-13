<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\OTP;
use App\UserLogin;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\AuthService;
use App\Services\Auth\OtpService;
use App\Http\Requests\OtpRequest;
use App\Http\Resources\UserResource;
use App\Http\Requests\VerifyPhoneAndPinRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private $otpStatus = 0;
    //
    protected $authService;
    protected $otpService;

    public function __construct(AuthService $authService, OtpService $otpService)
    {
        $this->authService = $authService;
        $this->otpService = $otpService;
    }
    public function verifyOTP($userId, $otp)
    {
        $user = UserLogin::where('user_id', $userId)->first();
        $otpDetail = OTP::where('user_mobile', $user->user_mobile)->where('status', $this->otpStatus)->first();
        if ($otpDetail->secret_code == $otp) {
            // Update User otp 
            $otpDetail->update(['status' => 1]);
            return true;
        }
        return false;
    }
    public function verifyPhone(VerifyPhoneAndPinRequest $request)
    {
        try {
            $data = $request->all();
            $user = $this->authService->verifyPhone($data['phone']);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Phone number not registered'], 404);
            }
            return response()->json(['success' => true, 'message' => 'Phone number is registered'], 200);
        } catch (\Exception $ex) {
            // Log::error('Error verifying PIN: ' . $e->getMessage());
            return response()->json(['error' => $ex->getMessage()], 401);
        }
    }

    public function verifyPin(VerifyPhoneAndPinRequest $request): JsonResponse
    {


        try {

            $validated = $request->all();

            $response = $this->authService->verifyPinAndGenerateTokens($validated['phone'], $validated['pin']);
            return response()->json([
                'success' => true,
                'message' => 'OTP Verified',
            ])->cookie('api_token', $response['api_token'], 60, '/', 'merchant.bizwy.in', true, true); // HTTP-only & Secure
            // return response()->json(['success' => true, 'message' => 'Pin verification successful', "user_identifier" => $response['user_identifier'], 'api_token' => $response['api_token']], 200);
            // return response()->json($response);
        } catch (\Exception $e) {
            // Log::error('Error verifying PIN: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
            // return response()->json(['error' => $e->getMessage()], 401);
        }
    }
    public function deactivateAccount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->all();
            $response = $this->authService->verifyAndDeactivateAccount($user, $data['pin']);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }
    public function logout(Request $request)
    {
        $token = $request->cookie('api_token');
        $hashedToken = hash('sha256', $token);

        // Manually find the token and user
        $personalAccessToken = PersonalAccessToken::where('token', $hashedToken)->first();
        $user = $personalAccessToken?->tokenable;
        if ($user) {
            $personalAccessToken->delete();

            // Alternatively, revoke all tokens for the user
            // $user->tokens()->delete();
        }
        // Optionally, clear the session (if using session-based authentication)
        // Auth::guard('web')->logout();

        // Return a success response
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    // Additional methods for registration, OTP handling, etc., can be added here
    public function sendOtp(OtpRequest $request)
    {
        $data = $request->validated();
        try {
            $otp = $this->otpService->sendRegistrationOtps($data['email'], null, $request->ip());
            $email = $data['email'];
            return response()->json([
                'error_flag' => false,
                'message' => 'OTP sent to email.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error_flag' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function authOtp(OtpRequest $request)
    {

        $data = $request->validated();
        try {
            $user = $this->otpService->verifyRegistrationOtp($data['email'], $data['otp']);
        
            return response()->json([
                'error_flag' => false,
                'message' => 'OTP verified successfully.',
                'data' => new UserResource($user)
            ], 200);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstMessage = collect($errors)->flatten()->first();
            return response()->json([
                'error_flag' => true,
                'message' => $firstMessage
                
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Verify OTP failed', ['err' => $e->getMessage()]);
            return response()->json([
                'error_flag' => true,
                'message' => 'OTP verification failed.',
                'error'=>$e->getMessage()
            ], 500);
        }
    }
    public function resendOtp(OtpRequest $request)
    {
        $data = $request->validated();
        try {
            $otp = $this->otpService->resendRegistrationOtp($data['email'], $request->ip());
            return response()->json([
                'error_flag' => false,
                'message' => 'OTP re-sent to email.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error_flag' => true,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Resend OTP failed', ['err' => $e->getMessage()]);
            return response()->json([
                'error_flag' => true,
                'message' => 'OTP re-send failed.'
            ], 500);
        }
    }
}
