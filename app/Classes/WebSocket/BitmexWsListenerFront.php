<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/17/2019
 * Time: 9:15 PM
 */

namespace App\Classes\WebSocket;
use App\Bot;
use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use App\Symbol;
use App\Account;
use App\Exchange;

class BitmexWsListenerFront
{
    public static $console;
    public static $candleMaker;
    public static $chart;
    private static $symbol;
    private static $priceChannelPeriod;
    private static $macdSettings;

    private static $connection;
    private static $isHistoryLoaded = true;
    private static $isUnsubscribed = false;
    private static $botId;
    private static $execution_symbol_name;
    private static $live_api_path;
    private static $testnet_api_path;
    private static $api;
    private static $api_secret;

    // $candleMaker, $chart, $symbol, $priceChannelPeriod, $macdSettings
    public static function subscribe($connector, $loop, $console, $botId){

        // FOR TESTING ONLY:
        //$botId = 1;

        self::$console = $console;
        //self::$symbol = $symbol;
        self::$priceChannelPeriod = 1;
        //self::$macdSettings = $macdSettings;
        self::$botId = $botId;

        $self = get_called_class(); // For static methods call inside an anonymous function




        self::$candleMaker = new \App\Classes\Trading\CandleMaker(
            'priceChannel',
            [
                'botTitle' => Bot::where('id', self::$botId)->value('db_table_name'),
                'bitmex_api_path' => 'test',
                'frontEndId' => Bot::where('id', self::$botId)->value('front_end_id'),
                'rateLimit' => Bot::where('id', self::$botId)->value('rate_limit'),
                'executionSymbol' => self::$execution_symbol_name,
                'timeFrame' => Bot::where('id', self::$botId)->value('time_frame')
            ]);

        self::$chart = new \App\Classes\Trading\Chart(
            self::$execution_symbol_name,
            Bot::where('id', self::$botId)->value('volume'),
            [
                'botTitle' => Bot::where('id', self::$botId)->value('db_table_name'),
                'volume' => Bot::where('id', self::$botId)->value('volume'),
                'commission' => 0.0750,
                'bitmex_api_path' => 'test',
                'bitmex_api_key' => self::$api,
                'api_api_secret' => self::$api_secret
            ]);




        $loop->addPeriodicTimer(1, function() use($loop, $botId, $self) {

            /*// Get account_id from Bot
            $account_id = Bot::where('id', $botId)->value('account_id');
            // Get exchnage_id from Account
            $exchange_id = Account::where('id', $account_id)->value('exchange_id');
            self::$api = Account::where('id', $account_id)->value('api');
            self::$api_secret = Account::where('id', $account_id)->value('api_secret');
            // Get live api path from Exchnage
            self::$live_api_path = Exchange::where('id', $exchange_id)->value('live_api_path');
            self::$testnet_api_path = Exchange::where('id', $exchange_id)->value('testnet_api_path');
            // Get execution_symbol_name
            // Get history_symbol_name
            self::$execution_symbol_name = Symbol::where('exchange_id', $exchange_id)->value('execution_symbol_name');
            $history_symbol_name = Symbol::where('exchange_id', $exchange_id)->value('history_symbol_name');*/

            // Get account_id from Bot
            $account_id = $botId;
            // Get exchnage_id from Account
            $exchange_id = $botId;
            self::$api = Account::where('id', $account_id)->value('api');
            self::$api_secret = Account::where('id', $account_id)->value('api_secret');
            // Get live api path from Exchnage
            self::$live_api_path = Exchange::where('id', $exchange_id)->value('live_api_path');
            self::$testnet_api_path = Exchange::where('id', $exchange_id)->value('testnet_api_path');
            // Get execution_symbol_name
            // Get history_symbol_name
            self::$execution_symbol_name = Symbol::where('id', $exchange_id)->value('execution_symbol_name');
            $history_symbol_name = Symbol::where('id', $exchange_id)->value('history_symbol_name');

            /*dump($botId);
            dump($account_id);
            dump(self::$live_api_path);
            dump(self::$testnet_api_path);
            dump(self::$execution_symbol_name);
            dump($history_symbol_name);*/

            echo (Bot::where('id', $botId)->value('status') == 'running' ? 'running' : 'idle') . "\n";






            // If status == running -> get history and subscribe to symbol
            if (Bot::where('id', $botId)->value('status') == 'running'){

                if (self::$isHistoryLoaded){
                    \App\Classes\Trading\History::loadPeriod([
                        'botTitle' => Bot::where('id', $botId)->value('db_table_name'),
                        'barsToLoad' => Bot::where('id', $botId)->value('bars_to_load'),
                        'timeFrame' => Bot::where('id', $botId)->value('time_frame'),
                        'historySymbol' => $history_symbol_name
                    ]);
                    dump('history loaded');
                    dump($history_symbol_name);



                    /* Initial indicators calculation and chart reload*/
                    \App\Classes\Indicators\PriceChannel::calculate(self::$priceChannelPeriod, Bot::where('id', $botId)->value('db_table_name'), true);
                    \App\Classes\Indicators\Sma::calculate('close', 2, 'sma1', Bot::where('id', $botId)->value('db_table_name'), true);

                    /* Reload chart */
                    $self::reloadChart(['frontEndId' => Bot::where('id', $botId)->value('front_end_id')]);


                    // SUBSCRIPTION GOES HERE
                    /* Manual SUbscription object */
                    // @todo exclude this object to a separte method
                    $requestObject = json_encode([
                        "op" => "subscribe",
                        "args" => "instrument:" . $history_symbol_name
                    ]);
                    self::$connection->send($requestObject);

                    self::$isHistoryLoaded = false;
                    self::$isUnsubscribed = true;
                }

            }

            // If status == idle -> stop the loop
            if (Bot::where('id', $botId)->value('status') == 'idle'){
                dump('the bot is idle/STOPPED');
                // reset history flag
                self::$isHistoryLoaded = true;


                if(self::$isUnsubscribed){
                    /* Manual UNsubscription object */
                    // @todo exclude this object to a separte method
                    $requestObject = json_encode([
                        "op" => "unsubscribe",
                        "args" => "instrument:" . $history_symbol_name
                    ]);
                    self::$connection->send($requestObject);

                    // Unsubscribed. Then do nothing.
                    // Wait for the next bot start

                    self::$isUnsubscribed = false;
                }
            }

        });

        /** Pick up the right websocket endpoint accordingly to the exchange */
        $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";
        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                self::$connection = $conn;
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);

                    if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            //dump($jsonMessage);
                            \App\Classes\WebSocket\ConsoleWebSocket::messageParse(
                                $jsonMessage,
                                self::$console,
                                self::$candleMaker,
                                self::$chart,
                                self::$priceChannelPeriod,
                                []);
                        }
                    }
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    self::$console->info("Connection closed. " . __LINE__);
                    self::$console->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    self::$console->handle(); // Call the main method of this class
                });

                /* Manual subscription object */
                // Subscription will be moved to start function
                $requestObject = json_encode([
                    "op" => "subscribe",
                    "args" => ["instrument:XBTUSD"] // ["instrument:XBTUSD", "instrument:ETHUSD"]
                ]);
                //$conn->send($requestObject);

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

    private static function reloadChart ($botSettings){
        $pusherApiMessage = new \App\Classes\WebSocket\PusherApiMessage();
        $pusherApiMessage->clientId = $botSettings['frontEndId'];
        $pusherApiMessage->messageType = 'reloadChartAfterHistoryLoaded';
        try{
            event(new \App\Events\jseevent($pusherApiMessage->toArray()));
        } catch (\Exception $e)
        {
            echo __FILE__ . " " . __LINE__ . "\n";
            dump($e);
        }
    }
}