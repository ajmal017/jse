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
     * @param float $close
     * @param int $period
     * @param string $smaColumn             To which column in DB the result will be written
     * @param string $emaColumn                    ..
     * @param string $table                 Table name
     * @param bool $isInitialCalculation    Represents whether the indicator is calculated for the first time or on bar update.
     */
    public static function calculate($close, $period, $smaColumn, $emaColumn, $table, $isInitialCalculation){
        self::$multiplier = (2 / ($period + 1));
        /* @var int $quantityOfBars The quantity of bars for which the price channel will be calculated */
        /*if ($isInitialCalculation){
            $bars = DB::table($table)
                ->where($smaColumn,'!=', null)
                ->orderBy('time_stamp', 'asc')
                ->take(100)
                ->get();
        } else {
            $bars = DB::table($table)
                ->where($smaColumn,'!=', null)
                ->orderBy('time_stamp', 'asc')
                ->take($period)
                ->get();
        }*/

        $bars = DB::table($table)
            ->orderBy('time_stamp', 'asc')
            ->where($smaColumn,'!=', null)
            //->take(100)
            ->get();

        $isFirstValue = true;

        foreach ($bars as $bar){

            if($isFirstValue){
                self::$emaValue = DB::table($table)->where('id', $bar->id)->value($smaColumn);
                $isFirstValue = false;
            } else{
                $ema1Penultimate = DB::table($table)->where('id', $bar->id-1)->value($emaColumn);
                self::$emaValue = self::$multiplier * ($bar->$close - $ema1Penultimate) + $ema1Penultimate;
            }

            DB::table($table)
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    $emaColumn => self::$emaValue,
                ]);
        }
    }
}