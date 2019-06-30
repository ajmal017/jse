<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/27/2019
 * Time: 6:50 PM
 */

namespace App\Classes\WebSocket\Front;
use App\Jobs\AmendOrder;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Support\Facades\Cache;

class LimitOrderMessage
{
    private static $limitOrderObj;
    private static $isFirstTimeTickCheck = true;
    private static $addedTickTime;

    public static function parse(array $message){
        /**
         * GET IT FROM CACHE!
         */
        self::$limitOrderObj = Cache::get('bot_1');

        /* Best bid/ask order book parse */
        if(array_key_exists('table', $message))
            if($message['table'] == 'orderBook10')
                if(array_key_exists('action', $message))
                    if($message['action'] == 'update')
                        if(array_key_exists('data', $message)){

                            /**
                             * Send parsed best bid/ask from order book
                             */
                            self::orderBookTick($message['data'][0]['asks'][0][0]);
                        }

        /* Order parse */
        if(array_key_exists('table', $message))
            if($message['table'] == 'order')

                dump($message);

                if(array_key_exists('action', $message)) // Action can be = 'insert' as well
                    if($message['action'] == 'update')

                        if(array_key_exists('data', $message)){
                            if(array_key_exists(0, $message['data']))

                                // Check orderID
                                if(array_key_exists('orderID', $message['data'][0]))
                                    If (($message['data'][0]['orderID']  == self::$limitOrderObj['orderID'])){

                                        if(array_key_exists('leavesQty', $message['data'][0]))

                                            if(($message['data'][0]['leavesQty']) == 0){
                                                dump('Order Fully filled');
                                                die('die from LimitOrderMessage.php xxccvv');
                                            } else {
                                                dump('Order NOT FULLY filled yet');
                                                //die('die from LimitOrderMessage.php yyggff'); // Works good!
                                            }

                                    } else {
                                        dump('Can not find order ID. LimitOrderMessage.php');
                                    }
                        }
    }

    private static function orderBookTick($ask){

        $botSettings = [
            'api' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
            'apiSecret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK',
            'isTestnet' => 1,
            'executionSymbolName' => 'BTC/USD'
        ];

        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACEEEE LIMIT ORDERRRRR!!!');
            // PUT CACHE
            self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
            self::$limitOrderObj['isLimitOrderPlaced'] = true;
            Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));
            /* Use bid/ask price + step/increment for testing purposes */
            PlaceLimitOrder::dispatch('sell', self::$limitOrderObj['volume'], $botSettings, $ask + self::$limitOrderObj['step'], self::$limitOrderObj);
            dump(self::$limitOrderObj);

        } else {

            /* If the current bid/ask price == the price on withc the limit order was places? */
            //echo "ask: " . $ask . " limitOrderObj->limitOrderPrice: " . self::$limitOrderObj['limitOrderPrice'] . "\n";

            if ($ask + self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
                //dump('Ask == best ASK!');
            } else {
                echo ('PRICE HAS CHANGED! PREPARE TO AMEND THE ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']);

                if(self::$limitOrderObj['limitOrderTimestamp'] != null){
                    dump('---------------NOW CAN AMEND ORDER');

                    // Rate limit goes gere
                    if (self::rateLimitheck())
                        AmendOrder::dispatch($ask + self::$limitOrderObj['step'], Cache::get('bot_1')['orderID'], $botSettings);

                    // Put price to cache in order no to amend more than nedded
                    self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
                    Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

                } else {
                    dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
                }
            }
        }
    }

    private static function rateLimitheck($tickDateFullTime){
        /* Rate limit */
        if (self::$isFirstTimeTickCheck || strtotime($tickDateFullTime) >= self::$addedTickTime) {
            self::$isFirstTimeTickCheck = false;
            self::$addedTickTime = strtotime($tickDateFullTime) + 1; // Seconds
            return true;
        }
    }
}