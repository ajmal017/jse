<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class TradeBar
{
    public static function update($botSettings, $direction, $tradePrice, $lastRowId){
        $lastRow =
            DB::table($botSettings['botTitle'])
                ->orderBy('id', 'desc')->take(1)
                ->get();

        /* Commission calculation */
        $orderExecutionResponse['symbol'] = 'XBTUSD';
        //if ($orderExecutionResponse['symbol'] == 'XBTUSD')
            $tradeCommissionValue = 1 / $lastRow[0]->close * $botSettings['volume'] * $botSettings['commission'];



        DB::table($botSettings['botTitle'])
            ->where('id', $lastRowId)
            ->update([
                'trade_date' => $lastRow[0]->date,
                'trade_price' => $tradePrice,
                'trade_direction' => $direction ,
                'trade_volume' => $botSettings['volume'],
                //'trade_commission' => round(($lastRow[0]->close * $botSettings['commission'] / 100) * $botSettings['volume'], 4),
                'trade_commission' => $tradeCommissionValue
            ]);
    }
}