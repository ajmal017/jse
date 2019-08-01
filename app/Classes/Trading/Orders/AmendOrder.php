<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 10:42 PM
 */

namespace App\Classes\Trading\Orders;
use Illuminate\Support\Facades\Cache;

abstract class AmendOrder extends TimeForce
{
    public static function amendBuyLimitOrder($bid, $botSettings, $amendReason){
        dump('amend price sell trace. ask: ' . $bid . " limit order price: " . LimitOrderMessage::$limitOrderObj['limitOrderPrice'] . " code: uu88");
        if ($bid - LimitOrderMessage::$limitOrderOffset == LimitOrderMessage::$limitOrderObj['limitOrderPrice']){
            dump('Bid == best BID!');
        } else {
            dump('PRICE CHANGED! ask: ' . $bid . " limit order price: " . LimitOrderMessage::$limitOrderObj['limitOrderPrice'] . " code: uu95");
            dump('---------------NOW CAN AMEND BUY ORDER (LimitOrderMessage.php line)' . __LINE__);

            \App\Jobs\AmendOrder::dispatch(
                $bid - LimitOrderMessage::$limitOrderOffset,
                (isset(Cache::get('bot_' . LimitOrderMessage::$botId)['orderID']) ?
                    Cache::get('bot_' . LimitOrderMessage::$botId)['orderID'] : 33445566),
                $botSettings,
                $amendReason,
                LimitOrderMessage::$exchange
            )->onQueue('bot_' . LimitOrderMessage::$queId);

            /* Put price to cache in order not to amend more than needed */
            LimitOrderMessage::$limitOrderObj['limitOrderPrice'] = $bid - LimitOrderMessage::$limitOrderOffset;
            Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));
        }
    }

    public static function amendSellLimitOrder($ask, $botSettings, $amendReason){
        dump('amend price sell trace. ask: ' . $ask . " limit order price: " . LimitOrderMessage::$limitOrderObj['limitOrderPrice'] . " code: uu87");
        if ($ask + LimitOrderMessage::$limitOrderOffset == LimitOrderMessage::$limitOrderObj['limitOrderPrice']){
            dump('Ask == best ASK!');
        } else {
            dump('PRICE CHANGED! ask: ' . $ask . " limit order price: " . LimitOrderMessage::$limitOrderObj['limitOrderPrice'] . " code: uu954");
            dump('---------------NOW CAN AMEND SELL ORDER');

            \App\Jobs\AmendOrder::dispatch(
                $ask + LimitOrderMessage::$limitOrderOffset,
                (isset(Cache::get('bot_' . LimitOrderMessage::$botId)['orderID']) ? Cache::get('bot_' . LimitOrderMessage::$botId)['orderID'] : 'NO_ORDERID_776676'),
                $botSettings,
                $amendReason,
                LimitOrderMessage::$exchange
            )->onQueue('bot_' . LimitOrderMessage::$queId);

            /* Put price to cache in order not to amend more than needed */
            LimitOrderMessage::$limitOrderObj['limitOrderPrice'] = $ask + LimitOrderMessage::$limitOrderOffset;
            Cache::put('bot_' . LimitOrderMessage::$botId, LimitOrderMessage::$limitOrderObj, now()->addMinute(30));
        }
    }
}