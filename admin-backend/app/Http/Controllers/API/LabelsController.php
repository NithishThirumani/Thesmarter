<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelsResource;
use App\Http\Resources\LocaleResource;
use App\Labels;
use App\Locales;
use App\LabelTranslations;
use Illuminate\Http\Request;

class LabelsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($code)
    {
        // Get all labels based on languauage 
        $labels = LabelTranslations::join('labels as l', 'l.id', '=', 'label_translations.label_id')
            ->join('locales as lc', 'lc.id', '=', 'label_translations.locale_id')
            ->where('lc.code', $code)
            ->get(['lc.code', 'l.code as label_code', 'label_translations.name']);

        // $fl = LabelsResource::collection($labels);
        $locale = Locales::where('code',$code)->first();
        // $localeObj = new LocaleController();
        $locales = new LocaleResource($locale);
        $finalLabls = array();
        foreach ($labels as $key => $value) {
            $finalLabls[$value->label_code] = $value->name;
        }
        $response = array(
            "error_flag"=>false,
            "error_message"=>"",
            "locale"=>$locales,
            "labels"=>$finalLabls
        );
        return response()->json( $response);
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
    public function show($id)
    {
        //
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
}
