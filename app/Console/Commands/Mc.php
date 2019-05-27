<?php

namespace App\Console\Commands;

use App\Classes\Indicators\Ema;
use App\Classes\Indicators\Macd;
use App\Classes\Indicators\MacdSettings;
use App\Classes\Indicators\PriceChannel;
use App\Classes\Indicators\Sma;
use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use App\Classes\Trading\Exchange;
use App\Classes\Trading\MacdTradesTrigger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;
use Illuminate\Support\Facades\DB;

/**
 *
 * Class Mc
 * @package App\Console\Commands
 *
 * php artisan mc 3
 * MACD common setting: 12, 26, 9
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_convergence_divergence_macd
 *
 * 1. Indicator period MACD 1. 1st EMA
 * 2. MACD 2. 2nd EMA
 * 3. MACD 3. SMA period for MACD signal line
 */

class Mc extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mc {botInstance}';

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

        Macd::calculate($macdSettings = [
            'ema1Period' => $botSettings['strategyParams']['emaPeriod'],
            'ema2Period' => $botSettings['strategyParams']['macdLinePeriod'],
            'ema3Period' => $botSettings['strategyParams']['macdSignalLinePeriod']],
            $botSettings,
            true);
        $this->reloadChart($botSettings);

        \App\Classes\WebSocket\BitmexWsListener::subscribe(
            $connector, // For web socket
            $loop, // For web socket
            $this, // For colored messages in console
            $candleMaker = new CandleMaker('macd', $botSettings),
            //@todo PASS $botSettings ONLY! Other params are redundant!
            $chart = new MacdTradesTrigger($botSettings['executionSymbol'], $botSettings['volume'], $botSettings),
            $botSettings['historySymbol'],
            null, // null,$this->argument('period') // Price channel period null
            $macdSettings
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
