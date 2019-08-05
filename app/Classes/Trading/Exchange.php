<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 6:45 PM
 */

namespace App\Classes\Trading;
use App\Classes\Trading\Orders\LimitOrderMessage;
use ccxt\bitmex;
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

    public static function placeMarketBuyOrder($botSettings, $volume, $botId, $exchange){
        dump('placeMarketBuyOrder. Exchange.php line: ' . __LINE__);

        /* Testnet or live. test or api */
        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test'];
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api: " . $exchange->urls['api'] . "\n";
            echo "Symbol: " . $botSettings['executionSymbolName'] . " in Exchnage.php \n";
            self::$response = $exchange->createMarketBuyOrder($botSettings['executionSymbolName'], $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line (code: ttggff): ' . __LINE__);
            self::$response = $e->getMessage();
        }

        if (gettype(self::$response) == 'array'){
            /* Update signal. Add orderId, date, timestamp, etc. */
            \App\Classes\DB\SignalTable::updateSignalInfo($botId, self::$response);

            /* Update status. Initial insertion. DO WE NEED IT? */
            self::$response['avgFillPrice'] = self::$response['price'];
            \App\Classes\DB\SignalTable::updateSignalStatus($botId, self::$response);

            /* Insert a record */
            self::$response['info']['lastQty'] = self::$response['price']; // There is no lastQty index in the response
            self::$response['info']['commission'] = 0.0075;
            self::$response['info']['execType'] = 'market or time force';
            \App\Classes\DB\SignalTable::insertRecord(self::$response['info'], $botId);

            /* Close the signa; */
            \App\Classes\DB\SignalTable::updateSignalStatusToClose($botId, self::$response['info']);

            $limitOrderObj['isLimitOrderPlaced'] = false;
            Cache::put('bot_' . $botId, $limitOrderObj, now()->addMinute(30));
        }

        self::checkResponse($botSettings);
    }

    public static function placeMarketSellOrder($botSettings, $volume, $botId, $exchange){
        dump('placeMarketSellOrder. Exchange.php line: ' . __LINE__);

        /* Testnet or live. test or api */
        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test'];
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api:" . $exchange->urls['api'] . "\n";
            self::$response = $exchange->createMarketSellOrder($botSettings['executionSymbolName'], $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line (code uuiiee): ' . __LINE__);
            self::$response = $e->getMessage();
        }

        if (gettype(self::$response) == 'array'){
            /* Update signal. Add orderId, date, timestamp, etc. */
            \App\Classes\DB\SignalTable::updateSignalInfo($botId, self::$response);

            /* Update status. Initial insertion. DO WE NEED IT? */
            self::$response['avgFillPrice'] = self::$response['price'];
            \App\Classes\DB\SignalTable::updateSignalStatus($botId, self::$response);

            /* Insert a record */
            self::$response['info']['lastQty'] = self::$response['price']; // There is no lastQty index in the response
            self::$response['info']['commission'] = 0.0075;
            self::$response['info']['execType'] = 'market or time force';
            \App\Classes\DB\SignalTable::insertRecord(self::$response['info'], $botId);

            /* Close the signal; */
            \App\Classes\DB\SignalTable::updateSignalStatusToClose($botId, self::$response['info']);

            $limitOrderObj['isLimitOrderPlaced'] = false;
            Cache::put('bot_' . $botId, $limitOrderObj, now()->addMinute(30));
        }

        self::checkResponse($botSettings);
    }

    public static function placeLimitSellOrder($botSettings, $price, $volume, $limitOrderObj, $botId, $exchange){
        dump('placeBuySellOrder. Exchange.php line: ' . __LINE__);
        echo __FILE__ . " line: " . __LINE__ . "\n";

        //$exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            //self::$response = $exchange->createLimitSellOrder($botSettings['executionSymbolName'], $volume, $price, array('clOrdID' => $limitOrderObj['clOrdID']));
            self::$response = $exchange->createLimitSellOrder($botSettings['executionSymbolName'], $volume, $price);
            echo "Limit order placement response: \n";
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line(Exchnage.php):' . __LINE__);
            self::$response = $e->getMessage();
        }

        /**
         * Set values if array is returned - success.
         * If string - error. It will be caught in checkResponse
         */
        if (gettype(self::$response) == 'array'){
            $limitOrderObj['limitOrderTimestamp'] = self::$response['datetime'];
            $limitOrderObj['orderID'] = self::$response['info']['orderID'];
            $limitOrderObj['price'] = self::$response['info']['price'];
            Cache::put('bot_' . $botId, $limitOrderObj, now()->addMinute(30));
            echo('SELL Limit order placed (Exchnage.php). MUST NOT BE EMPTY! orderID: ' . self::$response['info']['orderID'] . "\n");
            //dump(Cache::get('bot_' . $botId)); // Object writes correctly

            /* Update signal. Add orderId, date, timestamp, etc. */
            \App\Classes\DB\SignalTable::updateSignalInfo($botId, self::$response);
        }

        self::checkResponse($botSettings);
    }

    public static function placeLimitBuyOrder($botSettings, $price, $volume, $limitOrderObj, $botId, $exchange){
        echo "placeLimitBuyOrder. Exchnage.php line: " . __LINE__ . "\n";
        //$exchange = new bitmex();

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            self::$response = $exchange->createLimitBuyOrder($botSettings['executionSymbolName'], $volume, $price);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line(Exchnage.php):' . __LINE__);
            self::$response = $e->getMessage();
        }

        /**
         * Set values in array is returned - success.
         * If string - error. It will be proceeded in checkResponse
         */
        if (gettype(self::$response) == 'array'){
            $limitOrderObj['limitOrderTimestamp'] = self::$response['datetime'];
            $limitOrderObj['orderID'] = self::$response['info']['orderID'];
            $limitOrderObj['price'] = self::$response['info']['price'];
            Cache::put('bot_' . $botId, $limitOrderObj, now()->addMinute(30));
            echo('BUY Limit order placed (Exchnage.php). MUST NOT BE EMPTY! orderID: ' . self::$response['info']['orderID'] . "\n");
            //dump(Cache::get('bot_' . $botId));

            /* Update signal. Add orderId, date, timestamp, etc. */
            \App\Classes\DB\SignalTable::updateSignalInfo($botId, self::$response);
        }

        self::checkResponse($botSettings);
    }

    public static function amendOrder($newPrice, $orderID, $botSettings, $amendReason, $exchange){
        dump("****   AMEND ORDER. Reason: $amendReason ****");
        echo  "Exchnage.php. line: " . __LINE__ . "\n";
        Echo "orderID: " . $orderID . " MUST NOT BE NULL or EMPTY (Exchnage.php) code: ffddss\n";

        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            self::$response = $exchange->privatePutOrder(array('orderID' => $orderID, 'price' => $newPrice));
            echo "Amend order placement response(Exchange.php): \n";
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line (Exchange.php code: vvccxx): ' . __LINE__);
            self::$response = $e->getMessage();
        }

        self::checkResponse($botSettings);
    }

    public static function getOrders($botSettings, $limitOrderObj, $exchange){
        echo '****   GET TRADES FOR PLACED ORDER (Exchange.php). orderID: ' . $limitOrderObj['orderID'] . " line: " . __LINE__ . "\n";
        //$exchange = new bitmex();
        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            $orderID = $limitOrderObj['orderID'];
            self::$response = $exchange->privateGetExecutionTradeHistory(array('count' => 20, 'filter' => ['orderID' => $orderID])); // Works GOOD!
        }
        catch (\Exception $e)
        {
            dump('--------- exception ddffgg Exchange.php: ' . __LINE__);
            self::$response = $e->getMessage();
        }

        /**
         * If not array - error!
         * Do not output the responce to console because it is to heavy and long.
         * Output only in case when it is a error response.
         */
        if(gettype(self::$response) == 'array'){
            \App\Classes\WebSocket\Front\LimitOrderMessage::executionParse2(self::$response);
        } else {
            self::checkResponse($botSettings);
        }
    }







    public static function cancelOrder($botSettings, $exchange){
        dump('cancelOrder. Exchange.php line: ' . __LINE__);

        /* Testnet or live. test or api */
        if($botSettings['isTestnet'] == 1){
            $exchange->urls['api'] = $exchange->urls['test'];
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api'];
        $exchange->secret = $botSettings['apiSecret'];

        try{
            echo "API path. test or api:" . $exchange->urls['api'] . "\n";
            //dump('orderID:');
            //dump(LimitOrderMessage::$limitOrderObj);

            // REMOVE !! TESTING ONLY!
            self::$response = $exchange->cancelOrder(Cache::get('bot_1')['orderID']);
            echo "Order cancel response: \n";
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line (code ffddee): ' . __LINE__);
            self::$response = $e->getMessage();
        }

        if (gettype(self::$response) == 'array'){
            Dump('Order canceled. OK. Exchange.php' . __LINE__);
        }

        self::checkResponse($botSettings);
    }

    private static function checkResponse($botSettings){
        if (gettype(self::$response) == 'array'){
            dump(self::$response);
        }

        if (gettype(self::$response) == 'string'){
            echo "Error. Line: ". __LINE__ . " Text: " . self::$response . "\n";
            switch(false){
                case !strpos(self::$response, 'Account has insufficient');
                    $error = 'Account has insufficient funds. Die.' . __FILE__ . ' '. __LINE__;
                    dump('Account has insufficient funds. Workers stopped. Die. Exchange.php line: ' . __LINE__);
                    Log::notice($error);

                    // Stop workers here

                    dump('dump from Exchnage.php code: yyhhgg55');
                    dump($botSettings['botTitle']);

                    DB::table('bots')
                        ->where('db_table_name', $botSettings['botTitle'])
                        //->where('db_table_name', 'bot_1')
                        ->update([
                            'status' => 'idle'
                        ]);

                    die();

                case !strpos(self::$response, 'does not have market symbol'); // bitmex does not have market symbol
                    $error = 'Bitmex does not have market symbol. Execution is not possible';
                    throw new Exception($error);
                    break;
                /* @see: https://www.bitmex.com/app/restAPI#Overload */
                case !strpos(self::$response, 'overloaded');
                    // The system is currently overloaded. Please try again later
                    // Log::warning('Exchange overloaded! Exchnage.php' . __FILE__ . ' '. __LINE__);
                    $error = 'Exchange overloaded';
                    throw new Exception($error);
                    break;
                /* Full error text: bitmex {"error":{"message":"Invalid ordStatus","name":"HTTPError"}} */
                case !strpos(self::$response, 'ordStatus');
                    Log::notice('Invalid ordStatus. Usually it happens when trying to amend and order which is already filled' . __FILE__ . ' '. __LINE__);
                    dump('Order amend. It usually happens when the order was fully filled');
                    break;
                /**
                 * https://github.com/BitMEX/api-connectors/issues/202
                 * {"error":{"message":"This request has expired - `expires` is in the past. Current time: 1561918674","name":"HTTPError"}}
                 * Have no idea what does this error do. It seems like nothing happens.
                 */
                case !strpos(self::$response, 'expires');
                    Log::notice('This request has expired - `expires` is in the past. Current time: .. Check request signature. ' . __FILE__ . ' '. __LINE__);
                    break;
            }
        }
    }
}