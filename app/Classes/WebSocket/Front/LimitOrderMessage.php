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
use Illuminate\Support\Facades\Log;

class LimitOrderMessage
{
    private static $limitOrderObj;
    private static $signalRow;
    private static $botId;

    /* Rate limit vars */
    private static $isFirstTimeTickCheck = true;
    private static $isFirstTimeTickCheck2 = true;
    private static $isAmendOrderRateLimitheck = true;
    private static $addedTickTime;
    private static $addedTickTime2;
    private static $addedTickTimeAmend;

    private static $isGetExecutionsTickCheck = true;
    private static $addedTickGetExcutions;

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

        /**
         * Status of the signal is sent to pending once a limit order is placed. In Exchnage.php. placeLimitBuyOrder/placeLimitSellOrder
         */
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
        $botSettings = \App\Classes\WebSocket\Front\TradingAccount::getSettings(self::$botId);

        /* Place and amend order */
        if (self::$signalRow[0]->direction == "sell")
            self::handleSellLimitOrder($ask, $botSettings);

        if (self::$signalRow[0]->direction == "buy")
            self::handleBuyLimitOrder($bid, $botSettings);

        /* Get trades-executions for a placed limit order */
        if(self::rateLimitheck2())
            if (array_key_exists('isLimitOrderPlaced', self::$limitOrderObj))
                if (self::$limitOrderObj['isLimitOrderPlaced'])
                    if (array_key_exists('orderID', self::$limitOrderObj))
                        if (self::$limitOrderObj['orderID'])
                            \App\Classes\Trading\Exchange::getOrders($botSettings, self::$limitOrderObj);


        /* Start 55 seconds timer for getting executions */
        // Once over add a record to signals
        // Set limitOrderObj to 'after fully filled' state
        if(self::getExecutionsTimeRangeCheck())
            self::forceSignalFinish();
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
            self::$addedTickTime = strtotime(now()) + 5; // Seconds
            return true;
        }
    }

    /**
     * Do not amend order more than once per time interval.
     * It can flood the que and probable cause order expired error. Not sure.
     *
     * @return bool
     */
    private static function amendOrderRateLimitheck(){
        if (self::isAmendOrderRateLimitheck || strtotime(now()) >= self::$addedTickTimeAmend) {
            self::$isAmendOrderRateLimitheck = false;
            self::$addedTickTimeAmend = strtotime(now()) + 3; // Seconds
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


    /**
     * A time range during which an execution response must be received from Bitmex.
     * If it is not - a signal will be closed with force and with execution data taken from limit order placement response.
     * This may cause a trading balance not be equal to a signal.
     *
     * ATTENTION!
     * This value must be longer than limitOrderExecutionTimeCheck!
     * if it is 40
     * and getExecutionsTimeRangeCheck is 55
     * Then we have 15 seconds to pull executions out of Bitmex in case of force market order execution
     *
     * @return bool
     */
    private static function getExecutionsTimeRangeCheck(){
        if (self::$isGetExecutionsTickCheck || strtotime(now()) >= self::$addedTickGetExcutions) {
            self::$isGetExecutionsTickCheck = false;
            self::$addedTickGetExcutions = strtotime(now()) + 55; // Seconds
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


    // Second version.
    public static function executionParse2(array $message){
        foreach($message as $msg){
            if(array_key_exists('orderID', $msg)){
                echo "Exchange execution ID: " . $msg['orderID'] . "   -   Placed order ID: " . self::$limitOrderObj['orderID'] . "\n";

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

                                    /* Data object can contain multiple executions! https://dacoders.myjetbrains.com/youtrack/issue/JSE-195 */
                                    if (count($message) > 0){
                                        //dump('Execution DATA object contains more than 1 record. foreach it!!! LimitOrderMessage');
                                        foreach ($message as $execution){
                                            /* Check if fully filled. leavesQty - volume reminder */
                                            if(($execution['leavesQty']) == 0){
                                                dump('Order Fully filled');
                                                \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);
                                                // Set signal status to close
                                                \App\Classes\DB\SignalTable::updateSignalStatusToClose(self::$botId, $execution);
                                            } else {
                                                dump('Order NOT FULLY filled yet. Volume is written to DB: ' . $execution['lastQty']);
                                                \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);
                                            }
                                        }

                                        // Additional check.
                                        // Make sure that volume reminder == 0 after foreach is done. And only now we can refresh flags.
                                        // There can be several groups and foreach can work more than once when flags must be reset
                                        // exactly the whole order is fully filled.
                                        // Refresh flags and clOrdID in order to place new limit order

                                        if(($execution['leavesQty']) == 0) {
                                            self::$limitOrderObj['orderID'] = null;
                                            self::$limitOrderObj['isLimitOrderPlaced'] = false;
                                            self::$limitOrderObj['limitOrderPrice'] = null;
                                            self::$limitOrderObj['limitOrderTimestamp'] = null;
                                            Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
                                        }
                                    } else {
                                        dump('Message count = 0 LimitOrderMessage.php Code: uuyytt');
                                    }
                    } else {
                        dump('Can not find order ID. LimitOrderMessage.php in execution Code: qqwwee44');
                        //die();
                    }

            }
            else {
                echo "CANT FIND orderID field. Code: jjkkll\n";
            }
        }
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
                /* Start time force exit timer */
                self::limitOrderExecutionTimeCheck();
                /* Start execution receive time range timer*/
                self::getExecutionsTimeRangeCheck();
            }
            else {
             dump('Tried to place a SELL order while there is a PENDING order already. Die LimitOrderMessage.php ttyyuu');
             die();
            }
        } else {
            /**
             * Time force exit.
             * We calculate 40-something seconds here (or other time delat).
             * Once expired: send ask - 10% from the price - it will execute the limit order as market.
             */
            if(self::limitOrderExecutionTimeCheck()){
                self::timeForceExitSell($ask, $botSettings);
            }
            /**
             * Amend.
             * Do not amend if order has been placed. Additional check for the step after force exit.
             * https://dacoders.myjetbrains.com/youtrack/issue/JSE-228
             */
            self::$limitOrderObj = Cache::get('bot_' . self::$botId);
            if(!self::$limitOrderObj['isLimitOrderPlaced'])
                /* https://dacoders.myjetbrains.com/youtrack/issue/JSE-229 */
                if (self::amendOrderRateLimitheck())
                    self::amendSellLimitOrder($ask, $botSettings, 'regular amend');
        }
    }

    /**
     * First we need to see whether a limit order is placed - isLimitOrderPlaced
     *
     * @param $bid
     * @param $botSettings
     */
    private static function handleBuyLimitOrder($bid, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            if(self::$signalRow[0]->status != 'pending'){
                self::placeBuyLimitOrder($bid, $botSettings);
                /* Start time force exit timer */
                self::limitOrderExecutionTimeCheck($bid, $botSettings);
                /* Start execution receive time range timer*/
                self::getExecutionsTimeRangeCheck();
            } else {
                dump('Tried to place a BUY order while there is a PENDING order already. Die LimitOrderMessage.php ppooii');
                die();
            }
        } else {
            /* Time force exit */
            if(self::limitOrderExecutionTimeCheck()){
                self::timeForceExitBuy($bid, $botSettings);
            }
            /* Amend */
            self::$limitOrderObj = Cache::get('bot_' . self::$botId);
            if(!self::$limitOrderObj['isLimitOrderPlaced'])
                if (self::amendOrderRateLimitheck())
                    self::amendBuyLimitOrder($bid, $botSettings, 'regular amend');
        }
    }

    private static function placeBuyLimitOrder($bid, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE BUY LIMIT ORDERRRRR (LimitOrderMessage.php) code:rreeww ' . now());
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
            self::$limitOrderObj,
            self::$botId
        );

        \App\Classes\DB\SignalTable::updateSignalStatus(self::$botId);
    }

    private static function placeSellLimitOrder($ask, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE SELL LIMIT ORDERRRRR LimitOrderMessage.php code: rrffgg ' . now());
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
            self::$limitOrderObj,
            self::$botId
        );

        // Update to pending
        //self::updateSignalStatus();
        \App\Classes\DB\SignalTable::updateSignalStatus(self::$botId);

    }

    private static function amendBuyLimitOrder($bid, $botSettings, $amendReason){
        //if ($bid - self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
        if ($bid == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PREPARE TO AMEND BUY ORDER! if the order really placed? Limit price: '
                    . self::$limitOrderObj['limitOrderPrice']) . "\n";

            if(array_key_exists('limitOrderTimestamp', self::$limitOrderObj)){
                dump('---------------NOW CAN AMEND BUY ORDER (LimitOrderMessage.php line)' . __LINE__);
                //$response = \App\Classes\Trading\Exchange::amendOrder($bid - self::$limitOrderObj['step'], Cache::get('bot_' . self::$botId)['orderID'], $botSettings);
                \App\Jobs\AmendOrder::dispatch(
                    $bid,
                    Cache::get('bot_' . self::$botId)['orderID'],
                    $botSettings,
                    $amendReason
                );

                // Put price to cache in order not to amend more than needed
                //self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderObj['step'];
                self::$limitOrderObj['limitOrderPrice'] = $bid;
                Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

    private static function amendSellLimitOrder($ask, $botSettings, $amendReason){
        //if ($ask + self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
        if ($ask == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PREPARE TO AMEND SELL ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

            /* https://dacoders.myjetbrains.com/youtrack/issue/JSE-222 */
            if(array_key_exists('limitOrderTimestamp', self::$limitOrderObj)){
                dump('---------------NOW CAN AMEND SELL ORDER');
                \App\Jobs\AmendOrder::dispatch(
                    $ask,
                    Cache::get('bot_' . self::$botId)['orderID'],
                    $botSettings,
                    $amendReason
                );

                // Put price to cache in order no to amend more than needed
                //self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
                self::$limitOrderObj['limitOrderPrice'] = $ask;
                Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

    private static function timeForceExitBuy($bid, $botSettings){
        dump('------------------------------------------------------------------ FORCE TIME BUY LIMIT CLOSE! --------- ' . now());
        self::amendBuyLimitOrder($bid + self::limitToMarketOrderPrice($bid), $botSettings, 'force time close');
        /* Set flag to true. Do not amend the order after time forece exit*/
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
    }

    private static function timeForceExitSell($ask, $botSettings){
        dump('------------------------------------------------------------------ FORCE TIME SELL LIMIT CLOSE! --------- ' . now());
        self::amendSellLimitOrder($ask - self::limitToMarketOrderPrice($ask), $botSettings, 'time force amend');
        /**
         * Set flag to true. Do not amend the order after time force exit
         * https://dacoders.myjetbrains.com/youtrack/issue/JSE-227
         */
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
    }

    /**
     * When we need to execute a limit order fast - we transfer it to makrket by adding a 10% increment.
     * It causes limit order to execute as market.
     * In this case regular commission is payed indtead of a rebate
     *
     * @param $price
     * @return double $increment
     */
    private static function limitToMarketOrderPrice($price){
        $increment = $price * 5 / 100;  // 5%
        return ceil($increment);
    }

    /**
     * Close signal when a time is over and no execution received from Bitmex.
     */
    private static function forceSignalFinish(){

        echo "**************************\n";
        echo "** FORCE SIGNAL CLOSE!  **\n";
        echo "**************************\n";

        self::$limitOrderObj = Cache::get('bot_' . self::$botId);
        $execution = [
            'ordType' => 'not_used',
            'side' => 'Buy',
            'lastQty' => 0,
            'timestamp' => self::$limitOrderObj['limitOrderTimestamp'],
            //'time_stamp' => strtotime($orderExecutionResponse['timestamp']) * 1000, // 13 digits

            'trade_date' => gmdate("Y-m-d G:i:s", strtotime(self::$limitOrderObj['limitOrderTimestamp'])), // mysql date format
            'avgPx' => self::$limitOrderObj['price'], // Exec price
            'price' => self::$limitOrderObj['price'], // In case of amend-market order, will be the price which goes to opposite side of order book
            'commission' => 0,
            'leavesQty' => 0,
            'execType' => 'forceTrade',
            'orderID' => self::$limitOrderObj['orderID']
        ];

        \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);

        \App\Classes\DB\SignalTable::signalFinish(self::$botId, [
            'orderID' => self::$limitOrderObj['orderID'],
            'avgPx' => self::$limitOrderObj['price'],
            'commission' => 0
        ]);
    }
}