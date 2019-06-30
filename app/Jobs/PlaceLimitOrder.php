<?php

namespace App\Jobs;

use App\Classes\Trading\Exchange;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PlaceLimitOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $direction;
    private $volume;
    private $botSettings;
    private $limitOrderPrice;
    private $limitOrderObj;

    /**
     * The list of all possible variables to set.
     * @see: https://laravel.com/api/5.8/Illuminate/Bus/Queueable.html#method_onConnection
     * @see: https://laravel.com/docs/5.8/queues
     */
    public $retryAfter = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($direction, $volume, $botSettings, $limitOrderPrice, $limitOrderObj)
    {
        $this->direction = $direction;
        $this->volume = $volume;
        $this->botSettings = $botSettings;
        $this->limitOrderPrice = $limitOrderPrice;
        $this->limitOrderObj = $limitOrderObj;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->direction == 'buy'){
            //Exchange::placeMarketBuyOrder($this->botSettings, $this->volume);
        } else {
            Exchange::placeLimitSellOrder($this->botSettings, $this->limitOrderPrice, $this->volume, $this->limitOrderObj);
        }
    }
}
