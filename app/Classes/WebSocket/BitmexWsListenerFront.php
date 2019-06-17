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
use App\Strategy;
use App\Symbol;
use App\Account;
use App\Exchange;
use App\PricechannelSettings;
use App\MacdSettings;

class BitmexWsListenerFront
{
    public static $console;
    public static $candleMaker;
    public static $chart;
    private static $symbol;
    private static $priceChannelPeriod;
    private static $smaFilterPeriod;
    private static $macdSettings;
    private static $connection;
    private static $isHistoryLoaded = true;
    private static $isUnsubscribed = false;
    private static $botId;
    private static $execution_symbol_name;
    private static $apiPath;
    private static $api;
    private static $apiSecret;
    private static $commission;
    private static $isTestnet;

    private static $isCreateCLasses = true;

    public static function subscribe($connector, $loop, $console, $botId){

        // get strategy_id from bots
        $strategyId = Bot::where('id', $botId)->value('strategy_id');

        // get strategy type from strategies using strategy_id
        $strategyTypeId = Strategy::where('id', $strategyId)->value('strategy_type_id');

        // if strategy_tyme == 1 price channel
        if ($strategyTypeId == '1'){
            // get pricechannel_settings_id from strategies
            $pricechannelSettingsId = Strategy::where('id', $strategyId)->value('pricechannel_settings_id');
            // get settings row from price_channel_settings
            $pricechannelSettingsRow = PricechannelSettings::where('id', $pricechannelSettingsId)->get();
            self::$priceChannelPeriod = $pricechannelSettingsRow[0]->time_frame;
            self::$smaFilterPeriod = $pricechannelSettingsRow[0]->sma_filter_period;
        }

        // if strategy_type == 2 macd
        if ($strategyTypeId == '2'){
            $macdSettingsId = Strategy::where('id', $strategyId)->value('macd_settings_id');
            $macdSettingsRow = MacdSettings::where('id', $macdSettingsId)->get();
        }


        self::$console = $console;
        self::$botId = $botId;

        $self = get_called_class(); // For static methods call inside an anonymous function


        $loop->addPeriodicTimer(1, function() use($loop, $botId, $self) {
            $account_id = Bot::where('id', $botId)->value('account_id');
            //$exchange_id = Account::where('id', $account_id)->value('exchange_id');
            self::$api = Account::where('id', $account_id)->value('api');
            self::$apiSecret = Account::where('id', $account_id)->value('api_secret');
            self::$isTestnet = Account::where('id', $account_id)->value('is_testnet');
            $symbolId = Bot::where('id', $botId)->value('symbol_id');
            self::$execution_symbol_name = Symbol::where('id', $symbolId)->value('execution_symbol_name');
            self::$commission = Symbol::where('id', $symbolId)->value('commission');
            $history_symbol_name = Symbol::where('id', $symbolId)->value('history_symbol_name');

            echo "Bot id: " . $botId . " Time: " . now() . "\n";
            echo "isTestnet: " . self::$isTestnet . "\n";
            dump(self::$api);
            dump(self::$apiSecret);
            dump(self::$execution_symbol_name);
            dump($history_symbol_name);
            echo "Price channel settings. TimeFrame/Sma filter period: " . self::$priceChannelPeriod . " / " . self::$smaFilterPeriod . "\n";

            // Create Chart and Candle maker classes here. ONCE!
            // Create again after STOP!
            if (self::$isCreateCLasses) {
                self::$candleMaker = new \App\Classes\Trading\CandleMaker(
                    'priceChannel',
                    [
                        'botTitle' => Bot::where('id', self::$botId)->value('db_table_name'),
                        'bitmex_api_path' => self::$apiPath,
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
                        'commission' => self::$commission,
                        'api_path' => self::$isTestnet,
                        'api_key' => self::$api,
                        'secret' => self::$apiSecret
                    ]);
                self::$isCreateCLasses = false;
            }

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

                    /* Initial indicators calculation and chart reload*/
                    \App\Classes\Indicators\PriceChannel::calculate(self::$priceChannelPeriod, Bot::where('id', $botId)->value('db_table_name'), true);
                    \App\Classes\Indicators\Sma::calculate('close', 2, 'sma1', Bot::where('id', $botId)->value('db_table_name'), true);

                    /* Reload chart */
                    $self::reloadChart(['frontEndId' => Bot::where('id', $botId)->value('front_end_id')]);

                    // SUBSCRIPTION GOES HERE
                    /* Manual subscription object */
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

                /* @todo 12.06.19 Move to a separate method. This code and the first usage  */
                self::$chart->trade_flag = 'all'; // JSE-117. Trade flag doesn't reset on stop
                self::$chart->executionSymbolName = self::$execution_symbol_name;
                self::$chart->volume = Bot::where('id', self::$botId)->value('volume');
                self::$chart->botSettings =
                    [
                        'botTitle' => Bot::where('id', self::$botId)->value('db_table_name'),
                        'volume' => Bot::where('id', self::$botId)->value('volume'),
                        'commission' => self::$commission,
                        'api_path' => self::$isTestnet,
                        'api_key' => self::$api,
                        'secret' => self::$apiSecret
                    ];

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
                    self::$isCreateCLasses; // Chart and CandleMaker will be freshly created
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