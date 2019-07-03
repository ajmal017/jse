<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 6:45 PM
 */

namespace App\Classes\Trading;
use ccxt\bitmex;
use Illuminate\Cache\Events\CacheHit;
use Mockery\Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Market order execution.
 * Volume is sent as a separate parameter.
 * When a position is flipped - the volume is doubled. This is set in Chart.php
 *
 * Class Exchange
 * @package App\Classes\Trading
 */
class Exchange
{
    private static $response;
    public static function placeMarketBuyOrder($botSettings, $volume){
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api: " . $exchange->urls['api'] . "\n";
            echo "Symbol: " . $botSettings['executionSymbolName'] . " in Exchnage.php \n";
            self::$response = $exchange->createMarketBuyOrder($botSettings['executionSymbolName'], $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
            dump(self::$response);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line 40');
            self::$response = $e->getMessage();

        }
        self::checkResponse();
    }

    public static function placeMarketSellOrder($botSettings, $volume){
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api:" . $exchange->urls['api'] . "\n";
            self::$response = $exchange->createMarketSellOrder($botSettings['executionSymbolName'], $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
            dump(self::$response);
        }
        catch (\Exception $e)
        {
            self::$response = $e->getMessage();
        }
        self::checkResponse();
    }

    public static function placeLimitSellOrder($botSettings, $price, $volume, $limitOrderObj){
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api: " . $exchange->urls['api'] . "\n";
            echo "Symbol: " . $botSettings['executionSymbolName'] . " in Exchnage.php \n";

            self::$response = $exchange->createLimitSellOrder($botSettings['executionSymbolName'], $volume, $price, array('clOrdID' => $limitOrderObj['clOrdID'])
            );

            echo "Limit order placement response: \n";
            dump(self::$response);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line 102');
            self::$response = $e->getMessage();
        }

        /**
         * Set values if array is returned - success.
         * If string - error. It will be catch in checkResponse
         */
        if (gettype(self::$response) == 'array'){
            $limitOrderObj['limitOrderTimestamp'] = 12345;
            $limitOrderObj['orderID'] = self::$response['info']['orderID'];
            Cache::put('bot_1', $limitOrderObj, now()->addMinute(30));
        }

        self::checkResponse($limitOrderObj);
    }

    public static function placeLimitBuyOrder($botSettings, $price, $volume, $limitOrderObj){
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api: " . $exchange->urls['api'] . "\n";
            echo "Symbol: " . $botSettings['executionSymbolName'] . " in Exchnage.php \n";

            self::$response = $exchange->createLimitBuyOrder($botSettings['executionSymbolName'], $volume, $price, array('clOrdID' => $limitOrderObj['clOrdID'])
            );

            echo "Limit order placement response: \n";
            dump(self::$response);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line 150');
            self::$response = $e->getMessage();
        }

        /**
         * Set values in array is returned - success.
         * If string - error. It will be catch in checkResponse
         */
        if (gettype(self::$response) == 'array'){
            $limitOrderObj['limitOrderTimestamp'] = 12345;
            $limitOrderObj['orderID'] = self::$response['info']['orderID'];
            Cache::put('bot_1', $limitOrderObj, now()->addMinute(30));
        }

        self::checkResponse($limitOrderObj);
    }

    public static function amendOrder($newPrice, $orderID, $botSettings){
        dump('****   AMEND ORDER ****');
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            self::$response = $exchange->privatePutOrder(array('orderID' => $orderID, 'price' => $newPrice));
            echo "Amend order placement response: \n";
            //dump(self::$response);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line (Exchange.php): ' . __LINE__);
            self::$response = $e->getMessage();
        }

        self::checkResponse();
    }

    private static function checkResponse(){
        if (gettype(self::$response) == 'array'){
            dump(self::$response);
        }

        if (gettype(self::$response) == 'string'){
            echo "Error string line 120: " . self::$response . "\n";
            switch(false){
                case !strpos(self::$response, 'Account has insufficient');
                    $error = 'Account has insufficient funds. Die.' . __FILE__ . ' '. __LINE__;
                    Log::notice($error);
                    die(__FILE__ . ' ' . __LINE__);

                case !strpos(self::$response, 'does not have market symbol'); // bitmex does not have market symbol
                    $error = 'Bitmex does not have market symbol. Execution is not possible';
                    throw new \Exception($error);
                    break;
                /* @see: https://www.bitmex.com/app/restAPI#Overload */
                case !strpos(self::$response, 'overloaded');
                    // The system is currently overloaded. Please try again later
                    throw new \Exception('Exchange overloaded');
                    break;
                /* Full error text: bitmex {"error":{"message":"Invalid ordStatus","name":"HTTPError"}} */
                case !strpos(self::$response, 'ordStatus');
                    Log::notice('Invalid ordStatus. Usually it happens when trying to amend and order which is already filled' . __FILE__ . ' '. __LINE__);
                    dump('Order amend. It usually happens when the order was fully filled');
                /**
                 * https://github.com/BitMEX/api-connectors/issues/202
                 * {"error":{"message":"This request has expired - `expires` is in the past. Current time: 1561918674","name":"HTTPError"}} */
                case !strpos(self::$response, 'expires');
                    Log::notice('This request has expired - `expires` is in the past. Current time: .. Check request signature. ' . __FILE__ . ' '. __LINE__);
                    dump('This request has expired - `expires` is in the past. Current time: .. Check request signature');
            }
        }
    }

    // Cam use it for market order record insertion
    public static function insertRecordToSignalTable($botSettings, $response){
        DB::table($botSettings['signalTable'])->insert([
            'order_type' => 'limit',
            'volume' => 999, // execution_volume
            // self::$response['info']['orderID'];
        ]);
    }
}