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
    public static $console;
    public static $symbol;
    private static $accountSettingsObject;
    private static $isBotRunning;
    private static $botId;
    private static $connection;

    public static function listen($connector, $loop, $console, $botId){

        self::$botId = $botId;
        /*$loop->addPeriodicTimer(1, function() use($connector, $loop, $console, $botId) {

            // Get settings object
            // Get strategies settings object
            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);
            self::$isBotRunning =  Cache::get('status_bot_' . $botId);

            if (Bot::where('id', $botId)->value('status') == 'running' && !self::$isBotRunning){
                dump('FIREEEEEEEEEEED');
                Cache::put('status_bot_' . $botId, true, now()->addMinute(30));
                self::listen($connector, $loop, $console, $botId);
            }

            if (Bot::where('id', $botId)->value('status') == 'idle' && self::$isBotRunning){
                dump('---------- got into idle');
                self::$isBotRunning = false;
                Cache::put('status_bot_' . $botId, false, now()->addMinute(30));
            }
        });*/

        $loop->addPeriodicTimer(1, function() use($connector, $loop, $console, $botId) {

            // Get settings object
            // Get strategies settings object
            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);
            self::$isBotRunning =  Cache::get('status_bot_' . $botId);
            self::$symbol = self::$accountSettingsObject['historySymbolName'];

            if (Bot::where('id', $botId)->value('status') == 'running' && !self::$isBotRunning){
                dump('FIREEEEEEEEEEED ' . self::$accountSettingsObject['historySymbolName']);
                Cache::put('status_bot_' . $botId, true, now()->addMinute(30));
                self::listen($connector, $loop, $console, $botId);
            }

            if (Bot::where('id', $botId)->value('status') == 'idle' && self::$isBotRunning){
                dump('---------- got into idle');
                self::$isBotRunning = false;
                Cache::put('status_bot_' . $botId, false, now()->addMinute(30));
            }
        });



        self::$console = $console;
        //self::$symbol = 'XBTUSD'; // XBTUSD ADAU19

        /** Pick up the right websocket endpoint accordingly to the exchange */
        if(self::$accountSettingsObject['isTestnet']){
            $exchangeWebSocketEndPoint = "wss://testnet.bitmex.com/realtime";
        } else {
            $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";
        }

        //$exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";

        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                self::$connection = $conn;
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    /**
                     * Parse all websocket messages.
                     */


                    if(array_key_exists('info', $jsonMessage))
                        dump($jsonMessage['docs']);

                    if(array_key_exists('table', $jsonMessage))
                        if($jsonMessage['table'] == 'orderBook10')
                            if($jsonMessage['data'][0]['symbol'] == self::$symbol)
                                \App\Classes\WebSocket\Front\LimitOrderMessage::parse($jsonMessage, self::$botId);
                                //echo now() . " " . $jsonMessage['data'][0]['symbol'] . "\n";
                                //echo '';

                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::$console->handle(); // Call the main method of this class
                });

                /**
                 * Auth signature. It is the same for all requests.
                 * You just auth the whole WS connection.
                 */

                /*$api = "ikeCK-6ZRWtItOkqvqo8F6wO"; // testnet
                $secret = "JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK";*/

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
                $requestObject5 = json_encode(
                    [
                        "op" => "subscribe",
                        "args" => ["orderBook10:XBTUSD","orderBook10:ETHUSD"]
                    ]
                );

                //$conn->send($requestObject2); /* Connection authenticate  */
                //$conn->send($requestObject3); /* Subscribe to order channel */
                //$conn->send($requestObject4); /* Subscribe to order executions */
                $conn->send($requestObject5); /* Subscribe to order book with a specific symbol */

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attempt
                //$this->handle(); // Call the main method of this class
                self::subscribe();
                //$loop->stop();
            });
        $loop->run();
    }
}