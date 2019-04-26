<?php

namespace App\Console\Commands;

use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use App\Classes\Trading\Exchange;
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
    protected $signature = 'listenws {historySymbol}{orderSymbol}{period}';

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

        // History: XBTUSD, Order: BTC/USD
        // History: ETHUSD, Order: ETH/USD

        \App\Classes\Trading\History::loadPeriod($this->argument('historySymbol'));
        \App\Classes\Indicators\PriceChannel::calculate($this->argument('period'));
        \App\Classes\WebSocket\BitmexWsListener::subscribe(
            $connector,
            $loop,
            $this,
            $candleMaker = new CandleMaker(),
            $chart = new Chart($this->argument('orderSymbol')),
            $this->argument('historySymbol'),
            $this->argument('period') // Indicator period
        );
    }
}
