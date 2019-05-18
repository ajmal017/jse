<?php

namespace App\Console\Commands;

use App\Classes\Indicators\Ema;
use App\Classes\Indicators\Macd;
use App\Classes\Indicators\PriceChannel;
use App\Classes\Indicators\Sma;
use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use App\Classes\Trading\Exchange;
use App\Jobs\PlaceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Mockery\Exception;
use Ratchet\Client\WebSocket;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Providers\Auth\Illuminate;

/**
 * php artisan pc XBTUSD BTC/USD 15 1
 * php artisan pc ETHUSD ETH/USD 13 5
 * params:
 * 1. History symbol name
 * 2. Execution symbol name
 * 3. Order size (Contracts)
 * 4. Indicator period (Bars. One bar can be any time frame 1m, 5m, 1h, etc.)
 *
 * Class listenws
 * @package App\Console\Commands
 */

class Pc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pc {botInstance}';

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
        DB::table('jobs')->truncate();
        $botSettings = config('bot.bots')[$this->argument('botInstance')];
        $migration = new \App\Classes\DB\TradingTable($botSettings['botTitle']);
        $migration->down();
        $migration->up();

        /**
         * Ratchet/pawl websocket library
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, ['dns' => '8.8.8.8', 'timeout' => 10]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
        \App\Classes\Trading\History::loadPeriod($botSettings);

        /* Initial indicators calculation and chart reload*/
        PriceChannel::calculate($botSettings['strategyParams']['priceChannelPeriod'], $botSettings['botTitle']);
        Sma::calculate('close', 2, 'sma1', $botSettings['botTitle']);
        $this->reloadChart($botSettings);

        \App\Classes\WebSocket\BitmexWsListener::subscribe(
            $connector,
            $loop,
            $this, // For colored messages in console
            $candleMaker = new CandleMaker('priceChannel', $botSettings),
            //$chart = new Chart($this->argument('orderSymbol'), $this->argument('orderVolume')),
            $chart = new Chart($botSettings['executionSymbol'], $botSettings['volume'], $botSettings),
            $botSettings['historySymbol'],
            $botSettings['strategyParams']['priceChannelPeriod'],
            null
        );
    }

    private function reloadChart ($botSettings){
        $pusherApiMessage = new \App\Classes\WebSocket\PusherApiMessage();
        $pusherApiMessage->clientId = $botSettings['frontEndId'];
        $pusherApiMessage->messageType = 'reloadChartAfterHistoryLoaded';
        try{
            event(new \App\Events\jseevent($pusherApiMessage->toArray()));
        } catch (\Exception $e)
        {
            echo __FILE__ . " " . __LINE__ . "\n";
            dump($e);
        }
    }
}
