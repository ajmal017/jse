<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/27/2019
 * Time: 5:35 PM
 */

namespace App\Classes\Indicators;
use Illuminate\Support\Facades\DB;

class Ema
{
    private static $emaValue;
    private static $multiplier;

    /**
     * $period
     * $closeColumn - price input values for calculation. Can be close or EMA (when MACD signal line needs to be calculated)
     * $smaColumn - values for calculation
     * $emaColumn - output column
     *
     * @param $close
     * @param $period
     * @param $smaColumn
     * @param $emaColumn
     */
    public static function calculate($close, $period, $smaColumn, $emaColumn){
        self::$multiplier = (2 / ($period + 1));
        $bars = DB::table('asset_1')
            ->where($smaColumn,'!=', null)
            ->orderBy('time_stamp', 'asc') // desc, asc - order. Read the whole table from BD to $records
            ->get();

        $isFirstValue = true;

        foreach ($bars as $bar){
            if($isFirstValue){
                self::$emaValue = DB::table('asset_1')->where('id', $bar->id)->value($smaColumn);
                $isFirstValue = false;
            } else{
                $ema1Penultimate = DB::table('asset_1')->where('id', $bar->id-1)->value($emaColumn);
                self::$emaValue = self::$multiplier * ($bar->$close - $ema1Penultimate) + $ema1Penultimate;
            }

            DB::table("asset_1")
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    $emaColumn => self::$emaValue,
                ]);
        }
    }
}