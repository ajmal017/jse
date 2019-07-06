<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/17/2019
 * Time: 9:15 PM
 */

namespace App\Classes\WebSocket\Front;
use App\Bot;
use Illuminate\Support\Facades\DB;

class BitmexWsListenerFront
{
    public static $console;
    public static $candleMaker;
    public static $chart;
    private static $connection;
    private static $isHistoryLoaded = true;
    private static $isUnsubscribed = false;
    private static $botId;
    private static $isCreateClasses = true;
    private static $strategiesSettingsObject;
    private static $accountSettingsObject;

    public static function subscribe($connector, $loop, $console, $botId){
        self::$console = $console;
        self::$botId = $botId;

        /* For static methods call inside an anonymous function */
        $self = get_called_class();

        /* Endless loop. Executes once per second */
        $loop->addPeriodicTimer(1, function() use($loop, $botId, $self) {

            echo (Bot::where('id', $botId)->value('status') == 'running' ? 'running' : 'idle') . "\n";

            /* Get strategies settings object*/
            self::$strategiesSettingsObject = \App\Classes\WebSocket\Front\Strategies::getSettings($botId);
            /* Get account settings object */
            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);
            self::trace();

            /* Create Chart and Candle maker classes here. ONCE! Create again after STOP! */
            if (self::$isCreateClasses) {

                self::$candleMaker = new \App\Classes\Trading\CandleMaker(
                    (array_key_exists('priceChannel', self::$strategiesSettingsObject) ? 'priceChannel' : 'macd'),
                    self::$accountSettingsObject);

                dump (__FILE__);
                dump((array_key_exists('priceChannel', self::$strategiesSettingsObject) ? "!!!!!!! PC" : "!!!!!!!!!! MACD"));

                (array_key_exists('priceChannel', self::$strategiesSettingsObject) ?
                    self::$chart = new \App\Classes\Trading\Chart(self::$accountSettingsObject) :
                    self::$chart = new \App\Classes\Trading\MacdTradesTrigger(
                        self::$accountSettingsObject['executionSymbolName'],
                        self::$accountSettingsObject
                    ));

                self::$isCreateClasses = false;
            }

            /* Start the bot */
            if (Bot::where('id', $botId)->value('status') == 'running'){
                if(array_key_exists('priceChannel', self::$strategiesSettingsObject)) self::startPriceChannelBot($botId);
                if(array_key_exists('macd', self::$strategiesSettingsObject)) self::startMacdBot($botId);
            }

            /* Stop the bot */
            if (Bot::where('id', $botId)->value('status') == 'idle'){
                if(array_key_exists('priceChannel', self::$strategiesSettingsObject)) self::stopPriceChannelBot($botId);
                if(array_key_exists('macd', self::$strategiesSettingsObject)) self::stopMacdBot($botId);
            }
        });

        /**
         * Pick up the right websocket endpoint accordingly to the exchange
         */

        // HERE IT IS!
        $exchangeWebSocketEndPoint = "wss://www.bitmex.com/realtime";


        $connector($exchangeWebSocketEndPoint, [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                self::$connection = $conn;
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {
                    $jsonMessage = json_decode($socketMessage->getPayload(), true);
                    if (array_key_exists('data', $jsonMessage)){
                        if (array_key_exists('lastPrice', $jsonMessage['data'][0])){
                            self::messageParse($jsonMessage);
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

            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection attempt
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

    private static function trace(){
        /* Trace: */
        // dump(self::$accountSettingsObject);
        dump(self::$strategiesSettingsObject);
    }

    private static function startPriceChannelBot($botId){
        if (self::$isHistoryLoaded){

            /* DELETE IT FROM HERE! TESTING ONLY! */
            DB::table('signal_1')->truncate();


            \App\Classes\Trading\History::loadPeriod(self::$accountSettingsObject);
            dump('History loaded (Price Channel)');
            /* Initial indicators calculation */
            \App\Classes\Indicators\PriceChannel::calculate(
                self::$strategiesSettingsObject['priceChannel']['priceChannelPeriod'],
                Bot::where('id', $botId)->value('db_table_name'),
                true);
            \App\Classes\Indicators\Sma::calculate(
                'close',
                self::$strategiesSettingsObject['priceChannel']['smaFilterPeriod'],
                'sma1',
                Bot::where('id', $botId)->value('db_table_name'),
                true);

            /* Reload chart */
            self::reloadChart(['frontEndId' => self::$accountSettingsObject['frontEndId']]);

            /* Manual subscription object */
            $requestObject = json_encode([
                "op" => "subscribe",
                "args" => "instrument:" . self::$accountSettingsObject['historySymbolName']
            ]);
            self::$connection->send($requestObject);
            self::$isHistoryLoaded = false;
            self::$isUnsubscribed = true;
        }
    }

    private static function stopPriceChannelBot(){
        /**
         * Refresh settings.
         * New settings are loaded on stop. On play they picked up again
         * JSE-117. Trade flag doesn't reset on stop
         */
        self::$chart->trade_flag = 'all';
        /* JSE-165 */
        self::$candleMaker->indicator = 'priceChannel';
        self::$chart->botSettings = self::$accountSettingsObject;
        /* reset history flag */
        self::$isHistoryLoaded = true;
        self::$isCreateClasses = true; // Chart and CandleMaker will be freshly created

        if(self::$isUnsubscribed){
            /* Manual UNsubscription object */
            $requestObject = json_encode([
                "op" => "unsubscribe",
                "args" => "instrument:" . self::$accountSettingsObject['historySymbolName']
            ]);
            self::$connection->send($requestObject);
            /* Unsubscribed. Then do nothing. Wait for the next bot start */
            self::$isUnsubscribed = false;
        }
    }

    private static function startMacdBot(){
        if (self::$isHistoryLoaded){

            /* DELETE IT FROM HERE! TESTING ONLY! */
            DB::table('signal_1')->truncate();

            \App\Classes\Trading\History::loadPeriod(self::$accountSettingsObject);
            dump('History loaded (MACD)');

            \App\Classes\Indicators\Macd::calculate($macdSettings = [
                'ema1Period' => self::$strategiesSettingsObject['macd']['emaPeriod'],
                'ema2Period' => self::$strategiesSettingsObject['macd']['macdLinePeriod'],
                'ema3Period' => self::$strategiesSettingsObject['macd']['macdSignalLinePeriod']],
                self::$strategiesSettingsObject,
                true);

            /* Reload chart */
            self::reloadChart(['frontEndId' => self::$accountSettingsObject['frontEndId']]);

            /* Manual subscription object */
            $requestObject = json_encode([
                "op" => "subscribe",
                "args" => "instrument:" . self::$accountSettingsObject['historySymbolName']
            ]);
            self::$connection->send($requestObject);
            self::$isHistoryLoaded = false;
            self::$isUnsubscribed = true;
        }
    }

    private static function stopMacdBot(){
        
        self::$chart->botSettings = self::$accountSettingsObject;
        self::$chart->trade_flag = 'all';
        self::$candleMaker->indicator = 'macd';

        /* reset history flag */
        self::$isHistoryLoaded = true;
        self::$isCreateClasses = true; // Chart and CandleMaker will be freshly created

        if(self::$isUnsubscribed){
            /* Manual UNsubscription object */
            $requestObject = json_encode([
                "op" => "unsubscribe",
                "args" => "instrument:" . self::$accountSettingsObject['historySymbolName']
            ]);
            self::$connection->send($requestObject);
            /* Unsubscribed. Then do nothing. Wait for the next bot start */
            self::$isUnsubscribed = false;
        }
    }

    /**
     * Accordingly to the active strategy we pass different parameters.
     * @todo 22.06.19 Pass the whole strategies object and strategy index.
     *
     * @param $jsonMessage
     * @return void
     */
    private static function messageParse($jsonMessage){
        \App\Classes\WebSocket\ConsoleWebSocket::messageParse(
            $jsonMessage,
            self::$console,
            self::$candleMaker,
            self::$chart,
            (array_key_exists('priceChannel', self::$strategiesSettingsObject) ? self::$strategiesSettingsObject['priceChannel'] : null),
            (array_key_exists('macd', self::$strategiesSettingsObject) ? self::$strategiesSettingsObject['macd'] : null)
            //self::$strategiesSettingsObject['priceChannel'], // if price channel. if macd = null
            //self::$strategiesSettingsObject['macd'] // if macd. if price channel = null
        );
    }
}