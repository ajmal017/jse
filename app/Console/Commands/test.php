<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        //dump(\ccxt\Exchange::$exchanges);

        $exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['api'];
        dump($exchange->urls);
        $exchange->apiKey = 'AdpGKvlnElQmowv-SgKu9kiF'; // testnet
        $exchange->secret = 'KrcRtZ8SfAx_4xOSEm1DHon1gPF2wcSHPVZkyJ7SmOmCX0j1'; //testnet
        $response = $exchange->createMarketBuyOrder('BTC/USD', 1, []);
        dump($response);


    }
}
