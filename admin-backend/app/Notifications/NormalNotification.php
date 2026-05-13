<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;

class NormalNotification extends Notification
{
    use Queueable;
    private $notifyData;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->notifyData = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['firebase','customdb'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toCustomDB($notifiable)
    {
        return [
            'event' => $this->notifyData['event'],
            'message' => $this->notifyData['message'],
            'redirect' => $this->notifyData['redirect'],
            'payload' => json_encode($this->notifyData['payload']),
            'companyId' => $this->notifyData['companyId'],
            'appType' => $this->notifyData['appType'],
            'senderId' => $this->notifyData['senderId'],
            'recipients' => $this->notifyData['recipients']
        ];
    }


    public function toFirebase($notifiable)
    {
       
        if(empty($this->notifyData['fcmTokens'])){
            return false;
        }
        
        return (new FirebaseMessage)
            ->withTitle($this->notifyData['title'])
            ->withBody($this->notifyData['message'])
            ->withAuthenticationKey($this->notifyData['authentication_key'])
            ->withAdditionalData([
                'order' => $this->notifyData['payload'],
                'redirect' => $this->notifyData['redirect'],
            ])
            ->withPriority('high')
            ->asNotification($this->notifyData['fcmTokens']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
