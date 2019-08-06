<?php

namespace App\Console\Commands;

use App\Classes\Trading\LimitOrder;
use App\Job;
use App\Bot;
use App\Jobs\GetQueWorkerStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Mockery\Exception;

class LimitRest extends Command
{
    private $exchange;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limitrest {botId} {queId} {net}';

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
        $limitOrderObj = [
            'orderID' => null,
            'clOrdID' => 'abc-123-' . now(),
            'direction' => 'sell',
            'isLimitOrderPlaced' => false,
            'limitOrderPrice' => null,
            'limitOrderTimestamp' => null,
            'step' => 0 // Limit order position placement. Used for testing purpuses. If set - order will be locate deeper in the book.
        ];

        /* For firing subscription from demo to live. In LimitOrderWs.php */
        //Cache::put('status_bot_' . $this->argument('botId'), true, now()->addMinute(30));

        /**
         * Set cache object. It will be accesses from other classes and que workers.
         *
         * Contains settings and flags of a limit order.
         * These settings are read by other classes and que workers: market order que, amend order que, etc.
         * Once a flag is set, other classes can read it.
         * For example if an order is executed - we need to stop bid/ask order book subscription immediately.
         */
        //Cache::put('bot_' . $this->argument('botId'), $limitOrderObj, now()->addMinute(30));

        /**
         * Truncate signal table.
         * This table gets truncated on bot start/stop button click as well.
         */
        DB::table('signal_' . $this->argument('botId'))->truncate();

        /* Stop chart worker when console command is started */
        DB::table('bots')
            ->where('id', $this->argument('botId'))
            ->update([
                'status' => 'idle'
            ]);
        $this->exchange = new \ccxt\bitmex();
        $this->exchange->urls['api'] = $this->exchange->urls['api'];

        while (true){
         sleep(3);
         $this->listen();
        }
    }

    private function listen(){
        /* Update loop time stamp in bots table jse-274 */
        DB::table('bots')
            ->where('id', $this->argument('botId'))
            ->update([
                'execution_worker_update_time' => time()
            ]);

        GetQueWorkerStatus::dispatch($this->argument('botId'))->onQueue('bot_' . $this->argument('queId'));
        $accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($this->argument('botId'));
        $symbol = $accountSettingsObject['historySymbolName'];

        /* Exchange request */
        try{
            $orderBookMessage = $this->exchange->fetchOrderBook($accountSettingsObject['executionSymbolName'], 1);
        } catch (Exception $e)
        {
            dump($e);
        }


        /* Prepare an order book message. Make it the same format is websocket object */
        $message = [
            // $message['data'][0]['asks'][0][0]
            'table' => 'orderBook10',
            'action' => 'update',
            'data' => [
                [
                    'symbol' => $symbol,
                    'asks' => [
                        [
                            $orderBookMessage['bids'][0][0], $orderBookMessage['bids'][0][1]
                        ]
                    ],
                    'bids' => [
                        [
                            $orderBookMessage['asks'][0][0], $orderBookMessage['asks'][0][1]
                        ]
                    ],
                    'timestamp' => date("c", strtotime(now())) // 'datetime': '2017-07-05T18:47:14.692Z', // ISO8601 datetime string with milliseconds
                ]
            ]
        ];

        //dump($message);

        /* Order book parse */
        \App\Classes\Trading\Orders\LimitOrderMessage::parse($message, $this->argument('botId'), $this->argument('queId'), $this->exchange);

        echo now() .
            "Bot ID: " . $this->argument('botId') .
            " Que ID: " . $this->argument('queId') .
            " Symbol: " . $symbol .
            " Status: " . Bot::where('id', $this->argument('botId'))->value('status') .  "\n";
    }
}
