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
use Illuminate\Support\Facades\Log;

class Exchange
{
    private static $response;
    public static function placeMarketBuyOrder($symbol, $volume, $botSettings){

        echo __FILE__ . " line: " . __LINE__ . "\n";

        $exchange = new bitmex();

        if($botSettings['api_path'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api']; // Testnet or live. test or api
        }

        $exchange->apiKey = $botSettings['api_key'];
        $exchange->secret = $botSettings['secret'];

        try{
            echo "API path. test or api: " . $exchange->urls['api'] . "\n";
            echo "Symbol: " . $symbol . " in Exchnage.php \n";
            self::$response = $exchange->createMarketBuyOrder($symbol, $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
            dump(self::$response);
        }
        catch (\Exception $e)
        {
            dump('--------- in exception line 40');
            // Error
            self::$response = $e->getMessage();
            //dump(self::$response);
        }
        self::checkResponse();
    }

    public static function placeMarketSellOrder($symbol, $volume, $botSettings){
        echo __FILE__ . " line: " . __LINE__ . "\n";
        $exchange = new bitmex();

        if($botSettings['api_path'] == 1){
            $exchange->urls['api'] = $exchange->urls['test']; // Testnet or live. test or api
        } else {
            $exchange->urls['api'] = $exchange->urls['api'];
        }

        $exchange->apiKey = $botSettings['api_key'];
        $exchange->secret = $botSettings['secret'];

        try{
            echo "API path. test or api:" . $exchange->urls['api'] . "\n";
            self::$response = $exchange->createMarketSellOrder($symbol, $volume, []); // BTC/USD ETH/USD
            echo "Execution response: \n";
            dump(self::$response);
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
            echo "Error string line 82: " . self::$response . "\n";
            switch(false){
                case !strpos(self::$response, 'Account has insufficient');
                    $error = 'Account has insufficient funds. Die.';
                    Log::notice($error);
                    die(__FILE__ . ' ' . __LINE__);

                case !strpos(self::$response, 'does not have market symbol'); // bitmex does not have market symbol
                    $error = 'Bitmex does not have market symbol. Execution is not possible';
                    throw new \Exception($error);
                    break;
                /* @see: https://www.bitmex.com/app/restAPI#Overload */
                case !strpos(self::$response, 'overloaded');
                    // The system is currently overloaded. Please try again later
                    throw new \Exception('Exchange overloaded');
                    break;
            }
        }
    }
}