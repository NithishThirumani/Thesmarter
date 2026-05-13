<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Exception;
use Log;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\UserCompanies;

class UserCompaniesController extends Controller
{
    public function mapping(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer',
            'user_id' => 'required|integer',
            'user_type' => 'required|integer'
        ]);
        $data['otp'] = $data['otp'] ?? '';
        DB::beginTransaction();
        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors());
            }
            $feature = new FeaturesController($data['company_id']);
            $auth = new AuthController();
            if ($feature->isOTPEnabled() && !empty($data['otp']) && !$auth->verifyOTP($data['user_id'], $data['otp'])) {
                throw new Exception('Invalid OTP provided');
            }
            $user = array(
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type']
            );
            $status = array(
                'status' => 1,
                'creator_id' => $data['loggedin_user_id']
            );

            // Map user to Company
            UserCompanies::updateOrInsert($user, $status);

            // User Type Executive 
            // Add access controller as well to the users 

            DB::commit();
            $response = array(
                'error' => false,
                'message' => 'User successfully added to company'
            );
            return response()->json($response, 200);
        } catch (Exception $ex) {
            DB::rollback();
            $response = array(
                'error' => true,
                'message' => $ex->getMessage()
            );
            return response()->json($response, 422);
        }
    }
}
