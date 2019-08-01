<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 8:00 PM
 */

namespace App\Classes\Trading\Orders;


abstract class Signal extends LimitOrder
{
    /**
     * Close signal when a time is over and no execution received from Bitmex.
     * Close a signal artificially and continue trading.
     */
    private static function forceFinish($message, $botSettings){
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
            'commission' => 0.00075, // Signal row
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