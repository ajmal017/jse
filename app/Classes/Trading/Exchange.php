<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 6:45 PM
 */

namespace App\Classes\Trading;
use ccxt\bitmex;
use Mockery\Exception;

class Exchange
{
    private static $response;
    public static function placeMarketBuyOrder($symbol, $volume, $botSettings){
        $exchange = new bitmex();
        $exchange->urls['api'] = $exchange->urls[$botSettings['bitmex_api_path']]; // Testnet or live
        $exchange->apiKey = ($botSettings['bitmex_api_path'] == 'api' ? $botSettings['bitmex_api_key'] : config('bot.testNetSettings')['bitmex_testnet_api_key']);
        $exchange->secret = ($botSettings['bitmex_api_path'] == 'api' ? $botSettings['bitmex_api_secret'] : config('bot.testNetSettings')['bitmex_testnet_api_secret']);
        try{
            self::$response = $exchange->createMarketBuyOrder($symbol, $volume, []); // BTC/USD ETH/USD
        }
        catch (\Exception $e)
        {
            // Error
            self::$response = $e->getMessage();
            dump(self::$response);
        }
        self::checkResponse();
    }

    public static function placeMarketSellOrder($symbol, $volume, $botSettings){
        $exchange = new bitmex();
        $exchange->urls['api'] = $exchange->urls[$botSettings['bitmex_api_path']];
        $exchange->apiKey = ($botSettings['bitmex_api_path'] == 'api' ? $botSettings['bitmex_api_key'] : config('bot.testNetSettings')['bitmex_testnet_api_key']);
        $exchange->secret = ($botSettings['bitmex_api_path'] == 'api' ? $botSettings['bitmex_api_secret'] : config('bot.testNetSettings')['bitmex_testnet_api_secret']);
        try{
            self::$response = $exchange->createMarketSellOrder($symbol, $volume, []); // BTC/USD ETH/USD
        }
        catch (\Exception $e)
        {
            self::$response = $e->getMessage();
        }
        self::checkResponse();
    }

    private static function checkResponse(){
        if (gettype(self::$response) == 'array'){
            dump(self::$response);
        }

        if (gettype(self::$response) == 'string'){
            throw new Exception();
            // Exchange overload
            /**
             * @todo move all text possible errors to a dictionary. Allow user to change these values.
             */
            if (self::$response == "bitmex {\"error\":{\"message\":\"The system is currently overloaded. Please try again later.\",\"name\":\"HTTPError\"}}\""){
                dump('EXCHANGE OVERLOADED! RESTART JOB! IN place order');
                //throw new Exception();
            }
        }
    }
}