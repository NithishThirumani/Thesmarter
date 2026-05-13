<?php

namespace App\Http\Controllers\API;

use App\CompanyLocales;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocaleResource;
use App\Locales;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $locales = Locales::all();
        $response = array(
            "error_flag" => false,
            "error_message" => "",
            "locale" =>  LocaleResource::collection($locales)
        );
        return response()->json($response);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Locales $code)
    {
        return response(['locale' => new LocaleResource($code)]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function company($companyId)
    {

        $locales  = Locales::whereHas('companies', function ($query) use ($companyId) {
            return $query->where('company_id', $companyId);
        })->get();

        $response = array(
            "error_flag" => false,
            "error_message" => "",
            "locale" =>  LocaleResource::collection($locales)
        );
        return response()->json($response);
    }
}
