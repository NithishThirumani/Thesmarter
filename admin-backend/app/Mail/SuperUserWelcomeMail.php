<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuperUserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $displayName;

    /** @var string */
    public $companyName;

    /** @var string */
    public $mobile;

    /** @var string|null Plain PIN; only for brand-new accounts */
    public $plainPin;

    /** @var string|null Executive mail subject override */
    public $subjectLine;

    public function __construct(string $displayName, string $companyName, string $mobile, ?string $plainPin, ?string $subjectLine = null)
    {
        $this->displayName = $displayName;
        $this->companyName = $companyName;
        $this->mobile = $mobile;
        $this->plainPin = $plainPin;
        $this->subjectLine = $subjectLine;
    }

    public function build()
    {
        $subject = $this->subjectLine !== null && $this->subjectLine !== ''
            ? $this->subjectLine
            : ('Welcome to '.$this->companyName);

        return $this
            ->subject($subject)
            ->view('mails.super_user_welcome');
    }
}
