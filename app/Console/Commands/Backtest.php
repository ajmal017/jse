<?php

namespace App\Console\Commands;

use App\Classes\Backtesting\Backtesting;
use Illuminate\Console\Command;

class Backtest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backtest';

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
        $botSettings = [
            'botTitle' => 'bot_5', // Back testing table
            'executionSymbolName' => 'ETH/USD', // ETH/USD
            'historySymbolName' => 'ETHUSD', // ETHUSD
            'volume' => 1000,
            //'commission' => -0.00025, // Taker: 0.000750 - such values come from Bitmex
            'commission' => 0.075 / 100,
            'strategy' => 'pc',
            'strategyParams' => [
                'priceChannelPeriod' => 2
            ],
            'timeFrame' => 1, // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
            'barsToLoad' => 500,
            'frontEndId' => '12350',
        ];

        $botSettings2 = [
            'botTitle' => 'bot_5',
            'executionSymbolName' => 'ETH/USD', // ETH/USD
            'historySymbolName' => 'ETHUSD', //
            'volume' => 1000,
            //'commission' => -0.00025, // Taker: 0.000750
            'commission' => 0.075 / 100,
            'strategy' => 'macd',
            'strategyParams' => [
                'emaPeriod' => 2,
                'macdLinePeriod' => 5,
                'macdSignalLinePeriod' => 5
            ],
            'timeFrame' => 1, // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
            'barsToLoad' => 200,
            'frontEndId' => '12350',
        ];

        \App\Classes\Backtesting\BacktestingFront::start($botSettings);


        //$botSettings = config('bot.bots')[$this->argument('botInstance')];
        //Backtesting::start($botSettings);

        // Exclude to a separate class
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
}
