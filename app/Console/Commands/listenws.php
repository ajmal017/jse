<?php

namespace App\Console\Commands;

use App\Classes\Trading\CandleMaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;


class listenws extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listenws';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'BTMX ratchet/pawl ws client console application';

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
     * @param CandleMaker $candleMaker
     */
    public function handle()
    {
        /**
         * Ratchet/pawl websocket library
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, ['dns' => '8.8.8.8', 'timeout' => 10]);

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
        \App\Classes\WebSocket\BitmexWsListener::subscribe($connector, $loop, $this, $candleMaker = new CandleMaker());

    }
}
