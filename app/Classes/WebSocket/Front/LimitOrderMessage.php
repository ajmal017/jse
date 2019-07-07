<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/27/2019
 * Time: 6:50 PM
 */

namespace App\Classes\WebSocket\Front;
use App\Jobs\AmendOrder;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LimitOrderMessage
{
    private static $limitOrderObj;
    private static $isFirstTimeTickCheck = true;
    private static $isFirstTimeTickCheck2 = true;
    private static $addedTickTime;
    private static $addedTickTime2;
    private static $signalRow;
    private static $botId;

    public static function parse(array $message, $botId){

        self::$limitOrderObj = Cache::get('bot_' . $botId);
        self::$botId = $botId;

        /**
         * Check DB for new signals
         */
        self::$signalRow =
            DB::table('signal_' . $botId)
                ->where('status', 'new')
                ->orwhere('status', 'pending')
                ->get();
        
        if (count(self::$signalRow) > 1) {
            $message = 'There are more than onw record with New status in signals. Die from LimitOrderMessage.php';
            Log::emergency($message);
            die ($message);
        }

        if (count(self::$signalRow) == 1){
            if (self::$signalRow[0]->status != 'closed' ){

                /**
                 * Best bid/ask order book parse.
                 * Used for limit order placement and order amend.
                 */
                self::orderBookParse($message);

                /**
                 * Order parse.
                 * Not used.
                 */
                //self::orderParse($message);

                /**
                 * Execution parse.
                 * Used to track executions of the limit order and add these records to DB.
                 * Also indicates when the order was fully filled.
                 * Sets order status from new to pending/close.
                 * Actual statuses are set in SignalTable.php
                 */
                //self::executionParse($message);

            } else {
                //dump('No new signals. LimitOrderMessage.php ' . now());
            }
        } else {
            //dump('No new or pending signals in signals table. LimitOrderMessage.php ' . now());
        }

    }

    private static function orderBookTick($ask, $bid){

        // Get it from model
        // Add signal_ + botId

        $botSettings = \App\Classes\WebSocket\Front\TradingAccount::getSettings(self::$botId);

        /*$botSettings = [
            'api' => 'ct5AF7LcE3bsfz4gR5yTfvBq',
            'apiSecret' => 'Zy9UDdTGojC_T6RE2JjOY0N2F4EhQXqBxo92DSxU1_f0pXLg',
            'isTestnet' => 0,
            'executionSymbolName' => 'BTC/USD', // BTC/USD ADAU19
            'signalTable' => 'signal_1'
        ];*/


        // testnet
        /*$botSettings = [
            'api' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
            'apiSecret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK',
            'isTestnet' => 1,
            'executionSymbolName' => 'BTC/USD', // BTC/USD ADAU19
            'signalTable' => 'signal_1'
        ];*/

        if (self::$signalRow[0]->direction == "sell")
            self::handleSellLimitOrder($ask, $botSettings);

        if (self::$signalRow[0]->direction == "buy")
            self::handleBuyLimitOrder($bid, $botSettings);

        /* Get trades-executions for a placed limit order */
        if(self::rateLimitheck2())
            if (self::$limitOrderObj['isLimitOrderPlaced'])
                \App\Classes\Trading\Exchange::getOrders($botSettings, self::$limitOrderObj);
    }

    /**
     * Rate limit check.
     *
     * @param $tickDateFullTime
     * @return bool
     */
    private static function rateLimitheck2(){
        if (self::$isFirstTimeTickCheck || strtotime(now()) >= self::$addedTickTime) {
            self::$isFirstTimeTickCheck = false;
            self::$addedTickTime = strtotime(now()) + 4; // Seconds
            return true;
        }
    }

    /**
     * Limit order execution time.
     * Once exceeded - limit order is turned to a market via the price extention.
     * It is needed when a limit order was waiting too long for the execution.
     *
     * @return bool
     */
    private static function limitOrderExecutionTimeCheck(){
        if (self::$isFirstTimeTickCheck2 || strtotime(now()) >= self::$addedTickTime2) {
            self::$isFirstTimeTickCheck2 = false;
            self::$addedTickTime2 = strtotime(now()) + 40; // Seconds
            return true;
        }
    }

    private static function orderBookParse(array $message){

        if(array_key_exists('table', $message))
            if($message['table'] == 'orderBook10')
                if(array_key_exists('action', $message))
                    if($message['action'] == 'update')
                        if(array_key_exists('data', $message)){
                            /**
                             * Send parsed best bid/ask from order book.
                             * Rate limit. Order amend - once per 2 (or other delay) seconds.
                             * Otherwise we get to many amends and it gets flooded.
                             */
                            self::orderBookTick($message['data'][0]['asks'][0][0], $message['data'][0]['bids'][0][0]);
                        }
    }

    /**
     * Not used.
     *
     * @param array $message
     */
    private static function orderParse(array $message){
        if(array_key_exists('table', $message))
            if($message['table'] == 'order')
                if(array_key_exists('action', $message)) // Action can be = 'insert' as well
                    if($message['action'] == 'update')

                        if(array_key_exists('data', $message)){
                            if(array_key_exists(0, $message['data']))

                                // Check orderID
                                if(array_key_exists('orderID', $message['data'][0]))
                                    // echo $message['data'][0]['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";
                            If (($message['data'][0]['orderID']  == self::$limitOrderObj['orderID'])){

                                if(array_key_exists('leavesQty', $message['data'][0]))

                                    if(($message['data'][0]['leavesQty']) == 0){
                                        //dump($message['data'][0]);
                                        //dump('Order Fully filled');
                                        //\App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                        //die('die from LimitOrderMessage.php xxccvv'); // Works good
                                    } else {
                                        //dump($message['data'][0]);
                                        //dump('Order NOT FULLY filled yet');
                                        //\App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                        //die('die from LimitOrderMessage.php yyggff'); // Works good
                                    }

                            } else {
                                //dump('Can not find order ID. LimitOrderMessage.php');
                            }
                        }
    }

    // Second version.
    public static function executionParse2(array $message){
        foreach($message as $msg){
            if(array_key_exists('orderID', $msg)){
                echo $msg['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";

                if(array_key_exists('orderID', $msg))
                    If (($msg['orderID']  == self::$limitOrderObj['orderID'])){

                        /**
                         * Check execType.
                         * Can be:
                         * Trade - when order gets filled. Can be partial or full
                         * New - fresh order is placed
                         * Replaced - order amend
                         */
                        if(array_key_exists('execType', $msg))
                            if (($msg['execType']  == 'Trade'))

                                //if(array_key_exists('leavesQty', $msg['info']))
                                    /* Data object can contain multiple executions! https://dacoders.myjetbrains.com/youtrack/issue/JSE-195 */
                                    if (count($message) > 0){
                                        dump('Execution DATA object contains more than 1 record. foreach it!!! LimitOrderMessage');
                                        //dump($message);
                                        foreach ($message as $execution){
                                            /* Check if fully filled. leavesQty - volume reminder */
                                            if(($execution['leavesQty']) == 0){
                                                dump('Order Fully filled');
                                                \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);
                                                // Set signal status to close
                                                \App\Classes\DB\SignalTable::updateSignalStatus(self::$botId);
                                                //die('die from LimitOrderMessage.php execution ddffgg');

                                            } else {
                                                dump('Order NOT FULLY filled yet');
                                                \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);
                                                //die('die from LimitOrderMessage.php execution jjhhgg');
                                            }
                                        }

                                        // Additional check.
                                        // Make sure that volume reminder == 0 after foreach is done. And only now we can refresh flags.
                                        // There can be several groups and foreach can work more than once when flags must be reset
                                        // exactly the whole order is fully filled.
                                        // Refresh flags and clOrdID in order to place new limit order


                                        if(($execution['leavesQty']) == 0) {
                                            //self::$limitOrderObj = Cache::get('bot_1');
                                            self::$limitOrderObj['orderID'] = null;
                                            self::$limitOrderObj['clOrdID'] = 'abc-123-' . now();
                                            self::$limitOrderObj['isLimitOrderPlaced'] = false;
                                            self::$limitOrderObj['limitOrderPrice'] = null;
                                            self::$limitOrderObj['limitOrderTimestamp'] = null;

                                            Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
                                        }
                                    } else {
                                        dump('Message count = 0 LimitOrderMessage.php uuyytt');
                                    }
                    } else {
                        dump('Can not find order ID. LimitOrderMessage.php in execution qqwwee44');
                        //die();
                    }

            }
            else {
                echo "CANT FIND orderID field. jjkkll\n";
            }
        }
    }

    public static function executionParse(array $message){
        //if(array_key_exists('table', $message))
        //    if($message['table'] == 'execution')
        //        if(array_key_exists('action', $message))
        //            if($message['action'] == 'insert')

                        //if(array_key_exists('data', $message)){
                            //if(array_key_exists(0, $message['data']))

                                /* Check orderID */
                                //if(array_key_exists('orderID', $message['data'][0])){
                                    foreach($message as $msg){

                                        dump($msg['info']);

                                        if(array_key_exists('orderID', $msg['info']))
                                            echo $msg['info']['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";

                                        if(array_key_exists('orderID', $msg['info']))
                                            If (($msg['info']['orderID']  == self::$limitOrderObj['orderID'])){

                                                /**
                                                 * Check execType.
                                                 * Can be:
                                                 * Trade - when order gets filled. Can be partial or full
                                                 * New - fresh order is placed
                                                 * Replaced - order amend
                                                 */
                                                if(array_key_exists('execType', $msg['info']))
                                                    if (($msg['info']['execType']  == 'Trade'))

                                                        /**
                                                         * Check quantity in the trade.
                                                         */
                                                        if(array_key_exists('leavesQty', $msg['info']))

                                                            /* Data object can contain multiple executions! https://dacoders.myjetbrains.com/youtrack/issue/JSE-195 */
                                                            if (count($message['data']) > 0){
                                                                dump('Execution DATA object contains more thatn 1 record. foreach it!!! LimitOrderMessage');
                                                                // die('Execution DATA object contains more thatn 1 record. NOT HANDLED!!! LimitOrderMessage');
                                                                dump($message);
                                                                foreach ($message['data'] as $execution){
                                                                    /* Check if fully filled. leavesQty - volume reminder */
                                                                    //if(($msg['info']['leavesQty']) == 0){
                                                                    if(($execution['leavesQty']) == 0){
                                                                        dump('Order Fully filled');
                                                                        \App\Classes\DB\SignalTable::insertRecord($execution);
                                                                        // Set signal status to close
                                                                        \App\Classes\DB\SignalTable::updateSignalStatus();
                                                                        //die('die from LimitOrderMessage.php execution ddffgg');

                                                                    } else {
                                                                        dump('Order NOT FULLY filled yet');
                                                                        \App\Classes\DB\SignalTable::insertRecord($execution);
                                                                        //die('die from LimitOrderMessage.php execution jjhhgg');
                                                                    }
                                                                }

                                                                // Additional check.
                                                                // Make sure that volume reminder == 0 after foreach is done. And only now we can refresh flags.
                                                                // There can be several groups and foreach can work more than once when flags must be reset
                                                                // exactly the whole order is fully filled.
                                                                // Refresh flags and clOrdID in order to place new limit order


                                                                if(($execution['leavesQty']) == 0) {
                                                                    //self::$limitOrderObj = Cache::get('bot_1');
                                                                    self::$limitOrderObj['orderID'] = null;
                                                                    self::$limitOrderObj['clOrdID'] = 'abc-123-' . now();
                                                                    self::$limitOrderObj['isLimitOrderPlaced'] = false;
                                                                    self::$limitOrderObj['limitOrderPrice'] = null;
                                                                    self::$limitOrderObj['limitOrderTimestamp'] = null;

                                                                    Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
                                                                }
                                                            }
                                            } else {
                                                dump('Can not find order ID. LimitOrderMessage.php in execution qqwwee');
                                                die();
                                            }
                                    }
                                //}
                        //}
    }

    private static function handleSellLimitOrder($ask, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            /**
             * Don't place limit order if status is new or pending
             * This is an additional check https://dacoders.myjetbrains.com/youtrack/issue/JSE-204
             * I suspect that isLimitOrderPlaced flag does not properly work.
             */
            if (self::$signalRow[0]->status != 'pending'){
                self::placeSellLimitOrder($ask, $botSettings);
            }
            else {
             dump('Tried to place a SELL order while there is a PENDING order already. Die LimitOrderMessage.php ttyyuu');
             die();
            }
        } else {
            /**
             * We can calculate 50 seconds here.
             * Once expired: send ask - 10% from the price - it will execute the limit order as market.
             */
            //dump('&&&&&&&&&&&&&&&------------FORCE flag sell rrffv: ' . (self::limitOrderExecutionTimeCheck() ? 'true' : 'false'));

            self::amendSellLimitOrder($ask, $botSettings);

            if(self::limitOrderExecutionTimeCheck()){
                dump('------------------------------------------------------------------ FORCE TIME SELL LIMIT CLOSE! ---------');
                self::amendSellLimitOrder($ask - 100, $botSettings);
            }

        }
    }

    /**
     * First we need to see whether a alimit order is placed - isLimitOrderPlaced
     *
     * @param $bid
     * @param $botSettings
     */
    private static function handleBuyLimitOrder($bid, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            if(self::$signalRow[0]->status != 'pending'){
                self::placeBuyLimitOrder($bid, $botSettings);
            } else {
                dump('Tried to place a BUY order while there is a PENDING order already. Die LimitOrderMessage.php ppooii');
                die();
            }
        } else {
            //dump('&&&&&&&&&&&&&&&------------FORCE flag buy ooiiuu: ' . (self::limitOrderExecutionTimeCheck() ? 'true' : 'false'));
            self::amendBuyLimitOrder($bid, $botSettings);

            if(self::limitOrderExecutionTimeCheck()){
                dump('------------------------------------------------------------------ FORCE TIME BUY LIMIT CLOSE! ---------');
                self::amendBuyLimitOrder($bid + 100, $botSettings);
            }
        }
    }

    private static function placeBuyLimitOrder($bid, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE BUY LIMIT ORDERRRRR!!!');
        //self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderObj['step'];
        self::$limitOrderObj['limitOrderPrice'] = $bid;
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'buy',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            //$bid - self::$limitOrderObj['step'],
            $bid,
            self::$limitOrderObj
        );

        /* Set signal status to pending */
        DB::table('signal_' . self::$botId)
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);
    }

    private static function placeSellLimitOrder($ask, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE SELL LIMIT ORDERRRRR!!!');
        //self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
        self::$limitOrderObj['limitOrderPrice'] = $ask;
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

        /**
         * Use bid/ask price + step/increment for testing purposes.
         *
         */
        PlaceLimitOrder::dispatch(
            'sell',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            //$ask + self::$limitOrderObj['step'],
            $ask,
            self::$limitOrderObj
        );

        /* Set signal status to pending */
        DB::table('signal_' . self::$botId)
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);
    }

    private static function amendBuyLimitOrder($bid, $botSettings){
        //if ($bid - self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
        if ($bid == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PRICE HAS CHANGED! PREPARE TO AMEND SELL ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

            if(self::$limitOrderObj['limitOrderTimestamp'] != null){
                dump('---------------NOW CAN AMEND SELL ORDER');
                //$response = \App\Classes\Trading\Exchange::amendOrder($bid - self::$limitOrderObj['step'], Cache::get('bot_' . self::$botId)['orderID'], $botSettings);
                $response = \App\Classes\Trading\Exchange::amendOrder($bid, Cache::get('bot_' . self::$botId)['orderID'], $botSettings);
                //dump($response);

                // Put price to cache in order no to amend more than needed
                //self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderObj['step'];
                self::$limitOrderObj['limitOrderPrice'] = $bid;
                Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

    private static function amendSellLimitOrder($ask, $botSettings){
        //if ($ask + self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
        if ($ask == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PRICE HAS CHANGED! PREPARE TO AMEND SELL ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

            //if(self::$limitOrderObj['limitOrderTimestamp'] != null){
            /* https://dacoders.myjetbrains.com/youtrack/issue/JSE-222 */
            if(array_key_exists('limitOrderTimestamp', self::$limitOrderObj)){
                dump('---------------NOW CAN AMEND SELL ORDER');
                $response = \App\Classes\Trading\Exchange::amendOrder(
                    //$ask + self::$limitOrderObj['step'],
                    $ask,
                    Cache::get('bot_' . self::$botId)['orderID'],
                    $botSettings);
                //dump($response);

                // Put price to cache in order no to amend more than needed
                //self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
                self::$limitOrderObj['limitOrderPrice'] = $ask;
                Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

}