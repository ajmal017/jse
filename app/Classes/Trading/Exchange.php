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
        $exchange->urls['api'] = $exchange->urls[config('bot.bitmex_api_path')]; // Testnet or Live
        $exchange->apiKey = config('bot.bitmex_api_key');
        $exchange->secret = config('bot.bitmex_api_secret');
        $response = $exchange->createMarketBuyOrder($symbol, $volume, []); // BTC/USD ETH/USD
        dump($response);
    }

    public static function placeMarketSellOrder($symbol, $volume){
        $exchange = new bitmex();
        $exchange->urls['api'] = $exchange->urls[config('bot.bitmex_api_path')]; // Testnet or Live
        $exchange->apiKey = config('bot.bitmex_api_key');
        $exchange->secret = config('bot.bitmex_api_secret');
        $response = $exchange->createMarketSellOrder($symbol, $volume, []); // BTC/USD
        dump($response);
    }
}