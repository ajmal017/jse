<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/27/2019
 * Time: 6:50 PM
 */

namespace App\Classes\WebSocket\Front;


class LimitOrderMessage
{
    public static function parse($message){
        //dump($message);
        if(array_key_exists('table', $message))
            if($message['table'] == 'orderBook10')
                if(array_key_exists('action', $message))
                    if($message['action'] == 'update')
                        if(array_key_exists('data', $message)){
                            dump($message['data'][0]['asks'][0]); // Ask
                            $ask = $message['data'][0]['asks'][0][0];

                            \App\Classes\Trading\Exchange::placeLimitSellOrder(
                                [
                                    'api' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
                                    'apiSecret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK',
                                    'isTestnet' => 1,
                                    'executionSymbolName' => 'BTC/USD'
                                ],
                                $ask, 100000);
                        }

    }
}