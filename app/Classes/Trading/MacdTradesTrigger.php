<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/14/2018
 * Time: 9:57 PM
 */

namespace App\Classes\Trading;

use App\Classes\Accounting\AccumulatedProfit;
use App\Classes\Accounting\NetProfit;
use App\Classes\Accounting\TradeProfit;
use App\Jobs\PlaceLimitOrder;
use App\Jobs\PlaceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\eventTrigger;
use PhpParser\Node\Expr\Variable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Trigger trades based on MACD
 *
 * Class PrTradesTrigger
 * @package App\Classes\Trading
 *
 * @see Classes and backtest scheme https://drive.google.com/file/d/1IDBxR2dWDDsbFbradNapSo7QYxv36EQM/view?usp=sharing
 */
class MacdTradesTrigger
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

    public function __construct($executionSymbolName, $orderVolume, $botSettings)
    {
        $this->volume = $orderVolume;
        $this->executionSymbolName = $executionSymbolName;
        $this->trade_flag = 'all';
        $this->botSettings = $botSettings;
    }

    // Macd line > Macd signal line => go long
    // Macd line < Macd signal line => go short

    public function index($barDate, $timeStamp)
    {
        dump(__FILE__);
        // Realtime mode. No ID of the record is sent. Get the quantity of all records.
        /** In this case we do the same request, take the last record from the DB */
        $lastRow = DB::table($this->botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->get();


        $recordId = $lastRow[0]->id;

        // SMA filter ON?
        // $barClosePrice = $lastRow[0]->sma1;
        $barClosePrice = $lastRow[0]->close;


        // Get the penultimate row
        $penUltimanteRow =
            DB::table($this->botSettings['botTitle'])
                ->where('id', $recordId - 1)
                ->get() // Get row as a collection. A collection can contain may elements in it
                ->first(); // Get the first element from the collection. In this case there is only one

        /**
         * Do not calculate profit if there is no open position. If do not do this check - zeros in table occu
         * $this->trade_flag != "all" if it is "all" - it means that it is a first or initial start
         * We do not store position in DB thus we use "all" check to determine a position absence
         * if "all" - no position has been opened yet
         */
        if ($this->position != null && $this->trade_flag != "all"){

            // Get the price of the last trade
            $lastTradePrice = // Last trade price
                DB::table($this->botSettings['botTitle'])
                    ->whereNotNull('trade_price') // Not null trade price value
                    ->orderBy('id', 'desc') // Form biggest to smallest values
                    ->value('trade_price'); // Get trade price value

            $this->tradeProfit =
                (($this->position == "long" ?
                    ($lastRow[0]->close - $lastTradePrice) * $this->volume :
                    ($lastTradePrice - $lastRow[0]->close) * $this->volume)
                );

            TradeProfit::calculate($this->botSettings, $this->tradeProfit);
            echo "trade profit calculated. Chart.php line 165: " . $this->tradeProfit . "\n";
        }

        if (($lastRow[0]->macd_line > $lastRow[0]->macd_signal_line) && ($this->trade_flag == "all" || $this->trade_flag == "long")){

            echo "####### HIGH TRADE!<br>\n";
            // Is it the first trade ever?
            if ($this->trade_flag == "all"){
                // open order buy vol = vol
                echo "---------------------- FIRST EVER TRADE<br>\n";
                PlaceOrder::dispatch('buy', $this->executionSymbolName, $this->volume, $this->botSettings);

            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                // open order buy vol = vol * 2
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                PlaceOrder::dispatch('buy', $this->executionSymbolName, $this->volume * 2, $this->botSettings);
            }

            // Trade flag. If this flag set to short -> don't enter this IF and wait for channel low crossing (IF below)
            $this->trade_flag = 'short';
            $this->position = "long";
            $this->add_bar_long = true;

            \App\Classes\Accounting\TradeBar::update($this->botSettings, $timeStamp, "buy");
            \App\Classes\Accounting\Commission::accumulate($this->botSettings);
        } // BUY trade

        if (($lastRow[0]->macd_line < $lastRow[0]->macd_signal_line) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) {
            echo "####### LOW TRADE!<br>\n";

            // Is the the first trade ever?
            if ($this->trade_flag == "all"){
                echo "---------------------- FIRST EVER TRADE<br>\n";
                PlaceOrder::dispatch('sell', $this->executionSymbolName, $this->volume, $this->botSettings);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                PlaceOrder::dispatch('sell', $this->executionSymbolName, $this->volume * 2, $this->botSettings);
            }

            $this->trade_flag = 'long';
            $this->position = "short";
            $this->add_bar_short = true;

            /* Update the last bar/record in the DB */
            \App\Classes\Accounting\TradeBar::update($this->botSettings, $timeStamp, "sell");
            \App\Classes\Accounting\Commission::accumulate($this->botSettings);
        } // SELL trade

        /**
         * Do not calculate profit if there are no trades.
         * If trade_flag is set to all, it means that no trades hav been executed yet.
         */
        if ($this->trade_flag != "all") {

            AccumulatedProfit::calculate($this->botSettings, $lastRow[0]->id);
            NetProfit::calculate($this->position, $this->botSettings, $lastRow[0]->id);
        }
    }
}