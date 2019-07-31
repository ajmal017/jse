<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 12:00 PM
 */

namespace App\Classes\WebSocket\Front;
use App\Jobs\GetQueWorkerStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Bot;


class LimitOrderWs
{
    private static $accountSettingsObject;
    private static $isBotRunning;
    public static $symbol;
    private static $connection; // Not used

    public static $connector;
    public static $loop;
    public static $console;
    private static $botId;
    private static $queId;
    private static $net;
    private static $orderBookMessage;
    private static $exchange;


    public static function listen($connector, $loop, $console, $botId, $queId, $net, $exchnage){

        /* Vars for self-call listen method in case of reconnection */
        self::$connector = $connector;
        self::$loop = $loop;
        self::$console = $console;
        self::$botId = $botId;
        self::$queId = $queId;
        self::$net = $net;
        self::$orderBookMessage = null;
        self::$exchange = $exchnage;

        /**
         * Check start stop status each x seconds.
         * Within the same period of time we send order book message for parsing.
         */
        $loop->addPeriodicTimer(2, function() use($connector, $loop, $console, $botId, $net) {
            /* Update loop time stamp in bots table jse-274 */
            DB::table('bots')
                ->where('id', $botId)
                ->update([
                    'execution_worker_update_time' => time()
                ]);

            GetQueWorkerStatus::dispatch($botId)->onQueue('bot_' . self::$queId);

            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);
            self::$symbol = self::$accountSettingsObject['historySymbolName'];

            /*if (Bot::where('id', $botId)->value('status') == 'running' && !self::$isBotRunning){
                dump('FIREEEEEEEEEEED ' . self::$accountSettingsObject['historySymbolName']);
                Cache::put('status_bot_' . $botId, true, now()->addMinute(30));
                self::listen($connector, $loop, $console, $botId, $net, self::$exchange);
            }

            if (Bot::where('id', $botId)->value('status') == 'idle' && self::$isBotRunning){
                dump('---------- got into idle');
                self::$isBotRunning = false;
                Cache::put('status_bot_' . $botId, false, now()->addMinute(30));
            }*/

            /* Orderbook parse */
            if (self::$orderBookMessage)
                \App\Classes\WebSocket\Front\LimitOrderMessage::parse(self::$orderBookMessage, self::$botId, self::$queId, self::$exchange);

            echo now() .
                "Bot ID: " . self::$botId .
                " Que ID: " . self::$queId .
                " Symbol: " . self::$orderBookMessage['data'][0]['symbol'] .
                " Status: " . Bot::where('id', $botId)->value('status') .  "\n";
        });

        /** Pick up the right websocket endpoint accordingly to the exchange */
        if($net == 'testnet'){
            $exchangeWebSocketEndPoint = "wss://testnet.bitmex.com/realtime";
        } else {
            $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";
        }

        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                self::$connection = $conn;
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);

                    /** Parse all websocket messages */
                    if(array_key_exists('info', $jsonMessage))
                        dump("Bitmex end point: " . $jsonMessage['docs']);

                    if(array_key_exists('table', $jsonMessage))
                        if($jsonMessage['table'] == 'orderBook10')
                            if($jsonMessage['data'][0]['symbol'] == self::$symbol)
                                self::$orderBookMessage = $jsonMessage;
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {

                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::listen(self::$connector, self::$loop, self::$console, self::$botId, self::$queId, self::$net, self::$exchange);
                });

                /**
                 * Auth signature. It is the same for all requests.
                 * You just auth the whole WS connection.
                 * WS auth connection working example.
                 */

                $api = "ct5AF7LcE3bsfz4gR5yTfvBq";
                $secret = "Zy9UDdTGojC_T6RE2JjOY0N2F4EhQXqBxo92DSxU1_f0pXLg";

                $expires = (time() + 86400); // 10 digits
                $signature = hash_hmac('sha256', 'GET/realtime'. $expires, $secret);

                /**
                 * Prepare WS request subscription objects.
                 * @link https://www.bitmex.com/app/wsAPI
                 * @todo 01.07.19 Make and array and foreach it
                 */
                $requestObject2 = json_encode(
                    [
                        "op" => "authKeyExpires",
                        "args" => [$api, $expires, $signature]
                    ]
                );
                $requestObject3 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["order"]
                    ]
                );

                $requestObject4 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["execution"]
                    ]
                );

                /* Subscribe to order book */
                $requestObject6 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["orderBook10:XBTUSD","orderBook10:ETHUSD"]
                    ]
                );

                //$conn->send($requestObject2); /* Connection authenticate  */
                //$conn->send($requestObject3); /* Subscribe to order channel */
                //$conn->send($requestObject4); /* Subscribe to order executions */

                if ($conn){
                    $conn->send($requestObject6); /* Subscribe to order book with a specific symbol */
                } else {
                    dump('$conn is not created. Can not send WS obj. ' . __FILE__ . ' ' . __LINE__);
                }

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attempt
                //self::listen(self::$connector, self::$loop, self::$console, self::$botId, self::$net, self::$exchange); // Call the main method of this class
                $loop->stop();
            });

        $loop->run();
    }
}