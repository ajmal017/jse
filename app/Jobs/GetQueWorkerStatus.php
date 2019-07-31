<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class GetQueWorkerStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $botId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($botId)
    {
        $this->botId = $botId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /* Update loop time stamp in bots table jse-274 */
        DB::table('bots')
            ->where('id', $this->botId)
            ->update([
                'que_worker_update_time' => time()
            ]);
    }
}
