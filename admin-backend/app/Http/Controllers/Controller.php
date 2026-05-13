<?php

namespace App\Http\Controllers;

use App\AppModules;
use App\UserAccesPermissions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Kutia\Larafirebase\Facades\Larafirebase;
use Illuminate\Support\Facades\Http;



class Controller extends BaseController
{
    const API_URI = 'https://fcm.googleapis.com/fcm/send';
    public function notification()
    {


        try {
            $fcmTokens = 'cnV8A1-tR_KmE8v-Ft3u98:APA91bESOPyyxlmmgGf_Pk6q9ofUPKQ20ypB9nH0sGm4Ws3kXvYpgxrZhP1MWrE_x8iLmnaJCUIRDC9bq25imhBlUpoEMCi9vafWsZfGDoxugo8r9qyFJ_4-q2RNIMOz4QU7NoY928hD';

            //Notification::send(null,new SendPushNotification($request->title,$request->message,$fcmTokens));

            /* or */

            //auth()->user()->notify(new SendPushNotification($title,$message,$fcmTokens));

            /* or */

            // $response = Larafirebase::withTitle("Laravel sample title")
            //     ->withBody('Sample body for the laravel message')
            //     ->sendMessage($fcmTokens);

            $fields = array(
                'to' => $fcmTokens,
                "notification" => [
                    "body" => "Order invoice sample FCM",
                    "title" => "Sample 7 FCM message",
                    "subtitle" => "Firebase dsfdsCloud Message Subtitle"
                ],
                "data" => [
                    'order_id' => 123232,
                    'customername' => 'Customer name'
                ]
            );


            $response = Http::withHeaders([
                'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY_CUSTOMER_APP')
            ])->post(self::API_URI, $fields);
            print_r(json_encode($response));
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Something goes wrong while sending notification.');
        }
    }
    public function addUserAccessPermission()
    {
        $userId = 115;

        $appModules = AppModules::select('module_id')->get();
        foreach ($appModules as $module) {
            $userModule = ["user_id" => $userId, "module_id" => $module->module_id];
            $allowedModule = [1, 2, 4, 5, 7, 8, 9, 11, 14, 15, 34, 36];
            $permissions = [
                "Create_priv" => "N",
                'Update_priv' => "N",
                'Read_priv' => "N",
                'Delete_priv' => "N",
                'Access_priv' => "N"
            ];
            if (in_array($module->module_id, $allowedModule)) {
                $permissions = [
                    "Create_priv" => "Y",
                    'Update_priv' => "Y",
                    'Read_priv' => "Y",
                    'Delete_priv' => "Y",
                    'Access_priv' => "Y"
                ];
            }

            UserAccesPermissions::firstOrCreate(
                $userModule,
                $permissions
            );
        }
    }
}
