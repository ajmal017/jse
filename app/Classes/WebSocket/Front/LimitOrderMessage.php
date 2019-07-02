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
use Illuminate\Support\Facades\DB;

class LimitOrderMessage
{
    private static $limitOrderObj;
    private static $isFirstTimeTickCheck = true;
    private static $addedTickTime;

    public static function parse(array $message){
        self::$limitOrderObj = Cache::get('bot_1');
        /**
         * Check DB for new signals
         */
        $status = DB::table('signal_1')
            ->where('type', 'signal')
            ->value('status');
        if ($status == 'new' || $status == 'pending'){
            //dump('** There is a new signal!');
            /* Best bid/ask order book parse */
            self::orderBookParse($message);
            /* Order parse */
            self::orderParse($message);
            /* Execution parse */
            self::executionParse($message);
        } else {
            //dump('No new signals. LimitOrderMessage.php');
        }
    }

    private static function orderBookTick($ask){
        $botSettings = [
            'api' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
            'apiSecret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK',
            'isTestnet' => 1,
            'executionSymbolName' => 'BTC/USD', // BTC/USD ADAU19
            'signalTable' => 'signal_1'
        ];

        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE LIMIT ORDERRRRR!!!');
            self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
            self::$limitOrderObj['isLimitOrderPlaced'] = true;
            Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

            /* Use bid/ask price + step/increment for testing purposes */
            PlaceLimitOrder::dispatch('sell', self::$limitOrderObj['volume'], $botSettings, $ask + self::$limitOrderObj['step'], self::$limitOrderObj);
            //dump(self::$limitOrderObj);

            /* Set signal status to pending */
            DB::table('signal_1')
                ->where('type', 'signal')
                ->where('status', 'new')
                ->update([
                    'status' => 'pending'
                ]);

        } else {

            if ($ask + self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
                //dump('Ask == best ASK!');
            } else {
                echo ('PRICE HAS CHANGED! PREPARE TO AMEND THE ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

                if(self::$limitOrderObj['limitOrderTimestamp'] != null){
                    dump('---------------NOW CAN AMEND ORDER');
                    $response = \App\Classes\Trading\Exchange::amendOrder($ask + self::$limitOrderObj['step'], Cache::get('bot_1')['orderID'], $botSettings);
                    //dump($response);

                    // Put price to cache in order no to amend more than nedded
                    self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
                    Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

                } else {
                    dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
                }
            }
        }
    }

    /**
     * Rate limit check.
     *
     * @param $tickDateFullTime
     * @return bool
     */
    private static function rateLimitheck($tickDateFullTime){
        if (self::$isFirstTimeTickCheck || $tickDateFullTime >= self::$addedTickTime) {
            self::$isFirstTimeTickCheck = false;
            self::$addedTickTime = $tickDateFullTime + 4; // Seconds
            return true;
        }
    }

    private static function orderBookParse(array $message){
        if(array_key_exists('table', $message))
            if($message['table'] == 'orderBook10')
                if(array_key_exists('action', $message))
                    if($message['action'] == 'update')
                        if(array_key_exists('data', $message)){
                            /**
                             * Send parsed best bid/ask from order book.
                             * Rate limit. Order amend - once per 2 seconds.
                             * Otherwise we get to many amend jobs in que and it gets flooded.
                             */
                            self::rateLimitheck(strtotime($message['data'][0]['timestamp']));
                                self::orderBookTick($message['data'][0]['asks'][0][0]);
                        }
    }

    private static function orderParse(array $message){
        if(array_key_exists('table', $message))
            if($message['table'] == 'order')
                if(array_key_exists('action', $message)) // Action can be = 'insert' as well
                    if($message['action'] == 'update')

                        if(array_key_exists('data', $message)){
                            if(array_key_exists(0, $message['data']))

                                // Check orderID
                                if(array_key_exists('orderID', $message['data'][0]))
                                    echo $message['data'][0]['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";
                            If (($message['data'][0]['orderID']  == self::$limitOrderObj['orderID'])){

                                if(array_key_exists('leavesQty', $message['data'][0]))

                                    if(($message['data'][0]['leavesQty']) == 0){
                                        //dump($message['data'][0]);
                                        //dump('Order Fully filled');
                                        //\App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                        //die('die from LimitOrderMessage.php xxccvv'); // Works good
                                    } else {
                                        //dump($message['data'][0]);
                                        //dump('Order NOT FULLY filled yet');
                                        //\App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                        //die('die from LimitOrderMessage.php yyggff'); // Works good
                                    }

                            } else {
                                dump('Can not find order ID. LimitOrderMessage.php');
                            }
                        }
    }

    private static function executionParse(array $message){
        if(array_key_exists('table', $message))
            if($message['table'] == 'execution')
                if(array_key_exists('action', $message))
                    if($message['action'] == 'insert')

                        if(array_key_exists('data', $message)){
                            if(array_key_exists(0, $message['data']))

                                /* Check orderID */
                                if(array_key_exists('orderID', $message['data'][0])){
                                    echo $message['data'][0]['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";
                                    If (($message['data'][0]['orderID']  == self::$limitOrderObj['orderID'])){

                                        /**
                                         * Check execType.
                                         * Can be:
                                         * Trade - when order gets filled. Can be partial or full
                                         * New - fresh order is placed
                                         * Replaced - order amend
                                         */
                                        if(array_key_exists('execType', $message['data'][0]))
                                            if (($message['data'][0]['execType']  == 'Trade'))

                                                /**
                                                 * Check quantity in the trade.
                                                 */
                                                if(array_key_exists('leavesQty', $message['data'][0]))

                                                    /* Check if fully filled. leavesQty - volume reminder */
                                                    if(($message['data'][0]['leavesQty']) == 0){
                                                        dump($message);
                                                        dump('Order Fully filled');
                                                        \App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                                        // Set signal status to close
                                                        \App\Classes\DB\SignalTable::updateSignalStatus();
                                                        die('die from LimitOrderMessage.php execution ddffgg');
                                                    } else {
                                                        dump($message);
                                                        dump('Order NOT FULLY filled yet');
                                                        \App\Classes\DB\SignalTable::insertRecord($message['data'][0]);
                                                        //die('die from LimitOrderMessage.php execution jjhhgg');
                                                    }

                                    } else {
                                        dump('Can not find order ID. LimitOrderMessage.php in execution qqwwee');
                                    }
                                }
                        }
    }

}