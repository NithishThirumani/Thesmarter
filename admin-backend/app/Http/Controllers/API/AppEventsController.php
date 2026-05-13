<?php

namespace App\Http\Controllers\API;

use App\AppEvents;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AppEventsResource;
use Illuminate\Support\Facades\Validator;

class AppEventsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $events = AppEvents::all();
        return response(['events' => AppEventsResource::collection($events)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'isActive' => 'required'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $event = AppEvents::create($data);
        return response(['event' => new AppEventsResource($event), 'message' => 'Event registered successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\AppEvents  $appEvents
     * @return \Illuminate\Http\Response
     */
    public function show(AppEvents $event)
    {
        return response(['event'=>new AppEventsResource($event)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AppEvents  $appEvents
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AppEvents $event)
    {
        $data = $request->all();
        $validator = Validator::make($data,[
            'name'=>'required|max:255',
            'isActive'=>'required'
        ]);
        if($validator->fails()){
            return response(['error'=>$validator->errors(),'Validation Error']);
        }
        $event->update($data);
        return response(['channel'=>new AppEventsResource($event),'message'=>'Event successfully updated']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AppEvents  $appEvents
     * @return \Illuminate\Http\Response
     */
    public function destroy(AppEvents $event)
    {
        $event->delete();
        return response(['message'=>'App event has beem deleted']);
    }
}
