<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UserNotificatonList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:list {appType} {user} {company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command return the list of notification for the  respective users';

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
        $company = $this->argument('company');
        $user = $this->argument('user');
        $type = $this->argument('appType');
        app()
            ->make('Illuminate\Http\Request')
            ->merge([
               'company_id'=>$company,
               'user_id'=>$user,
               'app_type'=>$type
            ]);
        $controller = app()->make('App\Http\Controllers\API\NotificationController');
        $data = app()->call([$controller, 'list']);
        print_r(json_encode($data));
    }
}
