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
        // Get is from bot settings
        //$botSettings['historySymbolName'];
        //$orderExecutionResponse['symbol'] = 'XBTUSD';

        if ($botSettings['historySymbolName'] == 'XBTUSD')
            $tradeCommissionValue = 1 / $lastRow[0]->close * $botSettings['volume'] * $botSettings['commission'];

        if ($botSettings['historySymbolName'] == 'ETHUSD')
            $tradeCommissionValue = $lastRow[0]->close * 0.000001 * $botSettings['volume'] * $botSettings['commission'];


        /* Commission calculation */
        /*if ($orderExecutionResponse['symbol'] == 'XBTUSD')
            self::$tradeCommissionValue = 1 / $lastRow->avg_fill_price * $lastRow->signal_volume * $lastRow->trade_commission_percent;

        if ($orderExecutionResponse['symbol'] == 'ETHUSD')
            self::$tradeCommissionValue = $lastRow->avg_fill_price * 0.000001 * $lastRow->signal_volume * $lastRow->trade_commission_percent;*/

        DB::table($botSettings['botTitle'])
            ->where('id', $lastRowId)
            ->update([
                'trade_date' => $lastRow[0]->date,
                'trade_price' => $tradePrice,
                'trade_direction' => $direction ,
                'trade_volume' => $botSettings['volume'],
                'trade_commission' => $tradeCommissionValue
            ]);
    }
}