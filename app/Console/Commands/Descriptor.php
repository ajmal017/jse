<?php

namespace App\Console\Commands;

use App\Classes\LogToFile;
use App\Classes\Trading\LimitOrder;
use App\Job;
use App\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Descriptor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'descriptor';

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
     * Show all open files and descriptors JSE-296
     *
     * @return mixed
     */
    public function handle()
    {
        $resources = array();
        //dump(get_defined_vars());

        dump(get_resources('stream'));

        foreach( get_defined_vars() as $key => $val )
        {
            if( 'resource' == gettype( $val ) )
            {
                $resources[ get_resource_type( $val ) ][] = $key;
            }
        }

        foreach( $resources as $type => $res )
        {
            echo sprintf( '%- 20s: % 3d%s', $type, count($res), PHP_EOL );
        }
    }
}
