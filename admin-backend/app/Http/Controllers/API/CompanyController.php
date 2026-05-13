<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    //



    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'loggedin_user_id' => 'required|integer',
            'loggedin_user_type'=>'required|ineteger',
            'name'=>'required|string|max:255',
            'lob'=>'required|integer',
            'company_website'=>'url',
            'zone'=>'required|integer',
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        // Create Company

        // Create Company - related branches

        // Create company - contact details 
        
    }
}
