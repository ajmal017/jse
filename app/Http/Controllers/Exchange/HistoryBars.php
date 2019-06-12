<?php

namespace App\Http\Controllers\Exchange;

//use App\Console\Commands\Pc;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                //$rowValue->net_profit
                $rowValue->accumulated_profit // Profit diagram without commission
                //$rowValue->trade_profit
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

            // Add Symbol name
            //$symbol = "sampleSymbol_History_Bars.php";

        }

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
            "rawTable" => $allDbValues
        );

        return json_encode($seriesData);
    }
}
