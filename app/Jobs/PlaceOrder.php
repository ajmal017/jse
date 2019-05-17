<?php

namespace App\Jobs;

use App\Classes\LogToFile;
use App\Classes\Trading\Exchange;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PlaceOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $symbol;
    private $direction;
    private $volume;
    private $botSettings;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($direction, $symbol, $volume, $botSettings)
    {
        $this->symbol = $symbol;
        $this->direction = $direction;
        $this->volume = $volume;
        $this->botSettings = $botSettings;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->direction == 'buy'){
            Exchange::placeMarketBuyOrder($this->symbol, $this->volume, $this->botSettings);
        } else {
            Exchange::placeMarketSellOrder($this->symbol, $this->volume, $this->botSettings);
        }
    }
}
