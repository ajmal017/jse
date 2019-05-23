<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/22/2019
 * Time: 3:39 PM
 */

namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;
/**
 * Calculate net profit.
 * Take last row, get accumulated profit and accumulated commission out of it.
 * Subtract.
 *
 * Class NetProfit
 * @package App\Classes\Accounting
 */
class NetProfit
{
    public static function calculate($position, $botSettings, $recordId){
        if ($position != null){

            $accumulatedProfit =
                DB::table($botSettings['botTitle'])
                    ->where('id', $recordId)
                    ->value('accumulated_profit');

            $accumulatedCommission =
                DB::table($botSettings['botTitle'])
                    ->whereNotNull('accumulated_commission')
                    ->orderBy('id', 'desc')
                    ->value('accumulated_commission');

            DB::table($botSettings['botTitle'])
                ->where('id', $recordId)
                ->update([
                    // net profit = accum_profit - last accum_commission
                    'net_profit' => round($accumulatedProfit - $accumulatedCommission, 4)
                ]);
        }
    }
}