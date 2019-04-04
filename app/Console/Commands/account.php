<?php

namespace App\Console\Commands;

use Faker\Provider\DateTime;
use Illuminate\Console\Command;

class account extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jse:account';

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
        $apiToken = $_ENV['API_TOKEN'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://www.livingroomofsatoshi.com/api/v1/wallet/account");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "api-token: " . $apiToken
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);
    }
}
