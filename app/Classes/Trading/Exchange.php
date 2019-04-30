<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 6:45 PM
 */

namespace App\Classes\Trading;
use ccxt\bitmex;

class Exchange
{
    public static function placeMarketBuyOrder($symbol, $volume){

        $exchange = new bitmex();
        $exchange->urls['api'] = $exchange->urls[env('BITMEX_API_PATH')]; // Testnet or Live
        $exchange->apiKey = env('BITMEX_TESTNET_PUBLIC_API_KEY');
        $exchange->secret = env('BITMEX_TESTNET_PRIVATE_API_KEY');
        $response = $exchange->createMarketBuyOrder($symbol, $volume, []); // BTC/USD ETH/USD
        dump($response);
    }

    public static function placeMarketSellOrder($symbol, $volume){
        $exchange = new bitmex();
        $exchange->urls['api'] = $exchange->urls[env('BITMEX_API_PATH')]; // Testnet or Live
        $exchange->apiKey = env('BITMEX_TESTNET_PUBLIC_API_KEY');
        $exchange->secret = env('BITMEX_TESTNET_PRIVATE_API_KEY');
        $response = $exchange->createMarketSellOrder($symbol, $volume, []); // BTC/USD
        dump($response);
    }
}