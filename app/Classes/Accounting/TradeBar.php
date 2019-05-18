<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class TradeBar
{
    public static function update($botSettings, $recordId, $timeStamp, $assetRow, $direction){
        DB::table($botSettings['botTitle'])
            ->where('id', $recordId)
            ->update([
                'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                'trade_price' => $assetRow[0]->close,
                'trade_direction' => $direction ,
                'trade_volume' => $botSettings['volume'],
                'trade_commission' => round(($assetRow[0]->close * $botSettings['commission'] / 100) * $botSettings['volume'], 4),
            ]);
    }
}