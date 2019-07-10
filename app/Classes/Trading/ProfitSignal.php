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

            //echo "Last row: " . $lastRow->id . " ";
            //echo " Penultimate row: " . $penultimateRow->id . " \n";

            $direction = $lastRow->direction;

            if($direction == "buy"){
                dump('buy');
                if($orderExecutionResponse['symbol'] == 'XBTUSD'){
                    dump('FORMULA: BTC. ProfitSignal.php');
                    // BTC: 1 / (exit Price - entry Price) * volume
                    $profit = $penultimateRow->avg_fill_price - $lastRow->avg_fill_price;
                }
                if($orderExecutionResponse['symbol'] == 'ETHUSD'){
                    
                    dump('FORMULA: ETH. ProfitSignal.php');
                    $profit = $penultimateRow->avg_fill_price - $lastRow->avg_fill_price;
                }
            } else {
                dump('sell');
                if($orderExecutionResponse['symbol'] == 'XBTUSD'){
                    dump('FORMULA: BTC. ProfitSignal.php');
                    $profit = $lastRow->avg_fill_price - $penultimateRow->avg_fill_price;
                }
                if($orderExecutionResponse['symbol'] == 'ETHUSD'){
                    dump('FORMULA: ETH. ProfitSignal.php');
                    $profit = $lastRow->avg_fill_price - $penultimateRow->avg_fill_price;
                }
            }

            dump($profit);

            DB::table('signal_' . $botId)
                ->where('id', $lastRow->id)
                ->update([
                    'trade_profit' => $profit
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