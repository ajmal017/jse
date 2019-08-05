<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 8:02 PM
 */

namespace App\Classes\Trading\Orders;
use \Illuminate\Support\Facades\Cache;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Support\Facades\Log;


abstract class LimitOrder extends ForceSignalFinish
{
    public static function handleSellLimitOrder($message, $botSettings){
        if(!LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced']){
            /**
             * Don't place limit order if status is new or pending
             * This is an additional check https://dacoders.myjetbrains.com/youtrack/issue/JSE-204
             * I suspect that isLimitOrderPlaced flag does not properly work.
             */
            if (LimitOrderMessage::$signalRow[0]->status != 'pending'){
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
             * We calculate 40-something seconds here (or other time delay).
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
            LimitOrderMessage::$limitOrderObj = Cache::get('bot_' . LimitOrderMessage::$botId);
            /* Amend */
            if (LimitOrderMessage::amendOrderRateLimitheck())
                self::amendSellLimitOrder($message['data'][0]['asks'][0][0], $botSettings, 'regular amend');
        }
    }

    /**
     * First we need to see whether a limit order is placed - isLimitOrderPlaced
     *
     * @param $bid
     * @param $botSettings
     */
    public static function handleBuyLimitOrder($message, $botSettings){
        if(!LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced']){
            if(LimitOrderMessage::$signalRow[0]->status != 'pending'){
                LimitOrderMessage::placeBuyLimitOrder($message, $botSettings);
                /* Start time force exit timer */
                LimitOrderMessage::limitOrderExecutionTimeCheck();
                /* Start execution receive time range timer*/
                self::getExecutionsTimeRangeCheck();
            } else {
                $msg = 'Tried to place a BUY order while there is a PENDING order already. Die LimitOrderMessage.php';
                dump($msg);
                Log::emergency($msg);
                die();
            }
        } else {
            /* Time force exit */
            if(LimitOrderMessage::limitOrderExecutionTimeCheck()){
                self::timeForceExitBuy($message['data'][0]['bids'][0][0], $botSettings);
            }
            LimitOrderMessage::$limitOrderObj = Cache::get('bot_' . LimitOrderMessage::$botId);
            /* Amend */
            if (LimitOrderMessage::amendOrderRateLimitheck())
                self::amendBuyLimitOrder($message['data'][0]['bids'][0][0], $botSettings, 'regular amend');
        }
    }

    public static function placeBuyLimitOrder($message, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE BUY LIMIT ORDERRRRR (LimitOrderMessage.php) code:rreeww ' . now());
        LimitOrderMessage::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['bids'][0][0] - LimitOrderMessage::$limitOrderOffset;
        LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'buy',
            LimitOrderMessage::$signalRow[0]->signal_volume,
            $botSettings,
            $message['data'][0]['bids'][0][0] - LimitOrderMessage::$limitOrderOffset,
            LimitOrderMessage::$limitOrderObj,
            LimitOrderMessage::$botId,
            LimitOrderMessage::$exchange
        )->onQueue('bot_' . LimitOrderMessage::$queId);

        \App\Classes\DB\SignalTable::updateSignalStatus(
            LimitOrderMessage::$botId,
            [
                'timestamp' => strtotime($message['data'][0]['timestamp']) * 1000,
                'avgFillPrice' => $message['data'][0]['bids'][0][0]
            ]);
    }

    public static function placeSellLimitOrder($message, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE SELL LIMIT ORDERRRRR LimitOrderMessage.php code: rrffgg ' . now());
        LimitOrderMessage::$limitOrderObj['limitOrderPrice'] = $message['data'][0]['asks'][0][0] + LimitOrderMessage::$limitOrderOffset;
        LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'sell',
            LimitOrderMessage::$signalRow[0]->signal_volume,
            $botSettings,
            $message['data'][0]['asks'][0][0] + LimitOrderMessage::$limitOrderOffset,
            LimitOrderMessage::$limitOrderObj,
            LimitOrderMessage::$botId,
            LimitOrderMessage::$exchange
        )->onQueue('bot_' . LimitOrderMessage::$queId);

        /* Update signal's status to pending and add initial values to the signal */
        \App\Classes\DB\SignalTable::updateSignalStatus(LimitOrderMessage::$botId,
            [
                'timestamp' => strtotime($message['data'][0]['timestamp']) * 1000,
                'avgFillPrice' => $message['data'][0]['asks'][0][0]
            ]);
    }

    /**
     * Limit order execution time.
     * Once exceeded - limit order is turned to a market via the price extension.
     * It is needed when a limit order was waiting too long for the execution.
     *
     * @return bool
     */
    private static function limitOrderExecutionTimeCheck(){
        if (LimitOrderMessage::$isFirstTimeTickCheck2 || strtotime(now()) >= LimitOrderMessage::$addedTickTime2) {
            LimitOrderMessage::$isFirstTimeTickCheck2 = false;
            LimitOrderMessage::$addedTickTime2 = strtotime(now()) + LimitOrderMessage::$limitOrderExecutionTime; // Seconds
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
        if (LimitOrderMessage::$isGetExecutionsTickCheck || strtotime(now()) >= LimitOrderMessage::$addedTickGetExcutions) {
            LimitOrderMessage::$isGetExecutionsTickCheck = false;
            LimitOrderMessage::$addedTickGetExcutions = strtotime(now()) + LimitOrderMessage::$timeRange; // Seconds
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
        if (LimitOrderMessage::$isAmendOrderRateLimitCheck || strtotime(now()) >= LimitOrderMessage::$addedTickTimeAmend) {
            LimitOrderMessage::$isAmendOrderRateLimitCheck = false;
            LimitOrderMessage::$addedTickTimeAmend = strtotime(now()) + 3; // Seconds
            return true;
        }
    }
}