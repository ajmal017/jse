<?php

namespace App\Classes\Backtesting;
use App\Classes\LogToFile;
use Illuminate\Support\Facades\DB;

/**
 * Class Backtest
 * This backtesting class is called from front end, not from command line.
 * This class takes historical bars loaded from www.bitfinex.com one by one
 * and calculates profit. Calculated profit, positions, accumulated profit are recorded to DB.
 * This class simulates real ticks coming from the exchange. In this case only one tick per bar will be generated - close.
 *
 * @package App\Classes
 */
class BacktestingFront
{
    static public function start($botSettings){
        /* Empty backtesting bars table */
        DB::table('bot_5')->truncate();

        \App\Classes\Trading\History::loadPeriod($botSettings);

        if ($botSettings['strategy'] == 'pc'){
            \App\Classes\Indicators\PriceChannel::calculate($botSettings[
                'strategyParams']['priceChannelPeriod'],
                $botSettings['botTitle'],
                true);

            \App\Classes\Indicators\Sma::calculate('close', 2, 'sma1', $botSettings['botTitle'], true);
            $chart = new \App\Classes\Trading\Chart($botSettings);
        }

        if ($botSettings['strategy'] == 'macd'){
            \App\Classes\Indicators\Macd::calculate($macdSettings = [
                'ema1Period' => $botSettings['strategyParams']['emaPeriod'],
                'ema2Period' => $botSettings['strategyParams']['macdLinePeriod'],
                'ema3Period' => $botSettings['strategyParams']['macdSignalLinePeriod']],
                $botSettings, true);
            // @todo 25.05.19 SEND ONE OBJECT! NOT 3 PARAMS!
            $macd = new \App\Classes\Trading\MacdTradesTrigger($botSettings);
        }

        /** Empty calculated data like position, profit, accumulated profit, etc */
        DB::table($botSettings['botTitle'])
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

        $allDbValues = DB::table($botSettings['botTitle'])->get();

        $isFirstRecord = false;
        foreach ($allDbValues as $rowValue) { // Go through all DB records
             /**
              * We need to pass the first bar. It is needed to avoid null price channel trade check because
              * in Chart.php the penultimate value of the price channel is taken for calculation
              * for the first iteration of foreach this value is always null
              */
            if ($isFirstRecord){
                if ($botSettings['strategy'] == 'pc'){
                    $chart->index("backtest", $rowValue->id);
                }
                if ($botSettings['strategy'] == 'macd'){
                    $macd->index("backtest", $rowValue->id);
                }
            }
            else{
                $isFirstRecord = true;
            }
        }
    }
}