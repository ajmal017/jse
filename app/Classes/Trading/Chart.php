<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/14/2018
 * Time: 9:57 PM
 */

namespace App\Classes\Trading;

use App\Classes\Accounting\AccumulatedProfit;
use App\Classes\Accounting\Commission;
use App\Classes\Accounting\NetProfit;
use App\Classes\Accounting\TradeProfit;
use App\Classes\LogToFile;
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
 * Chart class provides collection preparation for chart drawing functionality:
 * History bars (candles)
 * Indicators and diagrams (price channel, volume, profit diagram etc.)
 * Trades (long, short, stop-loss mark)
 * DB actions (trades, profit, accumulated profit etc.)
 * Index method is called on each tick occurrence in RatchetPawlSocket class which reads the trades broadcast stream
 */
class Chart extends Profit
{
    public $trade_flag; // This flag indicates what trade should be opened next. When there is no trades, it is set to all. When long trade has been opened, the next (closing) one must be long and vise vera.
    public $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
    public $add_bar_short = true;
    public $position; // Current position
    public $volume; // Asset amount for order opening

    public $firstPositionEver = true; // Skip the first trade record. When it occurs we ignore calculations and make accumulated_profit = 0. On the next step (next bar) there will be the link to this value
    public $firstEverTradeFlag; // True - when the bot is started and the first trade is executed. Then flag turns to false and trade volume is doubled for closing current position and opening the opposite

    public $tradeProfit;
    public $executionSymbolName;
    public $botSettings;

    protected $lastRow;
    protected $penUltimanteRow;

    /**
     * @see Classes and backtest scheme https://drive.google.com/file/d/1IDBxR2dWDDsbFbradNapSo7QYxv36EQM/view?usp=sharing
     *
     * Chart constructor.
     * @param $botSettings
     */
    public function __construct($botSettings)
    {
        $this->executionSymbolName = $botSettings['executionSymbolName'];
        $this->volume = $botSettings['volume'];
        $this->trade_flag = 'all';
        $this->botSettings = $botSettings;
    }

    public function index($mode = null, $backTestRowId = null)
    {
        echo(__FILE__ . "\n");

        /**
         * Profit calculation inherited class.
         * This class is attached to all strategies.
         */
        $this->calc($mode, $backTestRowId);

        /**
         * $this->trade_flag == "all" is used only when the first trade occurs, then it turns to "long" or "short".
         * SMA filter is always on. SMA filter is a simple SMA with period = 2;
         */
        if (($this->lastRow[0]->sma1 > $this->penUltimanteRow->price_channel_high_value) &&
            ($this->trade_flag == "all" || $this->trade_flag == "long")){
            echo "####### HIGH TRADE!<br>\n";

            /* Is it the first trade ever? */
            if ($this->trade_flag == "all"){
                echo "---------------------- FIRST EVER TRADE<br>\n";
                if($mode != 'backtest')
                    DB::table($this->botSettings['signalTable'])
                        ->insert([
                            'type' => 'signal',
                            'status' => 'new',
                            'direction' => 'buy',
                            'signal_volume' => $this->botSettings['volume']
                        ]);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                if($mode != 'backtest')
                    // PlaceOrder::dispatch('buy', $this->botSettings['volume'] * 2, $this->botSettings);
                    DB::table($this->botSettings['signalTable'])
                        ->insert([
                            'type' => 'signal',
                            'status' => 'new',
                            'direction' => 'buy',
                            'signal_volume' => $this->botSettings['volume'] * 2
                        ]);
            }

            // Trade flag. If this flag set to short -> don't enter this IF and wait for channel low crossing (IF below)
            $this->trade_flag = 'short';
            $this->position = "long";
            $this->add_bar_long = true;

            /* Update the last bar/record in the DB + calculate comission */
            \App\Classes\Accounting\TradeBar::update($this->botSettings, "buy", $this->lastRow[0]->close, $this->lastRow[0]->id);
            \App\Classes\Accounting\Commission::accumulate($this->botSettings, $backTestRowId);
        }

        if (($this->lastRow[0]->sma1 < $this->penUltimanteRow->price_channel_low_value) &&
            ($this->trade_flag == "all"  || $this->trade_flag == "short")) {
            echo "####### LOW TRADE!<br>\n";
            // Is the the first trade ever?
            if ($this->trade_flag == "all"){
                echo "---------------------- FIRST EVER TRADE<br>\n";
                if($mode != 'backtest')

                    DB::table($this->botSettings['signalTable'])
                        ->insert([
                            'type' => 'signal',
                            'status' => 'new',
                            'direction' => 'sell',
                            'signal_volume' => $this->botSettings['volume']
                        ]);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                if($mode != 'backtest')
                    DB::table($this->botSettings['signalTable'])
                        ->insert([
                            'type' => 'signal',
                            'status' => 'new',
                            'direction' => 'sell',
                            'signal_volume' => $this->botSettings['volume'] * 2
                        ]);
            }

            $this->trade_flag = 'long';
            $this->position = "short";
            $this->add_bar_short = true;

            /* Update the last bar/record in the DB */
            \App\Classes\Accounting\TradeBar::update($this->botSettings, "sell", $this->lastRow[0]->close, $this->lastRow[0]->id);
            \App\Classes\Accounting\Commission::accumulate($this->botSettings, $backTestRowId);
        }

        /* Calculate net profit */
        $this->finish();
    }
}