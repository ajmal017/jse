<?php

namespace App\Http\Controllers\Exchange;
use Illuminate\Support\Facades\DB;

/**
 * This controller is called from DashboardChart.vue, Chart.vue and several other components.
 * It returns chart plot data collections and financial information like net_profit.
 *
 * Class HistoryBars
 * @package App\Http\Controllers\Exchange
 * @return array
 */
class HistoryBars extends \App\Http\Controllers\Controller
{
    public function load($botId){
        $candles = array();
        $priceChannelHighValues = array();
        $priceChannelLowValues = array();
        $longTradeMarkers = array();
        $sma1 = array();
        $macdLine = array();
        $macdSignalLine = array();
        $shortTradeMarkers = array();
        $netProfit = [];
        $accumulatedProfit = [];
        $seriesData = [];

        $executionLongMarkers = [];
        $executionShortMarkers = [];

        $allDbValues = DB::table('bot_' . $botId)->get();
        foreach ($allDbValues as $rowValue) { // Go through all DB records
            $candles[] = [
                $rowValue->time_stamp,
                $rowValue->open,
                $rowValue->high,
                $rowValue->low,
                $rowValue->close,
            ];
            $priceChannelHighValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_high_value
            ];
            $priceChannelLowValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_low_value
            ];
            // Long trade markers
            if ($rowValue->trade_direction == "buy") {
                $longTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }
            // Short trade markers
            if ($rowValue->trade_direction == "sell") {
                $shortTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }
            $sma1[] = [
                $rowValue->time_stamp,
                $rowValue->sma1
            ];
            $accumulatedProfit[] = [
                $rowValue->time_stamp,
                $rowValue->accumulated_profit
            ];
            $netProfit[] = [
                $rowValue->time_stamp,
                $rowValue->net_profit
            ];
            $macdLine[] = [
                $rowValue->time_stamp,
                $rowValue->macd_line
            ];
            $macdSignalLine[] = [
                $rowValue->time_stamp,
                $rowValue->macd_signal_line
            ];

            // Get all rows from signal_ . $botId
            // Make first collection where direction = Buy
            // Make second collection where direction == sell

            /* Execution long markers */
            /*$executionLongMarkers = DB::table('signal_' . $botId)
                ->where('type', 'signal')
                ->where('direction', 'buy')
                ->get();

            $executionLongMarkers = DB::table('signal_' . $botId)
                ->where('type', 'signal')
                ->where('direction', 'sell')
                ->get();*/


            $executions = DB::table('signal_' . $botId)->get();
            foreach ($executions as $execution) {
                if($execution->direction == 'buy' && $execution->type == 'signal')
                    $executionLongMarkers[] = [
                        $execution->time_stamp,
                        $execution->avg_fill_price
                    ];
                if($execution->direction == 'sell' && $execution->type == 'signal')
                    $executionShortMarkers[] = [
                        $execution->time_stamp,
                        $execution->avg_fill_price
                    ];
            }


            /* Execution short markers */
        }
        if ($allDbValues->count() != 0)
            $seriesData = array(
                "candles" => $candles,
                "priceChannelHighValues" => $priceChannelHighValues,
                "priceChannelLowValues" => $priceChannelLowValues,
                "longTradeMarkers" => $longTradeMarkers,
                "shortTradeMarkers" => $shortTradeMarkers,
                "sma1" => $sma1,
                "accumulatedProfit" => $accumulatedProfit,
                "netProfit" => $netProfit,
                "macdLine" => $macdLine,
                "macdSignalLine" => $macdSignalLine,
                "symbol" => $allDbValues[0]->symbol,
                "rawTable" => $allDbValues,
                'executionLongMarkers' => $executionLongMarkers,
                'executionShortMarkers' => $executionShortMarkers
            );
        return json_encode($seriesData);
    }
}
