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
class MacdTradesTrigger extends Profit
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
    private $macdCrossedFirstTime = true;

    public $botSettings;
    protected $lastRow;
    protected $penUltimanteRow; // Do wee need this var? It exists in Chart.php

    public function __construct($botSettings)
    {
        $this->executionSymbolName = $botSettings['executionSymbolName'];
        $this->volume = $botSettings['volume'];
        //$this->trade_flag = 'trades_disabled'; // Need to wait until MACD cross, then open a trade. Otherwise we get a trade at start.
        $this->trade_flag = 'all'; // Need to wait until MACD cross, then open a trade. Otherwise we get a trade at start.
        $this->botSettings = $botSettings;
    }

    // Macd line > Macd signal line => go long
    // Macd line < Macd signal line => go short

    public function index($mode = null, $backTestRowId = null)
    {
        echo __FILE__ . "\n" ;
        /* Extended class method call */
        $this->calc($mode, $backTestRowId);

        /* Wait until MACD generates a signal */
        if (($this->lastRow[0]->macd_line > $this->lastRow[0]->macd_signal_line) || ($this->lastRow[0]->macd_line < $this->lastRow[0]->macd_signal_line)){
            if ($this->macdCrossedFirstTime){
                $this->macdCrossedFirstTime = false;
                $this->trade_flag = "all";
            }
        }

        //echo "------------------------------------------------------MacdTradesTrigger.php 69. this->trade_flag: " . $this->trade_flag . "\n";

        if (($this->lastRow[0]->macd_line > $this->lastRow[0]->macd_signal_line) && ($this->trade_flag == "all" || $this->trade_flag == "long")){

            echo "####### HIGH TRADE!<br>\n";
            // Is it the first trade ever?
            if ($this->trade_flag == "all"){
                // open order buy vol = vol
                echo "---------------------- FIRST EVER TRADE<br>\n";
                if($mode != 'backtest')
                    //PlaceOrder::dispatch('buy', $this->botSettings['volume'], $this->botSettings);
                    DB::table('signal_1')
                        ->insert([
                            'type' => 'signal',
                            'status' => 'new',
                            'direction' => 'buy',
                            'signal_volume' => $this->botSettings['volume']
                        ]);
            }
            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
            {
                // open order buy vol = vol * 2
                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL * 2\n";
                if($mode != 'backtest')
                    //PlaceOrder::dispatch('buy', $this->botSettings['volume'] * 2, $this->botSettings);
                    DB::table('signal_1')
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

            \App\Classes\Accounting\TradeBar::update($this->botSettings, "buy", $this->lastRow[0]->close, $this->lastRow[0]->id);
            \App\Classes\Accounting\Commission::accumulate($this->botSettings);
        }

        if (($this->lastRow[0]->macd_line < $this->lastRow[0]->macd_signal_line) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) {
            echo "####### LOW TRADE!<br>\n";

            // Is the the first trade ever?
            if ($this->trade_flag == "all"){
                echo "---------------------- FIRST EVER TRADE<br>\n";
                if($mode != 'backtest')
                    DB::table('signal_1')
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
                    DB::table('signal_1')
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
            \App\Classes\Accounting\Commission::accumulate($this->botSettings);
        }

        $this->finish();
    }
}