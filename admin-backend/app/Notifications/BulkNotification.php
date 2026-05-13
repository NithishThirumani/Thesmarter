<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kutia\Larafirebase\Messages\FirebaseMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class BulkNotification extends Notification
{
    use Queueable;
    private $data = array();
    private $supportedChannels = array(
        'push' => 'firebase', 'database' => 'customdb', 'sms' => TwilioChannel::class
    );
    private $channels = array('push', 'database');
    private $preferedChannels = array();
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($channels, $data)
    {
        $this->data = $data;
        $this->channels = $channels ?? $this->channels;
        foreach ($this->channels as $channel) {
            array_push($this->preferedChannels, $this->supportedChannels[$channel]);
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->preferedChannels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toCustomDB($notifiable)
    {
        return [
            'event' => $this->data['event'],
            'message' => $this->data['message'],
            'redirect' => $this->data['redirect'],
            'payload' => json_encode($this->data['payload']),
            'companyId' => $this->data['companyId'],
            'appType' => $this->data['appType'],
            'senderId' => $this->data['senderId'],
            'recipients' => $this->data['recipients']
        ];
    }
    public function toTwilio($notifiable)
    {

        return (new TwilioSmsMessage())
            ->content($this->data['message']);
    }


    public function toFirebase($notifiable)
    {

        if (empty($this->data['fcmTokens'])) {
            return false;
        }

        return (new FirebaseMessage)
            ->withTitle($this->data['title'])
            ->withBody($this->data['message'])
            ->withAuthenticationKey($this->data['authentication_key'])
            ->withAdditionalData([
                'order' => $this->data['payload'],
                'redirect' => $this->data['redirect'],
            ])
            ->withPriority('high')
            ->asNotification($this->data['fcmTokens']);
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
