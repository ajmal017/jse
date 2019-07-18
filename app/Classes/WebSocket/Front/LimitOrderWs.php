<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/26/2019
 * Time: 12:00 PM
 */

namespace App\Classes\WebSocket\Front;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    private static $net;


    public static function listen($connector, $loop, $console, $botId, $net){

        /* Vars for self-call listen method in case of recconection */
        self::$connector = $connector;
        self::$loop = $loop;
        self::$console = $console;
        self::$botId = $botId;
        self::$net = $net;

        $loop->addPeriodicTimer(1, function() use($connector, $loop, $console, $botId, $net) {
            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);
            self::$isBotRunning =  Cache::get('status_bot_' . $botId);
            self::$symbol = self::$accountSettingsObject['historySymbolName'];

            if (Bot::where('id', $botId)->value('status') == 'running' && !self::$isBotRunning){
                dump('FIREEEEEEEEEEED ' . self::$accountSettingsObject['historySymbolName']);
                Cache::put('status_bot_' . $botId, true, now()->addMinute(30));
                self::listen($connector, $loop, $console, $botId, $net);
            }

            if (Bot::where('id', $botId)->value('status') == 'idle' && self::$isBotRunning){
                dump('---------- got into idle');
                self::$isBotRunning = false;
                Cache::put('status_bot_' . $botId, false, now()->addMinute(30));
            }
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
                                \App\Classes\WebSocket\Front\LimitOrderMessage::parse($jsonMessage, self::$botId);
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {

                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt

                    //self::$console->handle(); // Call the main method of this class. It calls the first console command! Not this class!
                    self::listen(self::$connector, self::$loop, self::$console, self::$botId, self::$net);
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
                $conn->send($requestObject6); /* Subscribe to order book with a specific symbol */

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attempt
                self::listen(self::$connector, self::$loop, self::$console, self::$botId, self::$net); // Call the main method of this class
            });

        $loop->run();
    }
}