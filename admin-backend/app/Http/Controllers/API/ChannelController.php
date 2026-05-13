<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\NotificationChannels;
use App\Http\Resources\NotificationChannelsResource;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $channels = NotificationChannels::all();
        return response(['channels' => NotificationChannelsResource::collection($channels)]);
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
        $channel = NotificationChannels::create($data);
        return response(['channel' => new NotificationChannelsResource($channel), 'message' => 'Channel Created Successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\NotificationChannels  $notificationChannels
     * @return \Illuminate\Http\Response
     */
    public function show(NotificationChannels $channel)
    {        
        return response(['channel' => new NotificationChannelsResource($channel)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\NotificationChannels  $notificationChannels
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, NotificationChannels $channel)
    {
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'name' => 'required|max:255',
            'isActive'=>'required'
        ]);
        
        if ($validator->fails()) {
            return response([
                'error' => $validator->errors(), 'Validation Error'
            ]);
        }
        $channel->update($data);

        
        return response(['channel' => new NotificationChannelsResource($channel), 'message' => 'Product updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\NotificationChannels  $notificationChannels
     * @return \Illuminate\Http\Response
     */
    public function destroy(NotificationChannels $channel)
    {
        
        $channel->delete();
        return response(['message' => 'Channel has been deleted']);
    }
}
