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

abstract class Profit
{
    public function calc($mode, $backTestRowId){
        /**
         * Backtest mode:
         * Bars are loaded into the DB and then read one by one in Backtesting.php and sent here.
         * $mode = 'backtest', $backTestRowId = current record id.
         *
         * Realtime mode:
         * Bars are created in CandleMaker.php, index($mode = null, $backTestRowId = null) is called once per time frame.
         */
        if ($mode == "backtest")
        {
            /**
             * @var int $recordId id of the record in DB. Generated in Backtesting.php
             * In backtest mode id is sent as a parameter. In realtime - pulled from DB.
             */
            $this->lastRow = DB::table($this->botSettings['botTitle'])->where('id', $backTestRowId)->get();
        }
        else /* Realtime */
        {
            /* No record id is sent in real time mode. We get the last record from the DB. */
            $this->lastRow = DB::table($this->botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->get();
        }

        /**
         * Backtest mode: we get the record from the DB accoringly to ID received from BackTesting.php
         * Realtime mode: we use not ID, we just get the last record from the DB.
         */
        $backTestRowId = $this->lastRow[0]->id;
        $this->penUltimanteRow = DB::table($this->botSettings['botTitle'])->where('id', $this->lastRow[0]->id - 1)->get()->first();

        /**
         * Do not calculate profit if there is no open position. If do not do this check - zeros in table occur
         * $this->trade_flag != "all" if it is "all" - it means that it is a first or initial start
         * We do not store position in DB thus we use "all" check to determine a position absence
         * if "all" - no position has been opened yet
         */
        if ($this->position != null && $this->trade_flag != "all"){
            /* Get the price of the last trade */
            $lastTradePrice =
                DB::table($this->botSettings['botTitle'])
                    ->whereNotNull('trade_price')
                    ->orderBy('id', 'desc') // Form biggest to smallest values
                    ->value('trade_price');

            $this->tradeProfit =
                (($this->position == "long" ? ($this->lastRow[0]->close - $lastTradePrice) * $this->volume : ($lastTradePrice - $this->lastRow[0]->close) * $this->volume));

            \App\Classes\Accounting\TradeProfit::calculate($this->botSettings, $this->tradeProfit, $backTestRowId);
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