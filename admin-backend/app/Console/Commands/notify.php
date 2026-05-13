<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Request;

class notify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:normal {event} {reference} {appType}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This comman is used to send notificationt users via different channels';

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
        $event = $this->argument('event');
        $reference = $this->argument('reference');
        $type = $this->argument('appType');
        app()
            ->make('Illuminate\Http\Request')
            ->merge([
               'event'=>$event,
               'referenceId'=>$reference,
               'type'=>$type
            ]);
        $controller = app()->make('App\Http\Controllers\API\NotificationController');
        app()->call([$controller, 'sendNormalNotification']);
       
    }
}
