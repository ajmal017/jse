<?php

namespace App\Console\Commands;

use App\Classes\DB\SignalTable;
use App\Job;
use App\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TestProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testprofit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::table('signal_1')->truncate();

        $execution1 =
            [
                "info" => [
                    "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc6c013",
                    "price" => 12000,
                ],
                "execID" => "1a2636be-f81b-43a2-f797-c7a8de0e2785",
                "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc6c013",
                "clOrdID" => "",
                "clOrdLinkID" => "",
                "account" => 207325,
                "symbol" => "XBTUSD",
                "side" => "Buy",
                "lastQty" => 1,
                "lastPx" => 12378,
                "underlyingLastPx" => null,
                "lastMkt" => "XBME",
                "lastLiquidityInd" => "RemovedLiquidity",
                "simpleOrderQty" => null,
                "orderQty" => 1,
                "price" => 12000,
                "displayQty" => null,
                "stopPx" => null,
                "pegOffsetValue" => null,
                "pegPriceType" => "",
                "currency" => "USD",
                "settlCurrency" => "XBt",
                "execType" => "Trade",
                "ordType" => "Limit",
                "timeInForce" => "GoodTillCancel",
                "execInst" => "",
                "contingencyType" => "",
                "exDestination" => "XBME",
                "ordStatus" => "Filled",
                "triggered" => "",
                "workingIndicator" => false,
                "ordRejReason" => "",
                "simpleLeavesQty" => null,
                "leavesQty" => 0,
                "simpleCumQty" => null,
                "cumQty" => 1,
                "avgPx" => 12000,
                "commission" => -0.025,
                "tradePublishIndicator" => "PublishTrade",
                "multiLegReportingType" => "SingleSecurity",
                "text" => "",
                "trdMatchID" => "a26d1c1a-31c2-94b3-6c33-6ef616cf6a58",
                "execCost" => -8079,
                "execComm" => 6,
                "homeNotional" => 8.079E-5,
                "foreignNotional" => -1,
                "transactTime" => "2019-07-10T15:18:57.449Z",
                "timestamp" => "1562771937001"
            ];
        $execution2 =
            [
                "info" => [
                    "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc6124",
                    "price" => 13000,
                ],
                "execID" => "1a2636be-f81b-43a2-f797-c7a8de0e7777",
                "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc6124",
                "clOrdID" => "",
                "clOrdLinkID" => "",
                "account" => 207325,
                "symbol" => "XBTUSD",
                "side" => "Sell",
                "lastQty" => 1,
                "lastPx" => 12378,
                "underlyingLastPx" => null,
                "lastMkt" => "XBME",
                "lastLiquidityInd" => "RemovedLiquidity",
                "simpleOrderQty" => null,
                "orderQty" => 1,
                "price" => 13000,
                "displayQty" => null,
                "stopPx" => null,
                "pegOffsetValue" => null,
                "pegPriceType" => "",
                "currency" => "USD",
                "settlCurrency" => "XBt",
                "execType" => "Trade",
                "ordType" => "Limit",
                "timeInForce" => "GoodTillCancel",
                "execInst" => "",
                "contingencyType" => "",
                "exDestination" => "XBME",
                "ordStatus" => "Filled",
                "triggered" => "",
                "workingIndicator" => false,
                "ordRejReason" => "",
                "simpleLeavesQty" => null,
                "leavesQty" => 0,
                "simpleCumQty" => null,
                "cumQty" => 1,
                "avgPx" => 13000,
                "commission" => -0.025,
                "tradePublishIndicator" => "PublishTrade",
                "multiLegReportingType" => "SingleSecurity",
                "text" => "",
                "trdMatchID" => "a26d1c1a-31c2-94b3-6c33-6ef616cf6a58",
                "execCost" => -8079,
                "execComm" => 6,
                "homeNotional" => 8.079E-5,
                "foreignNotional" => -1,
                "transactTime" => "2019-07-10T15:18:57.449Z",
                "timestamp" => "1562771937002"
            ];
        $execution3 =
            [
                "info" => [
                    "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc8888",
                    "price" => 11000,
                ],
                "execID" => "1a2636be-f81b-43a2-f797-c7a8de0e8888",
                "orderID" => "c54a781d-7168-dbf2-d99d-72dc7fc8888",
                "clOrdID" => "",
                "clOrdLinkID" => "",
                "account" => 207325,
                "symbol" => "XBTUSD",
                "side" => "Sell",
                "lastQty" => 1,
                "lastPx" => 12378,
                "underlyingLastPx" => null,
                "lastMkt" => "XBME",
                "lastLiquidityInd" => "RemovedLiquidity",
                "simpleOrderQty" => null,
                "orderQty" => 1,
                "price" => 11000,
                "displayQty" => null,
                "stopPx" => null,
                "pegOffsetValue" => null,
                "pegPriceType" => "",
                "currency" => "USD",
                "settlCurrency" => "XBt",
                "execType" => "Trade",
                "ordType" => "Limit",
                "timeInForce" => "GoodTillCancel",
                "execInst" => "",
                "contingencyType" => "",
                "exDestination" => "XBME",
                "ordStatus" => "Filled",
                "triggered" => "",
                "workingIndicator" => false,
                "ordRejReason" => "",
                "simpleLeavesQty" => null,
                "leavesQty" => 0,
                "simpleCumQty" => null,
                "cumQty" => 1,
                "avgPx" => 8000,
                "commission" => -0.025,
                "tradePublishIndicator" => "PublishTrade",
                "multiLegReportingType" => "SingleSecurity",
                "text" => "",
                "trdMatchID" => "a26d1c1a-31c2-94b3-6c33-6ef616cf6a58",
                "execCost" => -8079,
                "execComm" => 6,
                "homeNotional" => 8.079E-5,
                "foreignNotional" => -1,
                "transactTime" => "2019-07-10T15:18:57.449Z",
                "timestamp" => "1562771937003"
            ];

        DB::table('signal_1')
            ->insert([
                'type' => 'signal',
                'status' => 'new',
                'direction' => 'buy',
                'signal_volume' => 1000
            ]);

        DB::table('signal_1')
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);

        \App\Classes\DB\SignalTable::updateSignalInfo(1, $execution1);
        \App\Classes\DB\SignalTable::insertRecord($execution1, 1);
        \App\Classes\DB\SignalTable::updateSignalStatusToClose(1, [
            'timestamp' => '1562771937001',
            "avgPx" => 12000,
            'info' => [],
            'orderID' => 'c54a781d-7168-dbf2-d99d-72dc7fc6c013',
            'price' => 12000,
            'symbol' => 'XBTUSD',
            'commission' => -0.025
        ]);






        DB::table('signal_1')
            ->insert([
                'type' => 'signal',
                'status' => 'new',
                'direction' => 'sell',
                'signal_volume' => 1000
            ]);

        DB::table('signal_1')
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);

        \App\Classes\DB\SignalTable::updateSignalInfo(1, $execution2);
        \App\Classes\DB\SignalTable::insertRecord($execution2, 1);
        \App\Classes\DB\SignalTable::updateSignalStatusToClose(1, [
            'timestamp' => '1562771937002',
            "avgPx" => 13000,
            'info' => [],
            'orderID' => 'c54a781d-7168-dbf2-d99d-72dc7fc6124',
            'price' => 13000,
            'symbol' => 'XBTUSD',
            'commission' => -0.025
        ]);






        DB::table('signal_1')
            ->insert([
                'type' => 'signal',
                'status' => 'new',
                'direction' => 'buy',
                'signal_volume' => 1000
            ]);

        DB::table('signal_1')
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);

        \App\Classes\DB\SignalTable::updateSignalInfo(1, $execution3);
        \App\Classes\DB\SignalTable::insertRecord($execution3, 1);
        \App\Classes\DB\SignalTable::updateSignalStatusToClose(1, [
            'timestamp' => '1562771937003',
            "avgPx" => 8000,
            'info' => [],
            'orderID' => 'c54a781d-7168-dbf2-d99d-72dc7fc8888',
            'price' => 8000,
            'symbol' => 'XBTUSD',
            'commission' => -0.025
        ]);



    }


}
