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
    private $amendReason;
    private $exchange;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($newPrice, $orderID, $botSettings, $amendReason, $exchange)
    {
        $this->newPrice = $newPrice;
        $this->orderID = $orderID;
        $this->botSettings = $botSettings;
        $this->amendReason = $amendReason;
        $this->exchange = $exchange;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Exchange::amendOrder($this->newPrice, $this->orderID, $this->botSettings, $this->amendReason, $this->exchange);
    }
}
