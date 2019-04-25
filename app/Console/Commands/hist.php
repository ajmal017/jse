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
    protected $signature = 'hist {symbol}';

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
        //dd($this->argument('symbol'));

        \App\Classes\Trading\History::loadPeriod($this->argument('symbol'));
        \App\Classes\Indicators\PriceChannel::calculate();
        // Subscribe and start broadcasting
    }
}
