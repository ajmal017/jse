<?php

namespace App\Console\Commands;

use App\Bot;
use App\Jobs\GetQueWorkerStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;
use Illuminate\Support\Facades\Log;

/**
 *
 * REST version of the front worker.
 * This version is a replacement for websocket version.
 *
 * Class LimitRest
 * @package App\Console\Commands
 */
class LimitRest extends Command
{
    private $exchange;
    private $orderBookMessage = null;

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
    protected $description = 'limitrest {botId} {queId} {net}. new: live/testnet';

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
        $this->exchange->timeout = 30000; // 30 seconds. https://github.com/ccxt/ccxt/wiki/Manual#exchange-properties

        while (true){
         sleep(15);
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

        /* Handle exception https://github.com/ccxt/ccxt/wiki/Manual#error-handling */
        try {
            $this->orderBookMessage = $this->exchange->fetchOrderBook($accountSettingsObject['executionSymbolName'], 1);
        } catch (\ccxt\NetworkError $e) {
            $error = 'Request failed due to a network error: ' . $e->getMessage () . "\n";
            echo $error;
            Log::notice($error);
        } catch (\ccxt\ExchangeError $e) {
            $error = 'Request failed due to exchange error: ' . $e->getMessage () . "\n";
            echo $error;
            Log::notice($error);
        } catch (Exception $e) {
            $error = 'Request failed with: ' . $e->getMessage () . "\n";
            echo $error;
            Log::notice($error);
        }

        /**
         * Prepare an order book message. Make it the same format is websocket object.
         * We check the type. If it is text instead of array - it means that an error was thrown.
         * https://dacoders.myjetbrains.com/youtrack/issue/JSE-289
         */
        if($this->orderBookMessage)
            if(gettype($this->orderBookMessage) == 'array'){
                $message = [
                    'table' => 'orderBook10',
                    'action' => 'update',
                    'data' => [
                        [
                            'symbol' => $symbol,
                            'asks' => [
                                [
                                    $this->orderBookMessage['bids'][0][0], $this->orderBookMessage['bids'][0][1]
                                ]
                            ],
                            'bids' => [
                                [
                                    $this->orderBookMessage['asks'][0][0], $this->orderBookMessage['asks'][0][1]
                                ]
                            ],
                            'timestamp' => date("c", strtotime(now())) // 'datetime': '2017-07-05T18:47:14.692Z', // ISO8601 datetime string with milliseconds
                        ]
                    ]
                ];

                /* Order book parse */
                \App\Classes\Trading\Orders\LimitOrderMessage::parse($message, $this->argument('botId'), $this->argument('queId'), $this->exchange);
            } else {
                $errorMessage = 'Didn not get $this->exchange->fetchOrderBook ot the response is not array';
                dump(__FILE__ . $errorMessage);
                Log::error($errorMessage);
            }

        echo "LimitRest.php " . now() .
            " Bot ID: " . $this->argument('botId') .
            " Que ID: " . $this->argument('queId') .
            " Symbol: " . $symbol .
            " Status: " . Bot::where('id', $this->argument('botId'))->value('status') .  "\n";
    }
}
