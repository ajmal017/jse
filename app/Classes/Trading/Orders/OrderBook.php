<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 7:21 PM
 */

namespace App\Classes\Trading\Orders;
use App\Jobs\PlaceMarketOrder;
use Illuminate\Support\Facades\Cache;

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
    /**
     * This method is used when WS order book message is parsed.
     * In case of the same message returned from REST API call, orderBookTick is called instead.
     *
     * @param array $message
     */
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
        /* Place order as market or limit */
        $isPlaceAsMarketOrder = $botSettings['isPlaceAsMarket'];

        dump('Limit order params LimitOrderMessage.php code: kkoo99');
        dump($botSettings);

        /* Limit order offset. If this value is negative - the limit order will be converted to market */
        LimitOrderMessage::$limitOrderOffset = ceil($message['data'][0]['bids'][0][0] * $botSettings['offset'] / 100);

        /* Place and amend order */
        if (LimitOrderMessage::$signalRow[0]->direction == "sell")
            if($isPlaceAsMarketOrder){
                if(!LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced']){
                    PlaceMarketOrder::dispatch(
                        'sell',
                        LimitOrderMessage::$signalRow[0]->signal_volume,
                        $botSettings,
                        LimitOrderMessage::$botId,
                        LimitOrderMessage::$exchange
                    )->onQueue('bot_' . LimitOrderMessage::$queId);

                    /* This flag is reset in Exchange.php when the order is filled. */
                    $limitOrderObj['isLimitOrderPlaced'] = true;
                    Cache::put('bot_' . LimitOrderMessage::$botId, $limitOrderObj, now()->addMinute(30));
                }

            } else {
                LimitOrder::handleSellLimitOrder($message, $botSettings);
            }


        if (LimitOrderMessage::$signalRow[0]->direction == "buy")
            if($isPlaceAsMarketOrder){
                if(!LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced']){
                    PlaceMarketOrder::dispatch(
                        'buy',
                        LimitOrderMessage::$signalRow[0]->signal_volume,
                        $botSettings,
                        LimitOrderMessage::$botId,
                        LimitOrderMessage::$exchange
                    )->onQueue('bot_' . LimitOrderMessage::$queId);
                    $limitOrderObj['isLimitOrderPlaced'] = true;
                    Cache::put('bot_' . LimitOrderMessage::$botId, $limitOrderObj, now()->addMinute(30));
                }

            } else {
                LimitOrder::handleBuyLimitOrder($message, $botSettings);
            }

        /* Get trades-executions for a placed limit order */
        if(self::rateLimitheck2() && !$isPlaceAsMarketOrder)
            if (array_key_exists('isLimitOrderPlaced', LimitOrderMessage::$limitOrderObj))
                if (LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'])
                    if (array_key_exists('orderID', LimitOrderMessage::$limitOrderObj))
                        if (LimitOrderMessage::$limitOrderObj['orderID'])
                            \App\Classes\Trading\Exchange::getOrders($botSettings, LimitOrderMessage::$limitOrderObj, LimitOrderMessage::$exchange);


        /**
         * In bot settings - Time range.
         * Start 55 seconds timer for getting executions.
         * Force time signal close.
         * Once 55 seconds are over and no response has been receivd from Bitmex - finish the signal.
         * Add an artificial trade and continue trading.
         * We send bid as a parameter. In case returned avgFill price = null, bid will be used instead.
         */

        // DISABLED
        //if(self::getExecutionsTimeRangeCheck() && !$isPlaceAsMarketOrder)
        //    ForceSignalFinish::execute($message, $botSettings);
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