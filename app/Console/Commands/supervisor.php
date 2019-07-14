<?php

namespace App\Console\Commands;

use App\Job;
use App\Bot;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class supervisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor';

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
        // Dispatch a sample job. It will picked up by supervisor

        PlaceLimitOrder::dispatch(
            'buy',
            55,
            [], // $botSettings
            66,
            [], //self::$limitOrderObj,
            1
        );
    }


}
