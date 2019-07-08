<?php

namespace App\Http\Controllers;

use App\Classes\Backtesting\BacktestingFront;
use App\Classes\LogToFile;
use Illuminate\Http\Request;

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

        //LogToFile::add('BacktestController', json_encode($request));
        //return($request);
        //die();

        $strategy = $request['strategy'];

        if ($strategy == 'pc')
            $botSettings = [
                'botTitle' => 'bot_5',
                'executionSymbolName' => $request['execution_symbol_name'], // ETH/USD
                'historySymbolName' => $request['history_symbol_name'], // ETHUSD
                'volume' => 10,
                'commission' => -0.0250, // Taker: 0.0750
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
                'volume' => 10,
                'commission' => -0.0250, // Taker: 0.0750
                'strategy' => 'macd',
                'strategyParams' => [
                    'emaPeriod' => 2,
                    'macdLinePeriod' => 5,
                    'macdSignalLinePeriod' => 5
                ],
                'timeFrame' => $request['bar_time_frame'], // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
                'barsToLoad' => $request['bars_to_load'],
                'frontEndId' => '12350',
            ];

        BacktestingFront::start($botSettings);
        // reload chart goes here. exclude reload chart method from pc, mc to a separate class. located in trading. name: Chart::reload
        // Rename Chart.php to PcTradesTrigger

        // @todo Exclude to a separate class
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
