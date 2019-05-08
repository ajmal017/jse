<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/18/2018
 * Time: 3:15 PM
 */

namespace App\Classes\Indicators;
use Illuminate\Support\Facades\DB;

class Sma
{
    /**
     * @param string $close         Based on which part of a bar the SMA should be calculated. open, close, high, low.
     * @param string $period        SMA period.
     * @param sting $smaColumn      Output DB columnt for SMA calculated values.
     */
    public static function calculate($close, $period, $smaColumn)
    {
        /* @var int $priceChannelPeriod */
        $priceChannelPeriod = $period;

        /* @var int $smaPeriod */
        $smaPeriod = $period;

        /**
         * @var int elementIndex Loop index. If the price channel period is 5 the loop will go from 0 to 4.
         * The loop is started on each candle while running through all candles in the array.
         */
        $elementIndex = 0;

        /** @var int $priceChannelHighValue Base value for high value search*/
        $priceChannelHighValue = 0;

        /** @var int $priceChannelLowValue Base value for low value search. Really big value is needed at the beginning.
        Then we compare current value with 999999. It is, $priceChannelLowValue = current value*/
        $priceChannelLowValue = 999999;

        /**
         * desc - from big values to small. asc - from small to big
         * in this case: desc. [0] element is the last record in DB. and it's id - quantity of records
         *
         * @var json object $records Contains all DB data (records) in json format
         * IT IS NOT A JSON! IT MOST LIKELY A LARAVEL OBJECT. BUTSCH SENT ME THE LINK.
         * @todo FIX WHAT BUTSH SAYS
         * https://laravel.com/docs/5.6/collections
         */
        $records = DB::table("asset_1")
            ->orderBy('time_stamp', 'desc')
            ->get(); // desc, asc - order. Read the whole table from BD to $records

        /** @var int $quantityOfBars The quantity of bars for which the price channel will be calculated */
        $quantityOfBars = (DB::table('asset_1')
                ->orderBy('id', 'desc')
                ->first())->id - $priceChannelPeriod - 1;

        /**
         * Calculate price channel max, min.
         * First element in the array is the oldest. Accordingly to the chart - we start from the right end.
         * Start from the oldest element in the array which is on the right at the chart. The one on the left at the chart
         */
        foreach ($records as $record) {

            /* @var double $sma Calculated simple moving average value sma value is reset each iteration */
            $sma = 0;
            /**
             * Indexes go like this 0,1,2,3,4,5,6 from left to the right
             * We must stop before $requestBars reaches the end of the array
             */
            if ($elementIndex <= $quantityOfBars)
            {
                // Go from right to left (from present to last bar)

                // For SMA
                for ($j = $elementIndex  ; $j < $elementIndex + $smaPeriod; $j++)
                {
                    /** SMA calculation */
                    $sma += $records[$j]->$close; // SMA based on close value
                }

                /** Update high and low values, sma values in DB */
                DB::table("asset_1")
                    ->where('time_stamp', $records[$elementIndex]->time_stamp)
                    ->update([
                        $smaColumn => $sma / $smaPeriod,
                    ]);

            }
            else
            {
                /** Update high and low values in DB for bars which were not used in calculation
                 *  There is a case when first price channel with period 5 is calculated
                 *  Then next price channel is calculated with period 6. This causes that calculated values from period 5
                 *  remain in DB and spoil the chart. The price channel lines start to contain both values in the same series.
                 *  In order to prevent this, for those bars that were not used for computation, price channel values are set to null
                 */

                DB::table("asset_1")
                    ->where('time_stamp', $records[$elementIndex]->time_stamp)
                    ->update([
                        $smaColumn => null
                    ]);

            }
            $elementIndex++;
        }
    }
}