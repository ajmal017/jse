<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/22/2019
 * Time: 4:09 PM
 */

namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class AccumulatedProfit
{
    public static function calculate($botSettings, $recordId){
        $lastRecord = DB::table($botSettings['botTitle'])->where('id', $recordId)->get();
        $temp = DB::table($botSettings['botTitle'])->whereNotNull('trade_direction')->get();

        /* A trade is open at this bar */
        if ($lastRecord[0]->trade_direction == "buy" || $lastRecord[0]->trade_direction == "sell") {
            DB::table($botSettings['botTitle'])
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => (count($temp) > 1 ? $temp[count($temp) - 2]->accumulated_profit + $lastRecord[0]->trade_profit : 0)
                ]);

        } else /* A bar on which there is no trade. The trade has been already opened at one of the previous bars */
        {
            DB::table($botSettings['botTitle'])
                ->where('id', $recordId)
                ->update([
                    // -1 take a previous record
                    'accumulated_profit' => $temp[count($temp) - 1]->accumulated_profit + $lastRecord[0]->trade_profit

                ]);
        }
    }
}