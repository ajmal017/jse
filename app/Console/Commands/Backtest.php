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
    protected $signature = 'backtest {botInstance}';

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
        $botSettings = config('bot.bots')[$this->argument('botInstance')];
        Backtesting::start($botSettings);
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
    }
}
