<?php

namespace App\Console\Commands;

use App\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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

        dump(\App\Classes\WebSocket\Front\Strategies::getSettings(4)['macd']);
        die();

        $botSettings =
        [
            'botTitle' => 'bot_1',
            'executionSymbol' => 'BTC/USD',
            'historySymbol' => 'XBTUSD',
            'timeFrame' => 1, // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
            'barsToLoad' => 40,
            'api_path' => 1,
            'api_key' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
            'secret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK'
        ];
        // \App\Classes\Trading\History::loadPeriod($botSettings);
        // die();

        //dump(\ccxt\Exchange::$exchanges); // Show all available exchanges. Works good.

        /*$exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['api'];
        dump($exchange->urls);
        $exchange->apiKey = 'AdpGKvlnElQmowv-SgKu9kiF'; // testnet
        $exchange->secret = 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1'; //testnet
        $response = $exchange->createMarketBuyOrder('BTC/USD', 1, []);
        dump($response);*/

        $exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['test'];
        $exchange->apiKey = 'ikeCK-6ZRWtItOkqvqo8F6wO'; // testnet
        $exchange->secret = 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK'; //testnet jesse

         \App\Jobs\PlaceOrder::dispatch('buy', 'BTC/USD2', 1, $botSettings);

        //$response = $exchange->createMarketSellOrder('BTC/USD', 1, []);
        //dump($response);



    }
}
