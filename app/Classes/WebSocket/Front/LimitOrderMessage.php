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
    private static $isAmendOrderRateLimitCheck = true;
    private static $addedTickTime;
    private static $addedTickTime2;
    private static $addedTickTimeAmend;

    private static $isGetExecutionsTickCheck = true;
    private static $addedTickGetExcutions;
    private static $exchange;

    /* Time (sec) during which a limit order will located in order book. If it is not filled, it will be amend (turn to market) */
    private static $limitOrderExecutionTime;
    /**
     * If during this time an execution information is not received, mark the order as closed with price from order book.
     * This value can not be shorter than $limitOrderExecutionTime
     */
    private static $timeRange;
    private static $limitOrderOffset;

    public static function parse(array $message, $botId, $exchnage){
        /**
         * ddd
         */
        self::$limitOrderObj = Cache::get('bot_' . $botId);

        self::$botId = $botId;
        self::$exchange = $exchnage;
        /**
         * Check DB for new signals
         */
        self::$signalRow =
            DB::table('signal_' . $botId)
                ->where('status', 'new')
                ->orwhere('status', 'pending')
                ->get();

        if (count(self::$signalRow) > 1) {
            $message = 'There are more than onw record with New status in signals. LimitOrderMessage.php';
            Log::emergency($message);
        }

        /**
         * Status of the signal is sent to pending once a limit order is placed. In Exchnage.php. placeLimitBuyOrder/placeLimitSellOrder
         * Signals are added to signal_1.. table from Chart.php or MacdTradesTrigger.php
         */
        if (count(self::$signalRow) == 1){
            if (self::$signalRow[0]->status != 'closed' ){
                /**
                 * Best bid/ask order book parse.
                 * Used for limit order placement and order amend.
                 */
                self::orderBookParse($message);
            } else {
                //dump('No new signals. LimitOrderMessage.php ' . now());
            }
        } else {
            //dump('No new or pending signals in signals table. LimitOrderMessage.php ' . now());
        }
    }

    private static function orderBookTick($message){

        /* Get settings on each tick - once per 2 seconds. This limit is set in LimitOrderWs.php */
        $botSettings = \App\Classes\WebSocket\Front\TradingAccount::getSettings(self::$botId);
        /* Set limit order params */
        self::$limitOrderExecutionTime = $botSettings['executionTime'];
        self::$timeRange = $botSettings['timeRange'];
        self::$limitOrderOffset = $botSettings['offset'];

        dump('Limit order params LimitOrderMessage.php');
        dump(self::$limitOrderExecutionTime . ' ' . self::$timeRange . ' ' . self::$limitOrderOffset);

        /* Limit order offset. If this value is negative - the limit order will be converted to market */
        self::$limitOrderOffset = ceil($message['data'][0]['bids'][0][0] * $botSettings['offset'] / 100);

        /* Place and amend order */
        if (self::$signalRow[0]->direction == "sell")
            self::handleSellLimitOrder($message, $botSettings);

        if (self::$signalRow[0]->direction == "buy")
            self::handleBuyLimitOrder($message, $botSettings);

        /* Get trades-executions for a placed limit order */
        if(self::rateLimitheck2())
            if (array_key_exists('isLimitOrderPlaced', self::$limitOrderObj))
                if (self::$limitOrderObj['isLimitOrderPlaced'])
                    if (array_key_exists('orderID', self::$limitOrderObj))
                        if (self::$limitOrderObj['orderID'])
                            \App\Classes\Trading\Exchange::getOrders($botSettings, self::$limitOrderObj, self::$exchange);


        /**
         * Start 55 seconds timer for getting executions.
         * Force time signal close.
         * Once 55 seconds are over and no response has been receivd from Bitmex - finish the signal.
         * Add an artificial trade and continue trading.
         * We send bid as a parameter. In case returned avgFill price = null, bid will be used instead.
         */
        if(self::getExecutionsTimeRangeCheck())
            self::forceSignalFinish($message, $botSettings);
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
        if (self::$isAmendOrderRateLimitCheck || strtotime(now()) >= self::$addedTickTimeAmend) {
            self::$isAmendOrderRateLimitCheck = false;
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
            self::$addedTickTime2 = strtotime(now()) + self::$limitOrderExecutionTime; // Seconds
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
            self::$addedTickGetExcutions = strtotime(now()) + self::$timeRange; // Seconds
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
                            self::orderBookTick($message);
                        }
    }

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

    private static function handleSellLimitOrder($message, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            /**
             * Don't place limit order if status is new or pending
             * This is an additional check https://dacoders.myjetbrains.com/youtrack/issue/JSE-204
             * I suspect that isLimitOrderPlaced flag does not properly work.
             */
            if (self::$signalRow[0]->status != 'pending'){
                self::placeSellLimitOrder($message, $botSettings);
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
                self::timeForceExitSell($message['data'][0]['asks'][0][0], $botSettings);
            }
            /**
             * Amend.
             * Do not amend if order has been placed. Additional check for the step after force exit.
             * https://dacoders.myjetbrains.com/youtrack/issue/JSE-228
             */
            self::$limitOrderObj = Cache::get('bot_' . self::$botId);

            if (self::amendOrderRateLimitheck())
                self::amendSellLimitOrder($message['data'][0]['asks'][0][0], $botSettings, 'regular amend');
        }
    }

    /**
     * First we need to see whether a limit order is placed - isLimitOrderPlaced
     *
     * @param $bid
     * @param $botSettings
     */
    private static function handleBuyLimitOrder($message, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            if(self::$signalRow[0]->status != 'pending'){
                self::placeBuyLimitOrder($message, $botSettings);
                /* Start time force exit timer */
                self::limitOrderExecutionTimeCheck();
                /* Start execution receive time range timer*/
                self::getExecutionsTimeRangeCheck();
            } else {
                dump('Tried to place a BUY order while there is a PENDING order already. Die LimitOrderMessage.php ppooii');
                die();
            }
        } else {
            /* Time force exit */
            if(self::limitOrderExecutionTimeCheck()){
                self::timeForceExitBuy($message['data'][0]['bids'][0][0], $botSettings);
            }
            /* Amend */
            self::$limitOrderObj = Cache::get('bot_' . self::$botId);

            if (self::amendOrderRateLimitheck())
                self::amendBuyLimitOrder($message['data'][0]['bids'][0][0], $botSettings, 'regular amend');
        }
    }

    private static function placeBuyLimitOrder($message, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE BUY LIMIT ORDERRRRR (LimitOrderMessage.php) code:rreeww ' . now());
        self::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['bids'][0][0] - self::$limitOrderOffset;
        //self::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['bids'][0][0];
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'buy',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            $message['data'][0]['bids'][0][0] - self::$limitOrderOffset,
            //$message['data'][0]['bids'][0][0],
            self::$limitOrderObj,
            self::$botId,
            self::$exchange
        );

        \App\Classes\DB\SignalTable::updateSignalStatus(
            self::$botId,
            [
                'timestamp' => strtotime($message['data'][0]['timestamp']) * 1000,
                'avgFillPrice' => $message['data'][0]['bids'][0][0]
            ]);
    }

    private static function placeSellLimitOrder($message, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE SELL LIMIT ORDERRRRR LimitOrderMessage.php code: rrffgg ' . now());
        self::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['asks'][0][0] + self::$limitOrderOffset;
        //self::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['asks'][0][0];
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'sell',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            $message['data'][0]['asks'][0][0] + self::$limitOrderOffset,
            //$message['data'][0]['asks'][0][0],
            self::$limitOrderObj,
            self::$botId,
            self::$exchange
        );

        /* Update signal's status to pending and add initial values to the signal*/
        \App\Classes\DB\SignalTable::updateSignalStatus(self::$botId,
            [
                'timestamp' => strtotime($message['data'][0]['timestamp']) * 1000,
                'avgFillPrice' => $message['data'][0]['asks'][0][0]
            ]);
    }

    private static function amendBuyLimitOrder($bid, $botSettings, $amendReason){

        dump('amend price sell trace. ask: ' . $bid . " limit order price: " . self::$limitOrderObj['limitOrderPrice'] . " code: uu88");
        if ($bid - self::$limitOrderOffset == self::$limitOrderObj['limitOrderPrice']){
        //if ($bid == self::$limitOrderObj['limitOrderPrice']){
            dump('Bid == best BID!');
        } else {
            dump('PRICE CHANGED! ask: ' . $bid . " limit order price: " . self::$limitOrderObj['limitOrderPrice'] . " code: uu95");
            dump('---------------NOW CAN AMEND BUY ORDER (LimitOrderMessage.php line)' . __LINE__);

            \App\Jobs\AmendOrder::dispatch(
                $bid - self::$limitOrderOffset,
                (isset(Cache::get('bot_' . self::$botId)['orderID']) ?
                    Cache::get('bot_' . self::$botId)['orderID'] : 33445566),
                $botSettings,
                $amendReason,
                self::$exchange
            );
            // Put price to cache in order not to amend more than needed
            self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderOffset;
            //self::$limitOrderObj['limitOrderPrice'] = $bid;
            Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
        }
    }

    private static function amendSellLimitOrder($ask, $botSettings, $amendReason){
        dump('amend price sell trace. ask: ' . $ask . " limit order price: " . self::$limitOrderObj['limitOrderPrice'] . " code: uu87");
        if ($ask + self::$limitOrderOffset == self::$limitOrderObj['limitOrderPrice']){
        //if ($ask == self::$limitOrderObj['limitOrderPrice']){
            dump('Ask == best ASK!');
        } else {
            dump('PRICE CHANGED! ask: ' . $ask . " limit order price: " . self::$limitOrderObj['limitOrderPrice'] . " code: uu954");
            dump('---------------NOW CAN AMEND SELL ORDER');

            \App\Jobs\AmendOrder::dispatch(
                $ask + self::$limitOrderOffset,
                (isset(Cache::get('bot_' . self::$botId)['orderID']) ? Cache::get('bot_' . self::$botId)['orderID'] : 'NO_ORDERID_776676'),
                $botSettings,
                $amendReason,
                self::$exchange
            );

            /* Put price to cache in order not to amend more than needed */
            self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderOffset;
            Cache::put('bot_' . self::$botId, self::$limitOrderObj, now()->addMinute(30));
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
     * Close a signal artificially and continue trading.
     */
    private static function forceSignalFinish($message, $botSettings){

        // strtotime($message['data'][0]['timestamp']) * 1000
        //$ask = $message['data'][0]['asks'][0][0];
        $bid = $message['data'][0]['bids'][0][0];

        echo "*****************************************************\n";
        echo "** FORCE SIGNAL FINISH (bitmex sent no response)!  **\n";
        echo "*****************************************************\n";

        self::$limitOrderObj = Cache::get('bot_' . self::$botId);

        /**
         * In case even no order placement response was received.
         * There can be case when an order was placed buy even placement response was lost and no received from Bitmex.
         * Add these fake variables. They need for writing a signal row. It can be written with empty data.
         */
        if (array_key_exists('limitOrderTimestamp', self::$limitOrderObj)){
            $timeStamp = self::$limitOrderObj['limitOrderTimestamp'];
        } else {
            $timeStamp = strtotime($message['data'][0]['timestamp']) * 1000;
        }

        if (array_key_exists('price', self::$limitOrderObj)){
            $price = self::$limitOrderObj['price'];
        } else {
            $price = $message['data'][0]['bids'][0][0];
        }

        if (array_key_exists('orderID', self::$limitOrderObj)){
            $orderID = self::$limitOrderObj['orderID'];
        } else {
            $orderID = 12345677654;
        }

        $execution = [
            'symbol' => $botSettings['historySymbolName'], // Use real symbol. It can break profit calculation coz there we check symbol name
            'ordType' => 'not_used',
            //'side' => 'Buy',
            'side' => self::$signalRow[0]->direction,
            'lastQty' => self::$signalRow[0]->signal_volume, // Signal row
            'timestamp' => strtotime($message['data'][0]['timestamp']) * 1000,
            'trade_date' => gmdate("Y-m-d G:i:s", strtotime($timeStamp)), // mysql date format
            'avgPx' => ($price ? $price : $bid), // Exec price. It can be null
            'price' => ($price ? $price : $bid), // In case of amend-market order, will be the price which goes to opposite side of order book
            'commission' => -0.00025, // Signal row
            'leavesQty' => 7894,
            'execType' => 'forceTrade',
            'orderID' => $orderID
        ];

        \App\Classes\DB\SignalTable::insertRecord($execution, self::$botId);

        \App\Classes\DB\SignalTable::signalFinish(self::$botId, $execution);

        /**
         * Set limit object to initial start.
         * Do not place ot amend orders. Wait for other signals.
         */
        $limitOrderObj = [
            'orderID' => null,
            'clOrdID' => 'abc-123-' . now(),
            'direction' => 'sell',
            'isLimitOrderPlaced' => false,
            'limitOrderPrice' => null,
            'limitOrderTimestamp' => null,
            //'step' => 0 // Limit order position placement. Used for testing purpuses. If set - order will be locate deeper in the book.
        ];

        Cache::put('bot_' . self::$botId, $limitOrderObj, now()->addMinute(30));
    }
}