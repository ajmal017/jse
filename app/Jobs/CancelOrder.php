<?php

namespace App\Jobs;

use App\Classes\LogToFile;
use App\Classes\Trading\Exchange;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CancelOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $botSettings;
    private $exchange;

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
    public function __construct($botSettings, $exchange)
    {
        $this->botSettings = $botSettings;
        $this->exchange = $exchange;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Exchange::cancelOrder($this->botSettings, $this->exchange);
    }
}
