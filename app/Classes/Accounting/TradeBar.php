<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class TradeBar
{
    public static function update($botSettings, $timeStamp, $direction){
        $lastRow =
            DB::table($botSettings['botTitle'])
                ->orderBy('id', 'desc')->take(1)
                ->get();
        DB::table($botSettings['botTitle'])
            ->where('id', $lastRow[0]->id)
            ->update([
                'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                'trade_price' => $lastRow[0]->close,
                'trade_direction' => $direction ,
                'trade_volume' => $botSettings['volume'],
                'trade_commission' => round(($lastRow[0]->close * $botSettings['commission'] / 100) * $botSettings['volume'], 4),
            ]);
    }
}