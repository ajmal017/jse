<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/25/2019
 * Time: 2:12 PM
 */

namespace App\Classes\Trading;
use App\Classes\LogToFile;
use Illuminate\Support\Facades\DB;

/**
 * Profit calculation for signals table which contains real orders executions and prices.
 * Profit.php class - is used for back testing profit calculation.
 *
 * Class ProfitSignal
 * @package App\Classes\Trading
 */
class ProfitSignal
{
    private static $lastRow;
    private static $penUltimanteRow;
    private static $tradeCommissionValue;

    /* @todo remove this. Used only signal force finis is fired */
    private static $profit = 1;

    /**
     * Profit calculation. Profit is calculated between signals.
     * When on order is fully filed, the price where leavesVolume = 0 is copied to the signal row.
     * Also for more precise calculation an average from all executions can be used.
     * Now - we don't have it.
     *
     * @param $botId
     * @param $orderExecutionResponse
     */
    public static function calc($botId, $orderExecutionResponse){

        self::$penUltimanteRow = DB::table('signal_' . $botId)
            ->where('type', 'signal')
            ->where('status', 'closed')
            ->where('id', '!=', 1)
            ->get();

        $closedRows  = DB::table('signal_' . $botId)
            ->where('status', 'closed');

        /* Do not calculate profit for a first record in DB - this is a first position ever*/
        if (count(self::$penUltimanteRow) > 0){
            $lastRow = $closedRows
                ->get()
                ->last();
            $penultimateRow = $closedRows
                ->orderBy('id', 'desc')
                ->skip(1)
                ->take(1)
                ->get()[0];

            $direction = $lastRow->direction;

            if($direction == "buy"){
                dump('buy');
                if($orderExecutionResponse['symbol'] == 'XBTUSD'){
                    dump('FORMULA: BTC. ProfitSignal.php');
                    // BTC: 1 / (exit Price - entry Price) * volume
                    self::$profit = (1 / $lastRow->avg_fill_price - 1 / $penultimateRow->avg_fill_price) * $lastRow->signal_volume / 2;
                }
                if($orderExecutionResponse['symbol'] == 'ETHUSD'){
                    dump('FORMULA: ETH. ProfitSignal.php');
                    self::$profit = ($penultimateRow->avg_fill_price - $lastRow->avg_fill_price) * 0.000001 * $lastRow->signal_volume / 2;
                }
            }

            if($direction == "sell"){
                dump('sell');
                if($orderExecutionResponse['symbol'] == 'XBTUSD'){
                    dump('FORMULA: BTC. ProfitSignal.php');
                    self::$profit = (1 / $penultimateRow->avg_fill_price - 1 / $lastRow->avg_fill_price) * $lastRow->signal_volume / 2;
                }
                if($orderExecutionResponse['symbol'] == 'ETHUSD'){
                    dump('FORMULA: ETH. ProfitSignal.php');
                    self::$profit = ($lastRow->avg_fill_price - $penultimateRow->avg_fill_price) * 0.000001 * $lastRow->signal_volume / 2;
                }
            }

            /* Commission calculation */
            if ($orderExecutionResponse['symbol'] == 'XBTUSD')
                self::$tradeCommissionValue = 1 / $lastRow->avg_fill_price * $lastRow->signal_volume * $lastRow->trade_commission_percent;

            if ($orderExecutionResponse['symbol'] == 'ETHUSD')
                self::$tradeCommissionValue = $lastRow->avg_fill_price * 0.000001 * $lastRow->signal_volume * $lastRow->trade_commission_percent;

            /* Trade profit, comission update*/
            DB::table('signal_' . $botId)
                ->where('id', $lastRow->id)
                ->update([
                    'trade_profit' => self::$profit,
                    'trade_commission_value' => self::$tradeCommissionValue
                ]);

            /* Accumulated profit */
            DB::table('signal_' . $botId)
                ->where('id', $lastRow->id)
                ->update([
                    'accumulated_profit' => DB::table('signal_' . $botId)->sum('trade_profit'),
                    'accumulated_commission' => DB::table('signal_' . $botId)->sum('trade_commission_value')
                ]);

            /* Net profit */
            DB::table('signal_' . $botId)
                ->where('id', $lastRow->id)
                ->update([
                    'net_profit' => DB::table('signal_' . $botId)->sum('trade_profit') -
                        DB::table('signal_' . $botId)->sum('trade_commission_value')
                ]);


        }


    }

    public function finish(){
        /**
         * Do not calculate profit if there are no trades.
         * If trade_flag is set to all, it means that no trades have been executed yet.
         */
        if ($this->trade_flag != "all") {
            \App\Classes\Accounting\AccumulatedProfit::calculate($this->botSettings, $this->lastRow[0]->id);
            //\App\Classes\Accounting\NetProfit::calculate($this->position, $this->botSettings, $this->lastRow[0]->id);
        }
    }
}