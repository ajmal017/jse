<?php

namespace App\Classes\Backtesting;
use Illuminate\Support\Facades\DB;

/**
 * Class Backtest
 * This class takes historical bars loaded from www.bitfinex.com one by one
 * and calculates profit. Calculated profit, positions, accumulated profit are recorded to DB.
 * This class simulates real ticks coming from the exchange. In this case only one tick per bar will be generated - close.
 *
 * @package App\Classes
 */
class Backtesting
{
    static public function start($botSettings){
        /* Empty history bars table */
        $migration = new \App\Classes\DB\TradingTable($botSettings['botTitle']);
        $migration->down();
        $migration->up();

        \App\Classes\Trading\History::loadPeriod($botSettings);

        \App\Classes\Indicators\PriceChannel::calculate($botSettings['strategyParams']['priceChannelPeriod'], $botSettings['botTitle'], true);
        \App\Classes\Indicators\Sma::calculate('close', 2, 'sma1', $botSettings['botTitle'], true);

        $chart = new \App\Classes\Trading\Chart($botSettings['executionSymbol'], $botSettings['volume'], $botSettings);

        /** Empty calculated data like position, profit, accumulated profit, etc */
        DB::table($botSettings['botTitle'])
            //->whereNotNull('net_profit')
            ->whereNotNull('price_channel_high_value')
            ->update([
                'trade_date' => null,
                'trade_price' => null,
                'trade_commission' => null,
                'accumulated_commission' => null,
                'trade_direction' => null,
                'trade_volume' => null,
                'trade_profit' => null,
                'accumulated_profit' => null,
                'net_profit' => null,
            ]);

        $allDbValues = DB::table($botSettings['botTitle'])
            ->whereNotNull('price_channel_high_value') // We don't need bars without price channel
            ->get();

        $isFirstRecord = false;
        foreach ($allDbValues as $rowValue) { // Go through all DB records
            /** We need to pass the first bar. It is needed to avoid null price channel trade check because
             * in Chart.php the penultimate value of the price channel is taken for calculation
             * for the first iteration of foreach this value is always null
             */
            if ($isFirstRecord){
                // Used bar close crossing price channel
                //$chart->index("backtest", $rowValue->date, $rowValue->time_stamp, $rowValue->close, $rowValue->id);
                $chart->index("backtest", $rowValue->id);
            }
            else{
                $isFirstRecord = true;
            }
        }
    }
}