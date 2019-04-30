<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/27/2019
 * Time: 5:35 PM
 */

namespace App\Classes\Indicators;
use Illuminate\Support\Facades\DB;
/**
 * Class Macd
 *
 * MACD = EMA1 - EMA2
 * MACD signal line = EMA9 (MACD)
 *
 * @package App\Classes\Indicators
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_convergence_divergence_macd
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_averages
 */
class Macd
{
    private static $ema1Value;
    private static $ema2Value;
    private static $multiplier;

    public static function calculate(){ // 12, 26, 9.
        $bars = DB::table('asset_1')
            ->where('ema2','!=', null)
            ->orderBy('time_stamp', 'asc') // desc, asc - order. Read the whole table from BD to $records
            ->get();

        foreach ($bars as $bar){
            DB::table("asset_1")
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    'macd_line' => DB::table('asset_1')->where('id', $bar->id)->value('ema1') -
                        DB::table('asset_1')->where('id', $bar->id)->value('sma2')
                ]);

        }

        /*$isFirstValue = true;
        foreach ($bars as $bar){
            if($isFirstValue){
                self::$ema1Value = DB::table('asset_1')->where('id', $bar->id)->value('sma1');
                self::$ema2Value = DB::table('asset_1')->where('id', $bar->id)->value('sma2');
                $isFirstValue = false;
            } else{
                // ema1
                $ema1Penultimate = DB::table('asset_1')->where('id', $bar->id-1)->value('ema1');
                self::$ema1Value = self::$multiplier * ($bar->close - $ema1Penultimate) + $ema1Penultimate;
                // ema2
                $ema2Penultimate = DB::table('asset_1')->where('id', $bar->id-1)->value('ema2');
                self::$ema2Value = self::$multiplier * ($bar->close - $ema2Penultimate) + $ema2Penultimate;
            }
            DB::table("asset_1")
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    'ema1' => self::$ema1Value,
                    'ema2' => self::$ema2Value
                ]);
        }*/




    }
}