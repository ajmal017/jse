<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 7:21 PM
 */

namespace App\Classes\Trading\Orders;

/**
 * Parse order book messages.
 * On each parsed tick
 * - get trading settings. Symbol, market or limit order, etc.
 * - read a row from signal tabel. Get signals out of it.
 * - place orders (limit/market).
 * - get executions.
 * - Orders placement and get execution are rate limited.
 *
 * Class OrderBook
 * @package App\Classes\Trading\Orders
 */
abstract class OrderBook extends Signal
{
    public static function orderBookParse(array $message){
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

    public static function orderBookTick($message){
        /* Get settings on each tick - once per 2 seconds. This limit is set in LimitOrderWs.php */
        $botSettings = \App\Classes\WebSocket\Front\TradingAccount::getSettings(LimitOrderMessage::$botId);
        /* Set limit order params */
        LimitOrderMessage::$limitOrderExecutionTime = $botSettings['executionTime'];
        LimitOrderMessage::$timeRange = $botSettings['timeRange'];
        LimitOrderMessage::$limitOrderOffset = $botSettings['offset'];

        dump('Limit order params LimitOrderMessage.php');
        dump(LimitOrderMessage::$limitOrderExecutionTime . ' ' . LimitOrderMessage::$timeRange . ' ' . LimitOrderMessage::$limitOrderOffset);

        /* Limit order offset. If this value is negative - the limit order will be converted to market */
        LimitOrderMessage::$limitOrderOffset = ceil($message['data'][0]['bids'][0][0] * $botSettings['offset'] / 100);


        // Market/Limit orderder go here.
        // If Market - don't even start limit and timers/

        /* Place and amend order */
        if (LimitOrderMessage::$signalRow[0]->direction == "sell")
            LimitOrder::handleSellLimitOrder($message, $botSettings);

        if (LimitOrderMessage::$signalRow[0]->direction == "buy")
            LimitOrder::handleBuyLimitOrder($message, $botSettings);

        /* Get trades-executions for a placed limit order */
        if(self::rateLimitheck2())
            if (array_key_exists('isLimitOrderPlaced', LimitOrderMessage::$limitOrderObj))
                if (LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'])
                    if (array_key_exists('orderID', LimitOrderMessage::$limitOrderObj))
                        if (LimitOrderMessage::$limitOrderObj['orderID'])
                            \App\Classes\Trading\Exchange::getOrders($botSettings, LimitOrderMessage::$limitOrderObj, LimitOrderMessage::$exchange);


        /**
         * Start 55 seconds timer for getting executions.
         * Force time signal close.
         * Once 55 seconds are over and no response has been receivd from Bitmex - finish the signal.
         * Add an artificial trade and continue trading.
         * We send bid as a parameter. In case returned avgFill price = null, bid will be used instead.
         */
        if(self::getExecutionsTimeRangeCheck())
            ForceSignalFinish::execute($message, $botSettings);
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
        if (LimitOrderMessage::$isGetExecutionsTickCheck || strtotime(now()) >= LimitOrderMessage::$addedTickGetExcutions) {
            LimitOrderMessage::$isGetExecutionsTickCheck = false;
            LimitOrderMessage::$addedTickGetExcutions = strtotime(now()) + LimitOrderMessage::$timeRange; // Seconds
            return true;
        }
    }

    /**
     * Rate limit check.
     *
     * @param $tickDateFullTime
     * @return bool
     */
    private static function rateLimitheck2(){
        if (LimitOrderMessage::$isFirstTimeTickCheck || strtotime(now()) >= LimitOrderMessage::$addedTickTime) {
            LimitOrderMessage::$isFirstTimeTickCheck = false;
            LimitOrderMessage::$addedTickTime = strtotime(now()) + 5; // Seconds
            return true;
        }
    }
}