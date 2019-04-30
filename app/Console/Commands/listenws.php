<?php

namespace App\Console\Commands;

use App\Classes\Indicators\Ema;
use App\Classes\Indicators\Macd;
use App\Classes\Indicators\PriceChannel;
use App\Classes\Indicators\Sma;
use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use App\Classes\Trading\Exchange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;

/**
 * php artisan listenws XBTUSD BTC/USD 15 1
 * php artisan listenws ETHUSD ETH/USD 13 5
 * params:
 * 1. History symbol name
 * 2. Execution symbol name
 * 3. Order size
 * 4. Indicator period
 *
 * Class listenws
 * @package App\Console\Commands
 */

class listenws extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listenws {historySymbol}{orderSymbol}{orderVolume}{period}';

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

        PriceChannel::calculate($this->argument('period'));

        Sma::calculate('close',5, 'sma1');
        Sma::calculate('close',10, 'sma2');
        Ema::calculate('close', 5, 'sma1', 'ema1');
        Ema::calculate('close',10, 'sma2', 'ema2');
        Macd::calculate(12, 26, 9); // 12, 26, 9
        Sma::calculate('macd_line', 9, 'macd_signal_line');

        \App\Classes\WebSocket\BitmexWsListener::subscribe(
            $connector,
            $loop,
            $this, // For colored messages in console
            $candleMaker = new CandleMaker(),
            $chart = new Chart($this->argument('orderSymbol'), $this->argument('orderVolume')),
            $this->argument('historySymbol'),
            $this->argument('period') // Indicator period
        );
    }
}

// 1. Add a second command: listenws MACD
// 2. Create a copy of Chart.php class which is gonna react for MACD cross
// 3. This command will execute MACD indicator calc and it's own Chart.php class