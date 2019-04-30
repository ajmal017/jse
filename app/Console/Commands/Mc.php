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

/**
 *
 * Class Mc
 * @package App\Console\Commands
 *
 * php artisan mc XBTUSD BTC/USD 15 5 10 3
 *
 * MACD common setting: 12, 26, 9
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_convergence_divergence_macd
 *
 * 1. History symbol name
 * 2. Execution symbol name
 * 3. Order size (Contracts)
 * 4. Indicator period MACD 1. 1st EMA
 * 5. MACD 2. 2nd EMA
 * 6. MACD 3. SMA period for MACD signal line
 */

class Mc extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mc {historySymbol}{orderSymbol}{orderVolume}{ema1}{ema2}{ema3}';

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
        \App\Classes\Trading\History::loadPeriod($this->argument('historySymbol'));

        // MacdSettings::set($macdSettings = ['ema1Period' => $this->argument('ema1'), 'ema2Period' => $this->argument('ema2'),'ema3Period' => $this->argument('ema3')]);
        Macd::calculate($macdSettings = ['ema1Period' => $this->argument('ema1'), 'ema2Period' => $this->argument('ema2'),'ema3Period' => $this->argument('ema3')]);

        // Reload chart
        $pusherApiMessage = new \App\Classes\WebSocket\PusherApiMessage();
        $pusherApiMessage->clientId = 12345;
        $pusherApiMessage->messageType = 'reloadChartAfterHistoryLoaded';
        event(new \App\Events\jseevent($pusherApiMessage->toArray()));

        \App\Classes\WebSocket\BitmexWsListener::subscribe(
            $connector, // For web socket
            $loop, // For web socket
            $this, // For colored messages in console
            $candleMaker = new CandleMaker('macd'),
            // $chart = new Chart($this->argument('orderSymbol'), $this->argument('orderVolume')),
            $chart = new MacdTradesTrigger($this->argument('orderSymbol'), $this->argument('orderVolume')),
            $this->argument('historySymbol'),
            null, // null,$this->argument('period') // Price channel period null
            $macdSettings
        );
    }
}
