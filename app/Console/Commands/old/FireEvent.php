<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FireEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        dump('Fire event');

        try{
            event(new \App\Events\jseevent([
                'clientId' => 12345,
                'messageType' => 'nastyaEvent',
                'payload' => [
                    'status' => 'unread',
                    'date' => '2016-05-03',
                    'subject' => 'Tom',
                    'text' => 'Notifications from the server. Fired from the command line.'
                ]
            ]));
        } catch (\Exception $e)
        {
            echo __FILE__ . " " . __LINE__ . "\n";
            dump($e);
        }
    }
}
