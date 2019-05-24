<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class TradeBar
{
    // public static function update($botSettings, $timeStamp, $direction){
    public static function update($botSettings, $direction, $tradePrice, $lastRowId){
        $lastRow =
            DB::table($botSettings['botTitle'])
                ->orderBy('id', 'desc')->take(1)
                ->get();

        DB::table($botSettings['botTitle'])
            //->where('id', $lastRow[0]->id)
            ->where('id', $lastRowId)
            ->update([
                //'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                'trade_date' => $lastRow[0]->date,
                //'trade_price' => $lastRow[0]->close,
                'trade_price' => $tradePrice,
                'trade_direction' => $direction ,
                'trade_volume' => $botSettings['volume'],
                'trade_commission' => round(($lastRow[0]->close * $botSettings['commission'] / 100) * $botSettings['volume'], 4),
            ]);

        die($lastRowId);
    }
}