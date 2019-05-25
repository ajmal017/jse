<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/25/2019
 * Time: 2:12 PM
 */

namespace App\Classes\Trading;
use Illuminate\Support\Facades\DB;

class Profit
{
    public function calc($mode, $backTestRowId){

        /**
         * Backtest mode:
         * Bars are loaded into the DB and then read one by one in Backtesting.php and sent here.
         * $mode = 'backtest', $backTestRowId = current record id.
         *
         * Realtime mode:
         * Bars are created in CandleMaker.php, index($mode = null, $backTestRowId = null) is called once per time frame.
         *
         */
        if ($mode == "backtest")
        {
            /**
             * @var int $recordId id of the record in DB. Generated in Backtesting.php
             * In backtest mode id is sent as a parameter. In realtime - pulled from DB.
             */
            //$recordId = $id; // In the real time mode there is no id sent. It is sent only in back test mode.
            $this->lastRow = DB::table($this->botSettings['botTitle'])->where('id', $backTestRowId)->get();
        }
        else /* Realtime */
        {
            /* No record id is sent in real time mode. We get the last record from the DB. */
//            $this->lastRow = DB::table($this->botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->get();
            $this->lastRow = DB::table($this->botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->get();
        }

        /**
         * Backtest mode: we get the record from the DB accoringly to ID received from BackTesting.php
         * Realtime mode: we use not ID, we just get the last record from the DB.
         */
        $lastRow = $this->lastRow;
        $backTestRowId = $this->lastRow[0]->id;

        $penUltimanteRow = DB::table($this->botSettings['botTitle'])->where('id', $lastRow[0]->id - 1)->get()->first();

        /**
         * Do not calculate profit if there is no open position. If do not do this check - zeros in table occur
         * $this->trade_flag != "all" if it is "all" - it means that it is a first or initial start
         * We do not store position in DB thus we use "all" check to determine a position absence
         * if "all" - no position has been opened yet
         */
        //if ($position != null && $this->trade_flag != "all"){
        if ($this->position != null && $this->trade_flag != "all"){
            /* Get the price of the last trade */
            $lastTradePrice =
                DB::table($this->botSettings['botTitle'])
                    ->whereNotNull('trade_price')
                    ->orderBy('id', 'desc') // Form biggest to smallest values
                    ->value('trade_price');
            $this->tradeProfit = (($this->position == "long" ? ($lastRow[0]->close - $lastTradePrice) * $this->volume : ($lastTradePrice - $lastRow[0]->close) * $this->volume));
            \App\Classes\Accounting\TradeProfit::calculate($this->botSettings, $this->tradeProfit, $backTestRowId);
        }

        return $lastRow;
    }
}