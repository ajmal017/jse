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
    private static $chart;
    private static $macd;

    static public function start($botSettings){
        if ($botSettings['strategy'] == 'pc'){
            \App\Classes\Indicators\PriceChannel::calculate($botSettings[
                'strategyParams']['priceChannelPeriod'],
                $botSettings['botTitle'],
                true);

            \App\Classes\Indicators\Sma::calculate('close', 2, 'sma1', $botSettings['botTitle'], true);
            self::$chart = new \App\Classes\Trading\Chart($botSettings);
        }

        if ($botSettings['strategy'] == 'macd'){
            \App\Classes\Indicators\Macd::calculate($macdSettings = [
                'ema1Period' => $botSettings['strategyParams']['emaPeriod'],
                'ema2Period' => $botSettings['strategyParams']['macdLinePeriod'],
                'ema3Period' => $botSettings['strategyParams']['macdSignalLinePeriod']],
                $botSettings, true);

            self::$macd = new \App\Classes\Trading\MacdTradesTrigger($botSettings);
        }

        /* Empty calculated data like position, profit, accumulated profit, etc */
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
        foreach ($allDbValues as $rowValue) {
             /**
              * We need to pass the first bar. It is needed to avoid null price channel trade check because
              * in Chart.php the penultimate value of the price channel is taken for calculation
              * for the first iteration of foreach this value is always null
              */
            if ($isFirstRecord){
                if ($botSettings['strategy'] == 'pc'){
                    self::$chart->index("backtest", $rowValue->id);
                }
                if ($botSettings['strategy'] == 'macd'){
                    self::$macd->index("backtest", $rowValue->id);
                }
            }
            else{
                $isFirstRecord = true;
            }
        }
    }
}