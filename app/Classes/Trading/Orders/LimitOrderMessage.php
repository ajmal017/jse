<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/31/2019
 * Time: 7:00 PM
 */

namespace App\Classes\Trading\Orders;
//use App\Jobs\AmendOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * REMOVE THE PREVIOUS(BIG) OF THE LimitOrderMessage.php class!
 *
 * Inheritance secuence:
 * 1. OrderBook
 * 2. Signal
 * 3. Limit order
 * 4. ForceSignalFinish
 * 5. AmendOrder
 * 6. TimeForce
 *
 * Class LimitOrderMessage
 * @package App\Classes\Trading\Orders
 */
class LimitOrderMessage extends OrderBook
{
    /**
     * @var array $botSettings      1
     * @var array $limitOrderObj    2
     * @var array $signalRow        3
     * @var int $botId              4
     * @var int $queId              5
     */
    public static $botSettings, $limitOrderObj, $signalRow, $botId, $queId;

    /* Rate limit vars */
    public static $isFirstTimeTickCheck = true;
    public static $isFirstTimeTickCheck2 = true;
    public static $isAmendOrderRateLimitCheck = true;

    public static $addedTickTime;
    public static $addedTickTime2;
    public static $addedTickTimeAmend;

    public static $isGetExecutionsTickCheck = true;
    public static $addedTickGetExcutions;
    public static $exchange;

    /**
     * Time (sec) during which a limit order will located in order book.
     * If it is not filled, it will be amend (turn to market)
     */
    public static $limitOrderExecutionTime;
    /**
     * If during this time an execution information is not received, mark the order as closed with price from order book.
     * This value can not be shorter than $limitOrderExecutionTime
     */
    public static $timeRange;
    public static $limitOrderOffset;

    public static function parse(array $message, $botId, $queId, $exchnage){
        self::$botId = $botId;
        self::$queId = $queId;

        /* Get limit order object */
        self::$limitOrderObj = Cache::get('bot_' . $botId);

        self::$exchange = $exchnage;
        /*  Check DB for new signals */
        self::$signalRow =
            DB::table('signal_' . $botId)
                ->where('status', 'new')
                ->orwhere('status', 'pending')
                ->get();

        if (count(self::$signalRow) > 1) {
            $message = 'There are more than one record with New status in signals. LimitOrderMessage.php';
            Log::emergency($message);
            // Front end message goes here
        }

        /**
         * Status of the signal is sent to pending once a limit order is placed. In Exchnage.php. placeLimitBuyOrder/placeLimitSellOrder
         * Signals are added to signal_1.. table from Chart.php or MacdTradesTrigger.php
         */
        if (count(self::$signalRow) == 1){
            if (self::$signalRow[0]->status != 'closed' ){
                /**
                 * Best bid/ask order book parse.
                 * Used for limit order placement and order amend.
                 */
                parent::orderBookParse($message);
            } else {
                dump('No new signals. LimitOrderMessage.php ' . now());
            }
        } else {
            //dump('No new or pending signals in signals table. LimitOrderMessage.php ' . now());
        }
    }
}