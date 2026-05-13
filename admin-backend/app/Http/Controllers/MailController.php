<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendGridTestMail;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendEmail()
    {
        $to_email = 'nizamuddin@tutorialspoint.com';
        Mail::to($to_email)->send(new SendGridTestMail());
        return 'Email sent successfully!';
    }
}
