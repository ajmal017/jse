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
        Backtesting::start(config('bot.bots')[$this->argument('botInstance')]);
        // reload chart goes here. exclude reload chart method from pc, mc to a separate class. located in trading. name: Chart::reload
        // Rename Chart.php to PcTradesTrigger
    }
}
