<?php

namespace App\Console\Commands;

use Faker\Provider\DateTime;
use Illuminate\Console\Command;

class payments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jse:payment {volume}';

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
        $nonce = time();
        $uri = '/api/v1/wallet/payment';
        $apiToken = $_ENV['API_TOKEN'];
        $secret = $_ENV['SECRET'];
        $btcValletAddress = $_ENV['BTC_VALET_ADDRESS'];
        $amount = $this->argument('volume');

        $requestBody = "{
            'address':  . $btcValletAddress,
            'currency': 'BTC',
            'amount': . $amount
            }";

        $signature = hash_hmac('sha256', $uri . $nonce . $apiToken . $requestBody, $secret); // Signature preparation

        // CURL
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "https://www.livingroomofsatoshi.com" . $uri); // Live
        curl_setopt($ch, CURLOPT_URL, "https://private-86457c-livingroomofsatoshiwalletapi.apiary-mock.com" . $uri); // Mock
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "api-token: " . $apiToken,
            "nonce: " . $nonce,
            "signature: " . $signature
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);

    }
}
