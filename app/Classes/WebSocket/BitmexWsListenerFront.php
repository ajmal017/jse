<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/17/2019
 * Time: 9:15 PM
 */

namespace App\Classes\WebSocket;
use App\Bot;
use App\Symbol;

class BitmexWsListenerFront
{
    public static $console;
    public static $candleMaker;
    public static $chart;
    private static $symbol;
    private static $priceChannelPeriod;
    private static $macdSettings;

    private static $isHistoryLoaded = false;

    // $candleMaker, $chart, $symbol, $priceChannelPeriod, $macdSettings
    public static function subscribe($connector, $loop, $console, $botInstance){

        self::$console = $console;
        /*self::$candleMaker = $candleMaker;
        self::$chart = $chart;
        self::$symbol = $symbol;
        self::$priceChannelPeriod = $priceChannelPeriod;
        self::$macdSettings = $macdSettings;*/
        $self = get_called_class();


        $loop->addPeriodicTimer(1, function() use($loop, $botInstance, $self) {

            dump(self::$isHistoryLoaded);

            echo Bot::where('id', $botInstance)->value('status') . "\n";

            // If status == running -> get history and subscribe to symbol
            if (Bot::where('id', $botInstance)->value('status') == 'running' && self::$isHistoryLoaded){
                \App\Classes\Trading\History::loadPeriod([
                    'botTitle' => Bot::where('id', $botInstance)->value('db_table_name'),
                    'barsToLoad' => Bot::where('id', $botInstance)->value('bars_to_load'),
                    'timeFrame' => Bot::where('id', $botInstance)->value('time_frame'),

                    'historySymbol' => Symbol::
                    where('id',
                        Bot::where('id', $botInstance)->value('symbol_id'))
                        ->value('history_symbol_name')
                ]);
                dump('history loaded');
                dump(Symbol::
                where('id',
                    Bot::where('id', $botInstance)->value('symbol_id'))
                    ->value('history_symbol_name'));
                self::$isHistoryLoaded = false;
                $self::reloadChart(['frontEndId' => Bot::where('id', $botInstance)->value('front_end_id')]);

            }

            // If status == idle -> stop the loop
            if (Bot::where('id', $botInstance)->value('status') == 'idle'){
                dump('the bot is idle/STOPPED');
                // reset history flag
                self::$isHistoryLoaded = true;
            }

        });

        /** Pick up the right websocket endpoint accordingly to the exchange */
        $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";
        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    // Event fire. Sent to Chart.vue
                    if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            dump($jsonMessage);
                            //\App\Classes\WebSocket\ConsoleWebSocket::messageParse($jsonMessage, self::$console, self::$candleMaker, self::$chart, self::$priceChannelPeriod, self::$macdSettings);
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
                $requestObject = json_encode([
                    "op" => "subscribe",
                    "args" => ["instrument:" . self::$symbol] // ["instrument:XBTUSD", "instrument:ETHUSD"]
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