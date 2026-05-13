<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuthenticationOtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:otp {phoneNo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command is used to send one time password to authentication a user mobile number';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $phoneNo = $this->argument('phoneNo');
        app()
        ->make('Illuminate\Http\Request')
        ->merge([
           'phoneno'=>$phoneNo,
        ]);
        $controller = app()->make('App\Http\Controllers\API\NotificationController');
        app()->call([$controller, 'sendOtp']);
    }
}
