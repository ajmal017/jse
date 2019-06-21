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
     * The list of all possible variables to set.
     * @see: https://laravel.com/api/5.8/Illuminate/Bus/Queueable.html#method_onConnection
     * @see: https://laravel.com/docs/5.8/queues
     */
    public $retryAfter = 5;


    /**
     * Connection can be also hardcoded.
     *
     * @var string
     * public $connection = '';
     */


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
        echo "symbol: " . $this->symbol . "\n";
        echo "direction: " . $this->direction . "\n";
        echo "volume: " . $this->volume . "\n";
        echo "bot settings: \n";
        dump($this->botSettings);

        if($this->direction == 'buy'){
            Exchange::placeMarketBuyOrder($this->symbol, $this->volume, $this->botSettings);
        } else {
            Exchange::placeMarketSellOrder($this->symbol, $this->volume, $this->botSettings);
        }
    }
}
