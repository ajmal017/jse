<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/30/2019
 * Time: 11:27 PM
 */

namespace App\Classes\DB;
use Illuminate\Support\Facades\DB;

class SignalTable
{
    /**
     * Add execution records to DB.
     * One limit order can have many executions.
     * Once a first record is added - change signal status from new to pending.
     *
     * @param $orderEecutionResponse
     */
    public static function insertRecord($orderEecutionResponse, $botId){
        DB::table('signal_' . $botId)->insert([
            'order_type' => $orderEecutionResponse['ordType'],
            'direction' => $orderEecutionResponse['side'],
            'volume' => $orderEecutionResponse['lastQty'],
            'time_stamp' => strtotime($orderEecutionResponse['timestamp']) * 1000, // 13 digits
            'trade_date' => gmdate("Y-m-d G:i:s", strtotime($orderEecutionResponse['timestamp'])), // mysql date format
            'trade_price' => $orderEecutionResponse['price'],
            'trade_commission' => $orderEecutionResponse['commission'],
            'volume_reminder' => $orderEecutionResponse['leavesQty'],
            'type' => $orderEecutionResponse['execType']
        ]);
    }

    /**
     * Once a limit order is fully filled change its status from pending to closed.
     * There can be a case when there is no pending status assigned - change to close as well.
     * This happens when an order gets filled immediately.
     * @param $orderEecutionResponse
     */
    public static function updateSignalStatus($botId){
        DB::table('signal_' . $botId)
            ->where('type', 'signal')
            ->where('status', 'pending')
            ->orwhere('status', 'new')
            ->update([
                'status' => 'closed'
            ]);
    }
}