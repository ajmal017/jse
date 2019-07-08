<?php

namespace App\Console\Commands;

use App\Job;
use App\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

        $exchange = new \ccxt\bitmex();
        $exchange->urls['api'] = $exchange->urls['test'];
        $exchange->apiKey = 'ikeCK-6ZRWtItOkqvqo8F6wO'; // testnet
        $exchange->secret = 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK'; //testnet jesse
        //$response = $exchange->createLimitBuyOrder('BTC/USD', 1, 8000, array('clOrdID' => 'abc-123'));
        //$response = $exchange->fetchTicker('BTC/USD');
        //dump($response['info']['tickSize']); // Tick size. Works good
        //$response = $exchange->privatePutOrder(array('orderID' => Cache::get('bot_1')['orderID'], 'price' => 15000)); // Works good
        //$response = $exchange->fetchMyTrades('BTC/USD', $exchange->milliseconds()-86400000); // Works good
        $response = $exchange->privateGetExecutionTradeHistory(array('reverse' => true, 'count' => 5)); // Works good!
        $response = $exchange->privateGetExecutionTradeHistory(array('count' => 5, 'filter' => ['orderID' => 'e1524040-0678-c66a-a33c-744fe7a5cf12_'])); // Works GOOD!
        dump($response);
        die();


        /*$bitGit = new \App\Classes\Temp\BitmexGit('ikeCK-6ZRWtItOkqvqo8F6wO', 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK');
        //dump($bitGit);
        dump($bitGit->createOrder("Limit","Sell",50000,1000));
        die();
        //$signature = hash_hmac('sha256', $verb . $path . $expires, $secret);
        $signature = hash_hmac('sha256', 'GET/api/v1/instrument1518064236', $secret); // Works good. No dectohex needed
        echo ($signature) . "\n";
        dump($expires);
        die();*/




        $serializer = new \App\Classes\System\SerializeExtention();
        $payload = json_decode(Job::where('id', 1)->value('payload'));
        $finishArray = $serializer->toArray($payload);

        $command = unserialize($payload->data->command);
        $finishArray['data'] = $serializer->toArray($command);
        $finishArray['data']['chained'] = null;

        dump(json_encode($finishArray));
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
