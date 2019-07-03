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
    private static $signalRow;

    public static function parse(array $message){

        self::$limitOrderObj = Cache::get('bot_1');
        /**
         * Check DB for new signals
         */
        self::$signalRow =
            DB::table('signal_1')
                ->where('status', 'new')
                ->orwhere('status', 'pending')
                ->get();

        if (count(self::$signalRow) > 1) die ('There are more than onw record with New status in signals. Die from LimitOrderMessage.php');

        if (count(self::$signalRow) == 1){
            if (self::$signalRow[0]->status != 'closed' ){

                /**
                 * Best bid/ask order book parse.
                 * Used for limit order amend and placement.
                 */
                self::orderBookParse($message);

                /**
                 * Order parse.
                 * Not used.
                 */
                //self::orderParse($message);

                /**
                 * Execution parse.
                 * Used to track executions of the limit order and add these records to DB.
                 * Also indicates when the order was fully filled.
                 * Sets order status from new to pending/close.
                 * Actual statuses are set in SignalTable.php
                 */
                self::executionParse($message);

            } else {
                dump('No new signals. LimitOrderMessage.php ' . now());
            }
        } else {
            dump('No new or pending signals in signals table. LimitOrderMessage.php ' . now());
        }

    }

    private static function orderBookTick($ask, $bid){
        $botSettings = [
            'api' => 'ikeCK-6ZRWtItOkqvqo8F6wO',
            'apiSecret' => 'JfmMTXx3YruSP3OSBKQvULTg4sgQJKZkFI2Zy7TZXniOUbeK',
            'isTestnet' => 0,
            'executionSymbolName' => 'BTC/USD', // BTC/USD ADAU19
            'signalTable' => 'signal_1'
        ];

        /**
         * DEBUG! DELETE THIS!
         */
        echo "CHECK isLimitOrderPlaced flag. it must not be NULL! : " . self::$limitOrderObj['isLimitOrderPlaced'] . "LimitOrderMessage.php ccvvbb\n";

        if (self::$signalRow[0]->direction == "sell")
            self::handleSellLimitOrder($ask, $botSettings);

        if (self::$signalRow[0]->direction == "buy")
            self::handleBuyLimitOrder($bid, $botSettings);
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
                             * Rate limit. Order amend - once per 2 (or other delay) seconds.
                             * Otherwise we get to many amends and it gets flooded.
                             */
                            self::rateLimitheck(strtotime($message['data'][0]['timestamp']));
                                self::orderBookTick($message['data'][0]['asks'][0][0], $message['data'][0]['bids'][0][0]);
                        }
    }

    /**
     * Not used.
     *
     * @param array $message
     */
    private static function orderParse(array $message){
        if(array_key_exists('table', $message))
            if($message['table'] == 'order')
                if(array_key_exists('action', $message)) // Action can be = 'insert' as well
                    if($message['action'] == 'update')

                        if(array_key_exists('data', $message)){
                            if(array_key_exists(0, $message['data']))

                                // Check orderID
                                if(array_key_exists('orderID', $message['data'][0]))
                                    // echo $message['data'][0]['orderID'] . "   -   " . self::$limitOrderObj['orderID'] . "\n";
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
                                //dump('Can not find order ID. LimitOrderMessage.php');
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

                                                    /* Data object can contain multiple executions! https://dacoders.myjetbrains.com/youtrack/issue/JSE-195 */
                                                    if (count($message['data']) > 0){
                                                        dump('Execution DATA object contains more thatn 1 record. foreach it!!! LimitOrderMessage');
                                                        // die('Execution DATA object contains more thatn 1 record. NOT HANDLED!!! LimitOrderMessage');
                                                        dump($message);
                                                        foreach ($message['data'] as $execution){
                                                            /* Check if fully filled. leavesQty - volume reminder */
                                                            //if(($message['data'][0]['leavesQty']) == 0){
                                                            if(($execution['leavesQty']) == 0){
                                                                dump('Order Fully filled');
                                                                \App\Classes\DB\SignalTable::insertRecord($execution);
                                                                // Set signal status to close
                                                                \App\Classes\DB\SignalTable::updateSignalStatus();
                                                                //die('die from LimitOrderMessage.php execution ddffgg');

                                                            } else {
                                                                dump('Order NOT FULLY filled yet');
                                                                \App\Classes\DB\SignalTable::insertRecord($execution);
                                                                //die('die from LimitOrderMessage.php execution jjhhgg');
                                                            }
                                                        }

                                                        // Additional check.
                                                        // Make sure that volume reminder == 0 after foreach is done. And only now we can refresh flags.
                                                        // There can be several groups and foreach can work more than once when flags must be reset
                                                        // exactly the whole order is fully filled.
                                                        // Refresh flags and clOrdID in order to place new limit order


                                                        if(($execution['leavesQty']) == 0) {
                                                            //self::$limitOrderObj = Cache::get('bot_1');
                                                            self::$limitOrderObj['orderID'] = null;
                                                            self::$limitOrderObj['clOrdID'] = 'abc-123-' . now();
                                                            self::$limitOrderObj['isLimitOrderPlaced'] = false;
                                                            self::$limitOrderObj['limitOrderPrice'] = null;
                                                            self::$limitOrderObj['limitOrderTimestamp'] = null;

                                                            Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));
                                                        }
                                                    }
                                    } else {
                                        dump('Can not find order ID. LimitOrderMessage.php in execution qqwwee');
                                    }
                                }
                        }
    }

    private static function handleSellLimitOrder($ask, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            /**
             * Don't place limitorder if status is new or pending
             * This is an additional check https://dacoders.myjetbrains.com/youtrack/issue/JSE-204
             * I suspect that isLimitOrderPlaced flag does not properly work.
             */
            if (self::$signalRow[0]->status != 'pending'){
                self::placeSellLimitOrder($ask, $botSettings);
            }
            else {
             dump('Tried to place a SELL order while there is a PENDING order already. Die LimitOrderMessage.php ttyyuu');
             die();
            }
        } else {
            self::amendSellLimitOrder($ask, $botSettings);
        }
    }

    private static function handleBuyLimitOrder($bid, $botSettings){
        if(!self::$limitOrderObj['isLimitOrderPlaced']){
            if(self::$signalRow[0]->status != 'pending'){
                self::placeBuyLimitOrder($bid, $botSettings);
            } else {
                dump('Tried to place a BUY order while there is a PENDING order already. Die LimitOrderMessage.php ppooii');
                die();
            }
        } else {
            self::amendBuyLimitOrder($bid, $botSettings);
        }
    }

    private static function placeBuyLimitOrder($bid, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE BUY LIMIT ORDERRRRR!!!');
        self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderObj['step'];
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

        /* Use bid/ask price + step/increment for testing purposes */
        PlaceLimitOrder::dispatch(
            'buy',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            $bid - self::$limitOrderObj['step'],
            self::$limitOrderObj
        );

        /* Set signal status to pending */
        DB::table('signal_1')
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);
    }

    private static function placeSellLimitOrder($ask, $botSettings){
        dump('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~PLACE SELL LIMIT ORDERRRRR!!!');
        self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
        self::$limitOrderObj['isLimitOrderPlaced'] = true;
        Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

        /**
         * Use bid/ask price + step/increment for testing purposes.
         *
         */
        PlaceLimitOrder::dispatch(
            'sell',
            self::$signalRow[0]->signal_volume,
            $botSettings,
            $ask + self::$limitOrderObj['step'],
            self::$limitOrderObj
        );

        /* Set signal status to pending */
        DB::table('signal_1')
            ->where('type', 'signal')
            ->where('status', 'new')
            ->update([
                'status' => 'pending'
            ]);
    }

    private static function amendBuyLimitOrder($bid, $botSettings){
        if ($bid - self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PRICE HAS CHANGED! PREPARE TO AMEND SELL ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

            if(self::$limitOrderObj['limitOrderTimestamp'] != null){
                dump('---------------NOW CAN AMEND SELL ORDER');
                $response = \App\Classes\Trading\Exchange::amendOrder($bid - self::$limitOrderObj['step'], Cache::get('bot_1')['orderID'], $botSettings);
                //dump($response);

                // Put price to cache in order no to amend more than needed
                self::$limitOrderObj['limitOrderPrice'] = $bid - self::$limitOrderObj['step'];
                Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

    private static function amendSellLimitOrder($ask, $botSettings){
        if ($ask + self::$limitOrderObj['step'] == self::$limitOrderObj['limitOrderPrice']){
            //dump('Ask == best ASK!');
        } else {
            echo ('PRICE HAS CHANGED! PREPARE TO AMEND SELL ORDER! if the order really placed? Limit price: ' . self::$limitOrderObj['limitOrderPrice']) . "\n";

            if(self::$limitOrderObj['limitOrderTimestamp'] != null){
                dump('---------------NOW CAN AMEND SELL ORDER');
                $response = \App\Classes\Trading\Exchange::amendOrder(
                    $ask + self::$limitOrderObj['step'],
                    Cache::get('bot_1')['orderID'],
                    $botSettings);
                //dump($response);

                // Put price to cache in order no to amend more than needed
                self::$limitOrderObj['limitOrderPrice'] = $ask + self::$limitOrderObj['step'];
                Cache::put('bot_1', self::$limitOrderObj, now()->addMinute(30));

            } else {
                dump('********************** Waiting the order to be placed and timestamp returned. TIMESTAMP: ' . self::$limitOrderObj['limitOrderTimestamp']);
            }
        }
    }

}