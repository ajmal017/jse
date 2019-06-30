<?php

namespace App\Jobs;

use App\Classes\Trading\Exchange;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Amend limit buy/sell order.
 *
 * Class AmendOrder
 * @package App\Jobs
 */
class AmendOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $newPrice;
    private $orderID;
    private $botSettings;

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
    public function __construct($newPrice, $orderID, $botSettings)
    {
        $this->newPrice = $newPrice;
        $this->orderID = $orderID;
        $this->botSettings = $botSettings;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Exchange::amendOrder($this->newPrice, $this->orderID, $this->botSettings);
    }
}
