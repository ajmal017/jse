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
 * MACD signal line = SMA(MACD). In original formula it is EMA nor SMA.
 *
 * @package App\Classes\Indicators
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_average_convergence_divergence_macd
 * @see https://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:moving_averages
 */
class Macd
{
    public static function calculate($macdSettings, $botSettings, $isInitialCalculation){
        Sma::calculate('close',$macdSettings['ema1Period'], 'sma1', $botSettings['botTitle'], $isInitialCalculation); // SMA1
        Sma::calculate('close',$macdSettings['ema2Period'], 'sma2', $botSettings['botTitle'], $isInitialCalculation); // SMA2

        Ema::calculate('close', $macdSettings['ema1Period'], 'sma1', 'ema1', $botSettings['botTitle'], $isInitialCalculation); // EMA1
        Ema::calculate('close', $macdSettings['ema2Period'], 'sma2', 'ema2', $botSettings['botTitle'], $isInitialCalculation); // EMA2



        /* @var int $quantityOfBars The quantity of bars for which the price channel will be calculated */
        if ($isInitialCalculation){
            $quantityOfBars = (DB::table($tableName)
                    ->orderBy('id', 'desc')
                    ->first())->id - $priceChannelPeriod - 1;
        } else {
            $quantityOfBars = $priceChannelPeriod;
        }


        $bars = DB::table($botSettings['botTitle'])
            ->where('ema2','!=', null)
            ->orderBy('time_stamp', 'asc') // desc, asc - order. Read the whole table from BD to $records
            ->get();

        foreach ($bars as $bar){
            DB::table($botSettings['botTitle'])
                ->where('time_stamp', $bar->time_stamp)
                ->update([
                    'macd_line' => DB::table($botSettings['botTitle'])->where('id', $bar->id)->value('ema1') -
                        DB::table($botSettings['botTitle'])->where('id', $bar->id)->value('sma2')
                ]);
        }

        Sma::calculate('macd_line', $macdSettings['ema3Period'], 'macd_signal_line', $botSettings['botTitle'], $isInitialCalculation); // MACD signal line as SMA from MACD line
    }
}