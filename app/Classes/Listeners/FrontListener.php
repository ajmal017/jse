<?php

namespace App\Classes\Listeners;
use App\Bot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Get trades from exchange, build chart and generate signals.
 * This is a replacement for web socket listener.
 */
class FrontListener
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
    private static $exchange;
    private static $message = '';

    public static function subscribe($console, $botId){
        self::$console = $console;
        self::$botId = $botId;
        self::$exchange = new \ccxt\bitmex();
        self::$exchange->urls['api'] = self::$exchange->urls['api'];

        /* Endless loop. Executes once per second */
        while (true){
            echo (Bot::where('id', $botId)->value('status') == 'running' ? 'Bot is: Running ' . now() : 'Bot is: idle ' . now()) . "\n";
            /* Update loop time stamp in bots table jse-274 */
            DB::table('bots')
                ->where('id', $botId)
                ->update([
                    'front_worker_update_time' => time()
                ]);

            /* Get strategies settings object*/
            self::$strategiesSettingsObject = \App\Classes\WebSocket\Front\Strategies::getSettings($botId);

            /* Get account settings object */
            self::$accountSettingsObject = \App\Classes\WebSocket\Front\TradingAccount::getSettings($botId);

            /* Trace */
            dump(self::$strategiesSettingsObject);

            /* Create Chart and Candle maker classes here. ONCE! Create again after STOP! */
            if (self::$isCreateClasses) {
                self::$candleMaker = new \App\Classes\Trading\CandleMaker(
                    (array_key_exists('priceChannel', self::$strategiesSettingsObject) ? 'priceChannel' : 'macd'),
                    self::$accountSettingsObject);
                dump (__FILE__);

                dump((array_key_exists('priceChannel', self::$strategiesSettingsObject) ? "!!!!!!! PC" : "!!!!!!!!!! MACD"));
                (array_key_exists('priceChannel', self::$strategiesSettingsObject) ?
                    self::$chart = new \App\Classes\Trading\Chart(self::$accountSettingsObject) :
                    self::$chart = new \App\Classes\Trading\MacdTradesTrigger(self::$accountSettingsObject));
                self::$isCreateClasses = false;
            }

            /* Start/stop bots */
            if (Bot::where('id', $botId)->value('status') == 'running'){
                if(array_key_exists('priceChannel', self::$strategiesSettingsObject)) self::startPriceChannelBot($botId);
                if(array_key_exists('macd', self::$strategiesSettingsObject)) self::startMacdBot($botId);
            }

            /* Stop the bot */
            if (Bot::where('id', $botId)->value('status') == 'idle'){
                if(array_key_exists('priceChannel', self::$strategiesSettingsObject)) self::stopPriceChannelBot($botId);
                if(array_key_exists('macd', self::$strategiesSettingsObject)) self::stopMacdBot($botId);
            }

            if (Bot::where('id', $botId)->value('status') == 'running'){
                /* Handle exception https://github.com/ccxt/ccxt/wiki/Manual#error-handling */
                try {
                    /* Get the last recent trade */
                    self::$message = self::$exchange->fetchTrades(self::$accountSettingsObject['executionSymbolName'], '', 1, array('reverse' => 'true'));
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

                if(gettype(self::$message == array())){
                    /* @TODO need to check ID of the trade. In case of the same trade is received twice - filter it */
                    $tradeObj = [
                        'data' => [
                            [
                                'lastPrice' => self::$message[0]['price'],
                                'timestamp' => self::$message[0]['datetime']
                            ]
                        ]
                    ];
                    self::messageParse($tradeObj);
                }
            }
            sleep(5);
        };
    }

    private static function startPriceChannelBot($botId){
        if (self::$isHistoryLoaded){
            /* @TODO DELETE IT FROM HERE! TESTING ONLY! */
            self::truncateSignalsTable($botId);

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
    }

    private static function startMacdBot($botId){
        if (self::$isHistoryLoaded){

            /* DELETE IT FROM HERE! TESTING ONLY! */
            self::truncateSignalsTable($botId);

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
    }


    private static function truncateSignalsTable($botId){
        DB::table('signal_' . $botId)->truncate();
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
        );
    }

}