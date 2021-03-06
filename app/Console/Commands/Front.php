<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 13.09.19 New version of the subscriber without web socket
 *
 * For front end use.
 * Created command is controlled through the front end.
 * All parameters are read from the DB.
 *
 * Class Front
 * @package App\Console\Commands
 */
class Front extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'front {botId}';

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
        Log::debug("Front worker started. Bot id: " . $this->argument('botId'));

        /**
         * Set bot's instance status to idle (stop the bot)
         * @todo update Bots table in the fron end (send a WS event) once the status is set to idle
         */
        \App\Bot::where('id', $this->argument('botId'))->update(['status' => 'idle']);

        /**
         * Subscribe to quotes, calculate indicators, start trading, etc.
         */
        \App\Classes\Listeners\FrontListener::subscribe($this, $this->argument('botId'));
    }
}
