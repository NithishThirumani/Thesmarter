<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppFeaturesResource;
use App\AppFeatures;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CompanyFeatures;

class FeaturesController extends Controller
{
    private $otpFeatureId = 34;
    private $companyId ;

    public function __construct($companyId =0)
    {
        $this->companyId = $companyId;
    }
    public function index()
    {
        $features = AppFeatures::all();
        return response(['feature' => AppFeaturesResource::collection($features)]);
    }
    public function store()
    {
    }
    public function update(Request $request, AppFeatures $features)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'id' => 'required',
            'isActive' => 'required'
        ]);
        if ($validator->fails()) {
            return response([
                'error' => $validator->errors(), 'Validation Error'
            ]);
        }
        $features->find($data['id'])
            ->update([
                'feature_status' => ($data['isActive'] === true ? 'A' : 'D')
            ]);
        return response(['message' => 'Feature updated successfully']);
    }
    public function isOTPEnabled()
    {
        return CompanyFeatures::where('company_id',$this->companyId)->where('feature_id',$this->otpFeatureId)->exists();
    }
}
