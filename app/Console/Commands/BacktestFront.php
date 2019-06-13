<?php

namespace App\Console\Commands;

use App\Classes\Backtesting\Backtesting;
use App\Classes\Backtesting\BacktestingFront;
use Illuminate\Console\Command;

/**
 * This back testing class is made for running from front end. Not from command line.
 * Backtest.php - is the one for command line use.
 *
 * Class Backtest
 * @package App\Console\Commands
 */

class BacktestFront extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backtestfront';

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
    public function handle(Request $request)
    {
        $botSettings = [
            'botTitle' => 'bot_5',
            'executionSymbol' => 'ETH/USD',
            'historySymbol' => 'ETHUSD',
            'volume' => 10,
            'commission' => -0.0250, // Taker: 0.0750
            'strategy' => 'pc',
            'strategyParams' => [
                'priceChannelPeriod' => 1
            ],
            'timeFrame' => 1, // 1 or 5 minutes. https://www.bitmex.com/api/explorer/#!/Trade/Trade_getBucketed
            'barsToLoad' => 100,
            'frontEndId' => '12345',
            //'rateLimit' => 4,
        ];

        BacktestingFront::start($botSettings);
        // reload chart goes here. exclude reload chart method from pc, mc to a separate class. located in trading. name: Chart::reload
        // Rename Chart.php to PcTradesTrigger

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

        return $request;
    }
}
