<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class hist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hist {historySymbol}{orderSymbol}';

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
        // History: XBTUSD, Order: BTC/USD
        // History: ETHUSD, Order: ETH/USD

        // dump($this->argument('historySymbol'));
        // dd($this->argument('orderSymbol'));

        \App\Classes\Trading\History::loadPeriod($this->argument('historySymbol'));
        \App\Classes\Indicators\PriceChannel::calculate();
        // Subscribe and start broadcasting
    }
}
