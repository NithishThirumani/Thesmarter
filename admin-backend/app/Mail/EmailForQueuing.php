<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailForQueuing extends Mailable
{
    use Queueable, SerializesModels;

    public $body;
    public $subject;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject,$body)
    {
        $this->body = $body;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@bizwy.in', 'Bizwy')
            ->subject($this->subject)
            ->view('mails.invoice')
            ->with('data',$this->body);
    }
}
