<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 8/1/2019
 * Time: 6:00 PM
 */

namespace App\Classes\Trading\Orders;
use App\Jobs\CancelOrder;
use App\Jobs\PlaceMarketOrder;
use Illuminate\Support\Facades\Cache;

/**
 * A limit order is moved in located in an order book for a certain period of time.
 * Once the time is up - the order must be converted from limit to market.
 * This is done by adding the price offset.
 * It will result that the order will be placed to the opposite side of the orderbook.
 * The limit order will be executed immediatly. The market commission is payed.
 *
 * Class TimeForce
 * @package App\Classes\Trading\Orders
 */
abstract class TimeForce
{
    public static function timeForceExitBuy($bid, $botSettings){
        dump('------------------------------------------------------------------ FORCE TIME BUY LIMIT CLOSE! --------- ' . now());
        CancelOrder::dispatch(
            $botSettings,
            LimitOrderMessage::$exchange)
            ->onQueue('bot_' . LimitOrderMessage::$queId);

        PlaceMarketOrder::dispatch(
            'buy',
            LimitOrderMessage::$signalRow[0]->signal_volume,
            $botSettings,
            LimitOrderMessage::$botId,
            LimitOrderMessage::$exchange
        )->onQueue('bot_' . LimitOrderMessage::$queId);

        /* Set flag to true. Do not amend the order after time force exit*/
        LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));
    }

    public static function timeForceExitSell($ask, $botSettings){
        dump('------------------------------------------------------------------ FORCE TIME SELL LIMIT CLOSE! --------- ' . now());

        CancelOrder::dispatch(
            $botSettings,
            LimitOrderMessage::$exchange)
            ->onQueue('bot_' . LimitOrderMessage::$queId);

        PlaceMarketOrder::dispatch(
            'sell',
            LimitOrderMessage::$signalRow[0]->signal_volume,
            $botSettings,
            LimitOrderMessage::$botId,
            LimitOrderMessage::$exchange
        )->onQueue('bot_' . LimitOrderMessage::$queId);

        /**
         * Set flag to true. Do not amend the order after time force exit
         * https://dacoders.myjetbrains.com/youtrack/issue/JSE-227
         */
        LimitOrderMessage::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));
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
}