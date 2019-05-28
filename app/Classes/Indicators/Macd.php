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
 * Calculates MACD indicator
 * MACD = EMA1 - EMA2
 * MACD signal line = SMA(MACD). In original formula it is EMA nor SMA
 * Can be calculated in teo cased:
 * 1. Initial run / backtest - all bars from DB are used.
 * 2. Live trading - only 'period' bars are used. If a period = 10, 10 bars from the oldest bar will be used.
 * This approach is used in order not to recalculate the whole history on each bar. This overloads CPU.
 * @package App\Classes\Indicators
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_convergence_divergence_macd
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_averages
 */
class Macd
{
    public static function calculate($macdSettings, $botSettings, $isInitialCalculation){
        Sma::calculate('close',$macdSettings['ema1Period'], 'sma1', $botSettings['botTitle'], $isInitialCalculation); // SMA1
        Sma::calculate('close',$macdSettings['ema2Period'], 'sma2', $botSettings['botTitle'], $isInitialCalculation); // SMA2
        // Where MACD signal line?
        Ema::calculate('close', $macdSettings['ema1Period'], 'sma1', 'ema1', $botSettings['botTitle'], $isInitialCalculation); // EMA1
        Ema::calculate('close', $macdSettings['ema2Period'], 'sma2', 'ema2', $botSettings['botTitle'], $isInitialCalculation); // EMA2

        /* @var int $quantityOfBars The quantity of bars for which the price channel will be calculated */
        if ($isInitialCalculation){
            /*$bars = (DB::table($botSettings['botTitle'])
                    ->orderBy('id', 'desc')
                    ->first())->id - $priceChannelPeriod - 1;*/

            $bars = DB::table($botSettings['botTitle'])
                ->where('ema2','!=', null)
                ->orderBy('time_stamp', 'asc') // desc, asc - order. Read the whole table from BD to $records
                ->get();

        } else {
            $bars = $macdSettings['ema3Period'];
        }

        /* MACD line calculation */
        foreach ($bars as $bar){
            DB::table($botSettings['botTitle'])
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    'macd_line' => DB::table($botSettings['botTitle'])->where('id', $bar->id)->value('ema1') -
                        DB::table($botSettings['botTitle'])->where('id', $bar->id)->value('sma2')
                ]);
        }
        /* MACD signal line calculation */
        Sma::calculate('macd_line', $macdSettings['ema3Period'], 'macd_signal_line', $botSettings['botTitle'], $isInitialCalculation); // MACD signal line as SMA from MACD line
    }
}