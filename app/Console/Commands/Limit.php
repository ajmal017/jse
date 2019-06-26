<?php

namespace App\Console\Commands;

use App\Classes\Trading\LimitOrder;
use App\Job;
use App\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Limit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limit';

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
         * Call LimitOrder.php and start limit orders
         */
        $limitOrder = new LimitOrder();
        $limitOrder->start();


        /*$exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['api'];
        dump($exchange->urls);
        $exchange->apiKey = 'AdpGKvlnElQmowv-SgKu9kiF'; // testnet
        $exchange->secret = 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1'; //testnet
        $response = $exchange->createMarketBuyOrder('BTC/USD', 1, []);
        dump($response);*/

        /*$exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['test'];
        $exchange->apiKey = 'ikeCK-6ZRWtItOkqvqo8F6wO'; // testnet
        $exchange->secret = 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK'; //testnet jesse*/
    }
}
