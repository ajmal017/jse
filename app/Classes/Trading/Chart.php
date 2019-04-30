<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/14/2018
 * Time: 9:57 PM
 */

namespace App\Classes\Trading;

use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\eventTrigger;
use PhpParser\Node\Expr\Variable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;


/**
 * Chart class provides collection preparation for chart drawing functionality:
 * History bars (candles)
 * Indicators and diagrams (price channel, volume, profit diagram etc.)
 * Trades (long, short, stop-loss mark)
 * DB actions (trades, profit, accumulated profit etc.)
 * Index method is called on each tick occurrence in RatchetPawlSocket class which reads the trades broadcast stream
 *
 * Tick types in websocket channel:
 * 'te', 'tu' Flags explained
 * 'te' - When the trades is rearrested at the exchange
 * 'tu' - When the actual trade has happened. Delayed for 1-2 seconds from 'te'
 * 'hb' - Heart beating. If there is no new message in the channel for 1 second, Websocket server will send you an heartbeat message in this format
 * SNAPSHOT (the initial message)
 * @see http://blog.bitfinex.com/api/websocket-api-update/
 * @see https://docs.bitfinex.com/docs/ws-general
 */
class Chart
{
    public $trade_flag; // The value is stored in DB. This flag indicates what trade should be opened next. When there is not trades, it is set to all. When long trade has been opened, the next (closing) one must be long and vise vera.
    public $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
    public $add_bar_short = true;
    public $position; // Current position
    public $volume; // Asset amount for order opening
    public $firstPositionEver = true; // Skip the first trade record. When it occurs we ignore calculations and make accumulated_profit = 0. On the next step (next bar) there will be the link to this value
    public $firstEverTradeFlag; // True - when the bot is started and the first trade is executed. Then flag turns to false and trade volume is doubled for closing current position and opening the opposite
    public $tradeProfit;

    private $executionSymbolName;

    /**
     * Received message in RatchetPawlSocket.php is sent to this method as an argument.
     * A message is processed, bars are added to DB, profit is calculated.
     *
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $socketMessage
     * @param Command Variable type for colored and formatted console messages like alert, warning, error etc.
     * @return array $messageArray Array which has OHLC of the bar, new bar flag and other parameters. The array is
     * generated on each tick (each websocket message) and then passed as an event to the browser. These messages
     * are transmitted over websocket pusher broadcast service.
     * @see https://pusher.com/
     * @see Classes and backtest scheme https://drive.google.com/file/d/1IDBxR2dWDDsbFbradNapSo7QYxv36EQM/view?usp=sharing
     */

    public function __construct($executionSymbolName, $orderVolume)
    {
        $this->volume = $orderVolume;
        $this->executionSymbolName = $executionSymbolName;
        $this->trade_flag = 'all';
    }

    public function index($barDate, $timeStamp)
    {
        echo "********************************************** Chart.php!<br>\n";

        // Realtime mode. No ID of the record is sent. Get the quantity of all records.
        /** In this case we do the same request, take the last record from the DB */
        $assetRow =
            DB::table('asset_1')
                ->orderBy('id', 'desc')->take(1)
                ->get();
        $recordId = $assetRow[0]->id;

        $barClosePrice = $assetRow[0]->sma1;

        /**
         * We do this check because sometimes, don't really understand under which circumstances, we get
         * trying to get property of non-object
         */
        if (!is_null(DB::table('asset_1')->where('id', $recordId - 1)->get()->first()))
        {
            // Get the penultimate row
            $penUltimanteRow =
                DB::table('asset_1')
                    ->where('id', $recordId - 1)
                    ->get() // Get row as a collection. A collection can contain may elements in it
                    ->first(); // Get the first element from the collection. In this case there is only one
        }
        else
        {
            echo "Null check. Chart.php " . __LINE__;
        }

        /**
         * Do not calculate profit if there is no open position. If do not do this check - zeros in table occu
         * $this->trade_flag != "all" if it is "all" - it means that it is a first or initial start
         * We do not store position in DB thus we use "all" check to determine a position absence
         * if "all" - no position has been opened yet
         */
        if ($this->position != null && $this->trade_flag != "all"){

            // Get the price of the last trade
            $lastTradePrice = // Last trade price
                DB::table('asset_1')
                    ->whereNotNull('trade_price') // Not null trade price value
                    //->where('time_stamp', '<', $timeStamp) // Find the last trade. This check is needed only for historical back testing.
                    ->orderBy('id', 'desc') // Form biggest to smallest values
                    ->value('trade_price'); // Get trade price value

            $this->tradeProfit =
                (($this->position == "long" ?
                    ($assetRow[0]->close - $lastTradePrice) * $this->volume :
                    ($lastTradePrice - $assetRow[0]->close) * $this->volume)
                );

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    // Calculate trade profit only if the position is open.
                    // Because we reach this code on each new bar is issued when high or low price channel boundary is exceeded
                    'trade_profit' => round($this->tradeProfit, 4),
                ]);

            //event(new \App\Events\ConnectionError("INFO. Chart.php line 164. trade profit calculated "));
            echo "trade profit calculated. Chart.php line 165: " . $this->tradeProfit . "\n";
        }

        $this->dateCompeareFlag = true;
        /**
         * TRADES WATCH. Channel value of previous (penultimate bar)
         * @todo Read the whole row as a single collection then access it by keys. No need to make several request. Get rid of settings_tester
         */
        $allow_trading = false;


        // $this->trade_flag == "all" is used only when the first trade occurs, then it turns to "long" or "short".
        if (($barClosePrice > $penUltimanteRow->price_channel_high_value) && ($this->trade_flag == "all" || $this->trade_flag == "long")){
            echo "####### HIGH TRADE!<br>\n";
            // Is it the first trade ever?
            if ($this->trade_flag == "all"){
                // open order buy vol = vol
                echo "---------------------- FIRST EVER TRADE<br>\n";
                Exchange::placeMarketBuyOrder($this->executionSymbolName, $this->volume);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                // open order buy vol = vol * 2
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                Exchange::placeMarketBuyOrder($this->executionSymbolName, $this->volume);
                Exchange::placeMarketBuyOrder($this->executionSymbolName, $this->volume);
            }

            // Trade flag. If this flag set to short -> don't enter this IF and wait for channel low crossing (IF below)
            $this->trade_flag = 'short';
            $this->position = "long";
            $this->add_bar_long = true;

            // Update trade info to the last(current) bar(record)
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $assetRow[0]->close,
                    'trade_direction' => "buy",
                    'trade_volume' => $this->volume,
                    //'trade_commission' => round(($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    'trade_commission' => 0.35, // Fixed commission
                    //'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    'accumulated_commission' => DB::table('asset_1')->sum('trade_commission')
                ]);

            echo "Trade price: " . $assetRow[0]->close . "<br>\n";

        } // BUY trade


        // If < low price channel. SELL
        if (($barClosePrice < $penUltimanteRow->price_channel_low_value) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) {
            echo "####### LOW TRADE!<br>\n";

            // Is the the first trade ever?
            if ($this->trade_flag == "all"){
                echo "---------------------- FIRST EVER TRADE<br>\n";
                Exchange::placeMarketSellOrder($this->executionSymbolName, $this->volume);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                Exchange::placeMarketSellOrder($this->executionSymbolName, $this->volume);
                Exchange::placeMarketSellOrder($this->executionSymbolName, $this->volume);
            }

            // DB::table("settings_realtime")->where('id', 1)->update(['trade_flag' => 'long']);
            $this->trade_flag = 'long';
            $this->position = "short";
            $this->add_bar_short = true;

            // Add(update) trade info to the last(current) bar(record)
            // EXCLUDE THIS CODE TO SEPARATE CLASS!!!!!!!!!!!!!!!!!!!
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $assetRow[0]->close,
                    'trade_direction' => "sell",
                    'trade_volume' => $this->volume,
                    //'trade_commission' => round(($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    'trade_commission' => 2,

                    //'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    // IB Forex comission
                    'accumulated_commission' => DB::table('asset_1')->sum('trade_commission') + 2,
                ]);
        } // SELL trade
    }
}