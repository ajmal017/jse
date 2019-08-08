<?php

namespace App\Http\Controllers;

use App\Classes\Backtesting\BacktestingFront;
use App\Classes\LogToFile;
use App\Classes\Trading\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class BacktestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $strategy = $request['strategy'];

        /**
         * Load step history.
         * This is not a strategy.
         */
        if($strategy == 'historyStep') {
            $botSettings = [
                'botTitle' => 'bot_5', // Back testing table
                'executionSymbolName' => $request['execution_symbol_name'], // ETH/USD
                'historySymbolName' => $request['history_symbol_name'], // ETHUSD
                'timeFrame' => $request['bar_time_frame']
            ];
            return(History::loadStep($botSettings));
        }

        /* Truncate history table */
        if($strategy == 'truncate') {
            DB::table('bot_5')->truncate();
            return([
                'barsLoaded' => 0,
                'startDate' => 'none',
                'endDate' => 'none'
            ]);
        }

        if ($strategy == 'pc')
            $botSettings = [
                'botTitle' => 'bot_5', // Back testing table
                'executionSymbolName' => $request['execution_symbol_name'], // ETH/USD
                'historySymbolName' => $request['history_symbol_name'], // ETHUSD
                'volume' => $request['volume'],
                'commission' => $request['commission'] / 100, // Maker: -0.00025, Taker: 0.000750 - such values come from Bitmex
                'strategy' => 'pc',
                'strategyParams' => [
                    'priceChannelPeriod' => $request['time_frame']
                ],
                'timeFrame' => $request['bar_time_frame'], // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
                'barsToLoad' => $request['bars_to_load'],
                'frontEndId' => '12350',
            ];

        if ($strategy == 'macd')
            $botSettings = [
                'botTitle' => 'bot_5',
                'executionSymbolName' => $request['execution_symbol_name'], // ETH/USD
                'historySymbolName' => $request['history_symbol_name'], // ETHUSD
                'volume' => $request['volume'],
                'commission' => $request['commission'] / 100, // Taker: 0.000750
                'strategy' => 'macd',
                'strategyParams' => [
                    'emaPeriod' => $request['ema_period'],
                    'macdLinePeriod' => $request['macd_line_period'],
                    'macdSignalLinePeriod' => $request['macd_signalline_period']
                ],
                'timeFrame' => $request['bar_time_frame'], // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
                'barsToLoad' => $request['bars_to_load'],
                'frontEndId' => '12350',
            ];

        /* Load history and run back tester */
        BacktestingFront::start($botSettings);

        /* @todo Exclude to a separate class */
        $pusherApiMessage = new \App\Classes\WebSocket\PusherApiMessage();
        $pusherApiMessage->clientId = $botSettings['frontEndId'];
        $pusherApiMessage->messageType = 'reloadChartAfterHistoryLoaded';

        try{
            event(new \App\Events\jseevent($pusherApiMessage->toArray()));
        } catch (\Exception $e)
        {
            echo __FILE__ . " " . __LINE__ . "\n";
            dump($e);
        }

        /* Show pop up message at the front end with back tester result in it */
        $lastRow =
            DB::table($botSettings['botTitle'])
                ->orderBy('id', 'desc')->take(1)
                ->get();

        $accumulated_commission =
            DB::table($botSettings['botTitle'])
                ->orderBy('id', 'desc')->take(1)
                ->where('trade_price', '!=', null)
                ->value('accumulated_commission');

        $trades_quantity =
            DB::table($botSettings['botTitle'])
                ->where('trade_price', '!=', null)
                ->count();

        try{
            event(new \App\Events\jseevent([
                'clientId' => $botSettings['frontEndId'],
                'messageType' => 'backTestingResult',
                'payload' => [
                    'netProfit' => $lastRow[0]->net_profit,
                    'trades' => $trades_quantity,
                    'accumulatedCommission' => $accumulated_commission
                ]
            ]));
        } catch (\Exception $e)
        {
            throw new Exception($e);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
