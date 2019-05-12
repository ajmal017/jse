<?php

namespace App\Http\Controllers\Exchange;

use App\Console\Commands\Pc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryBars extends \App\Http\Controllers\Controller
{
    public function load(){
        $candles = array();
        $priceChannelHighValues = array();
        $priceChannelLowValues = array();
        $longTradeMarkers = array();
        $sma1 = array();
        $macdLine = array();
        $macdSignalLine = array();

        $shortTradeMarkers = array();
        $allDbValues = DB::table("asset_1")->get(); // Read the whole table from BD to $allDbValues

        foreach ($allDbValues as $rowValue) { // Go through all DB records
            $candles[] = [
                $rowValue->time_stamp,
                $rowValue->open,
                $rowValue->high,
                $rowValue->low,
                $rowValue->close,
            ];

            //$rowValue->price_channel_high_value,
            //$rowValue->price_channel_low_value

            $priceChannelHighValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_high_value
            ];

            $priceChannelLowValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_low_value
            ];

            // Add long trade markers
            if ($rowValue->trade_direction == "buy") {
                $longTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }

            // Add short trade markers
            if ($rowValue->trade_direction == "sell") {
                $shortTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }

            // Add SMA
            $sma1[] = [
                $rowValue->time_stamp,
                $rowValue->sma1
            ];

            // Add profit diagram
            $profitDiagram[] = [
                $rowValue->time_stamp,
                //$rowValue->net_profit
                $rowValue->accumulated_profit // Profit diagram without commission
            ];

            // Add MACD line
            $macdLine[] = [
                $rowValue->time_stamp,
                $rowValue->macd_line
            ];

            // Add MACD signal line
            $macdSignalLine[] = [
                $rowValue->time_stamp,
                $rowValue->macd_signal_line
            ];

            // Add Symbol name
            $symbol = "sampleSymbol_History_Bars.php";

        }

        $seriesData = array(
            "candles" => $candles,
            "priceChannelHighValues" => $priceChannelHighValues,
            "priceChannelLowValues" => $priceChannelLowValues,
            "longTradeMarkers" => $longTradeMarkers,
            "shortTradeMarkers" => $shortTradeMarkers,
            "sma1" => $sma1,
            "profitDiagram" => $profitDiagram,
            "macdLine" => $macdLine,
            "macdSignalLine" => $macdSignalLine,
            "symbol" => $allDbValues[0]->symbol
        );

        //              0                   1                       2                   3                   4              5
        //$seriesData = [$candles, $priceChannelHighValue, $priceChannelLowValue, $longTradeMarkers, $shortTradeMarkers, $sma];
        return json_encode($seriesData);
    }
}
