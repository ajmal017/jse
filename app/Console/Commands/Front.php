<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
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
        /**
         * Set bot's instance status to idle (stop the bot)
         * @tofo update Bots table once the status is set to idle
         */
        \App\Bot::where('id', $this->argument('botId'))->update(['status' => 'idle']);

        /**
         * Websocket connection
         * Ratchet/pawl websocket library
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, ['dns' => '8.8.8.8', 'timeout' => 10]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        /**
         * Subscribe to quotes, calculate indicators, start trading, etc.
         */
        \App\Classes\WebSocket\BitmexWsListenerFront::subscribe($connector, $loop, $this, $this->argument('botId'));
    }
}
