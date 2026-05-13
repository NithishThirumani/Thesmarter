<?php

namespace App\Channels;

use App\NotificationDB;
use App\NotificationPublisher;
use App\NotificationRecipient;
use Illuminate\Notifications\Notification;

class CustomDBChannel
{
    public function send($notifiable, Notification $notification)
    {

        $data = method_exists($notification, 'toCustomDB')
            ? $notification->toCustomDB($notifiable)
            : $notification->toArray($notifiable);


        // Add to notification 
        $notification = NotificationDB::create([
            'event_id' => $data['event'],
            'message' => $data['message'],
            'redirect' => $data['redirect'],
            'payload' => $data['payload']
        ]);
        // Add to notification publisher 
        NotificationPublisher::create([
            'notification_id' => $notification->id,
            'company_id' => $data['companyId'],
            'user_id' => $data['senderId']
        ]);
        // Looping through all the recipients
        foreach ($data['recipients'] as $recipient) {
            // Add to notification reciver 
           
            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'user_id' => $recipient['user_id'],
                'app_type'=>$data['appType'],
                'company_id'=>$data['companyId']
            ]);
        }


        return true;
    }
}
