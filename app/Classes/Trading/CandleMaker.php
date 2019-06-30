<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/29/2018
 * Time: 8:37 PM
 */

namespace App\Classes\Trading;
use App\Classes\Indicators\Macd;
use App\Classes\Indicators\PriceChannel;
use App\Classes\Indicators\Sma;
use App\Classes\WebSocket\PusherApiMessage;
use App\Console\Commands\RatchetPawlSocket;
use Illuminate\Support\Facades\DB;
//use Mockery\Exception;
//use Illuminate\Support\Facades\Log;

/**
 * Class CandleMaker
 *
 * Receives ticks from RatchetPawlSocket.php
 * Makes Candles and pass them to Chart.php
 * Also pass ticks to the front end and notifies the chart when a new bar is issued (via new bar issued flag)
 */
class CandleMaker
{
    private $tt;
    private $barHigh = 0; // For high value calculation
    private $barLow = 9999999;
    private $isFirstTickInBar;
    private $tickDate;
    public $indicator;
    private $tableName;
    private $isFirstTimeTickCheck;
    private $addedTickTime;
    private $botSettings;

    public function __construct($indicator, $botSettings)
    {
        $this->isFirstTickInBar = true;
        $this->indicator = $indicator;
        $this->botSettings = $botSettings;
        $this->tableName = $botSettings['botTitle'];
    }

    /**
     * @param double        $tickPrice The price of the current trade (tick)
     * @param date          $tickDate The date of the trade
     * @param double        $tickVolume The volume of the trade. Can be less than 1
     * @param collection    $settings Row of settings from DB
     * @param Command       $command Needed for throwing colored messages to the console output (->info, ->error etc.)
     * @param date          $priceChannelPeriod
     */
    public function index($tickPrice, $tickDateFullTime, $tickVolume, $chart, $command, $priceChannelPeriod, $macdSettings){

        echo "********************************************** CandleMaker\n";

        /** First time ever application run check. Table is empty */
        $lastRecordId = DB::table($this->tableName)->orderBy('time_stamp', 'desc')->first()->id;

        /* Take seconds off and add 1 min. Do it only once per interval (for example 1min) */
        if ($this->isFirstTickInBar) {
            $this->tickDate = strtotime($tickDateFullTime) * 1000;
            $x = date("Y-m-d H:i", $this->tickDate / 1000) . "\n"; // Take seconds off. Convert timestamp to date
            $this->tt = strtotime($x . $this->botSettings['timeFrame'] . "minute"); // // *** TIME FRAME IS HERE!! ***
            //$this->tt = strtotime($x . "+30seconds"); // Custom time frame
            $this->isFirstTickInBar = false;
            /**
             * The first tick after a bar is added can go up or down.
             * At this tick make barHigh and barLow = tickPrice
             * This may bring the shadow of the bar to the opposite side of the bar which looks like the shadow goes
             * into the bar. In this case if the price goes up, we keep low bar shadow = open and vise versa.
             */
            if($tickPrice > (DB::table($this->tableName)->where('id', $lastRecordId))->value('open')){
                $this->barLow = (DB::table($this->tableName)->where('id', $lastRecordId))->value('open');
            } else {
                $this->barHigh = (DB::table($this->tableName)->where('id', $lastRecordId))->value('open');
            }
        }

        /* Calculate high and low of the bar then pass it to the chart in $messageArray */
        if ($tickPrice > $this->barHigh) $this->barHigh = $tickPrice;
        if ($tickPrice < $this->barLow) $this->barLow = $tickPrice;

        DB::table($this->tableName)
            ->where('id', $lastRecordId) // id of the last record. desc - descent order
            ->update([
                'close' => $tickPrice,
                'high' => $this->barHigh,
                'low' => $this->barLow,
            ]);

        $command->error("current tick   : " . gmdate("Y-m-d G:i:s", strtotime($tickDateFullTime)) . " price: $tickPrice");
        echo "time to compare: " . gmdate("Y-m-d G:i:s", ($this->tt)) . " ";
        echo "time frame: " . $this->botSettings['timeFrame'] . "\n";
        echo "Bot instance: " . $this->botSettings['botTitle'] . " Symbol: " . $this->botSettings['executionSymbolName'] . "\n";

        /**
         * New bar is issued. This code is executed once per time frame.
         * When the time of the tick is > added time - add this bar to the DB.
         */
        if (floor(strtotime($tickDateFullTime)) >= $this->tt){
            $command->info("------------------- NEW BAR ISSUED ----------------------");
            /**
             * This price channel calculation is used specially for SMA value. Nothing is gonna change visually if to disable this
             * method call. The only affected variable is SMA. If to disable this call - sma value at the chart and the
             * $barClosePrice variable (Chart.php line 108) will not be the same. SMA is calculated using a bar closes within
             * a determined SMA filter period. Close value is rewritten on each tick received from www.bitfinex.com. This two
             * PriceChannel::calculate() may result as two different SMA values - one on the chart and one in DB.
             * This makes the code haed to debug.
             */
            $this->indicatorsCalculate($priceChannelPeriod, $this->tableName, $macdSettings);

            /* Generate trade signals and add trade info to DB */
            $chart->index();

            /* Add bar to DB */
            $this->addBarToDb($this->tableName, $tickDateFullTime, $tickPrice, $tickVolume);

            /*Set flag to true in order to drop seconds of the time and add time frame */
            $this->isFirstTickInBar = true;

            /**
             * Calculate price channel. All records in the DB are gonna be used.
             * @todo When bars are added, no need go through all bars and calculate price channel. We can go only
             * through price channel period bars and get the value. In this case PriceChannel class must have a parameter
             * whether to calculate the whole data or just a period.
             * This price channel calculation is applied when a new bar is added to the chart. Right after it was added
             * we calculate price channel and inform front end that the chart mast be reloaded
             */

            // Actually this code is called once per time frame
            // We can only call priceChannel::calculate for last bars only from here
            // while from pc.php the full priceChannel::calculate must be called
            $this->indicatorsCalculate($priceChannelPeriod, $this->tableName, $macdSettings);

            /**
             * This flag informs Chart.vue that it needs to add new bar to the chart.
             * We reach this code only when new bar is issued and only in this case this flag is added.
             * In all other cases $messageArray[] array does not contain flag ['flag'] which means that Chart.vue is
             * not adding new bar and updating the current one
             */
            $messageArray['flag'] = true;
        }

        /* Prepare message array */
        $messageArray['tradeDate'] = $this->tickDate;
        $messageArray['tradePrice'] = $tickPrice; // Tick price = current price and close (when a bar is closed)

        /* These values are used for showing on the form */
        $messageArray['tradeVolume'] = $tickVolume;
        $messageArray['tradeBarHigh'] = $this->barHigh; // High value of the bar
        $messageArray['tradeBarLow'] = $this->barLow; // Low value of the bar

        $messageArray['priceChannelHighValue'] =
            (DB::table($this->tableName)
                ->where('id', $lastRecordId - 1)
                ->value('price_channel_high_value'));

        $messageArray['priceChannelLowValue'] =
            (DB::table($this->tableName)
                ->where('id', $lastRecordId - 1)
                ->value('price_channel_low_value'));

        /* Send the information to the chart. Event is received in Chart.vue */
        $pusherApiMessage = new PusherApiMessage();
        $pusherApiMessage->clientId = $this->botSettings['frontEndId'];
        $pusherApiMessage->messageType = 'symbolTickPriceResponse'; // symbolTickPriceResponse, error
        $pusherApiMessage->payload = $messageArray;

        $this->rateLimitCheck($tickDateFullTime, $pusherApiMessage);

        /* Reset high, low of the bar but do not out send these values to the chart. Next bar will be started from scratch */
        if ($this->isFirstTickInBar == true){
            $this->barHigh = 0;
            $this->barLow = 9999999;
        }
    }

    private function rateLimitCheck($tickDateFullTime, $pusherApiMessage){
        /* Allow ticks not more than twice a second */
        if ($this->isFirstTimeTickCheck || strtotime($tickDateFullTime) >= $this->addedTickTime) {
            $this->isFirstTimeTickCheck = false;
            $this->addedTickTime = strtotime($tickDateFullTime) + $this->botSettings['rateLimit'];

            try{
                event(new \App\Events\jseevent($pusherApiMessage->toArray()));
            } catch (\Exception $e) {
                echo __FILE__ . " " . __LINE__ . "\n";
                dump($e);
            }
        }
    }

    private function addBarToDb($tableName, $tickDateFullTime, $tickPrice, $tickVolume){
        DB::table($tableName)->insert(array(
            'date' => gmdate("Y-m-d G:i:s", strtotime($tickDateFullTime)), // Date in regular format. Converted from unix timestamp
            'time_stamp' => strtotime($tickDateFullTime) * 1000,
            'open' => $tickPrice,
            'close' => $tickPrice,
            'high' => $tickPrice,
            'low' => $tickPrice,
            'volume' => $tickVolume
        ));
    }

    private function indicatorsCalculate($priceChannelPeriod, $tableName, $macdSettings){
        if ($this->indicator == 'priceChannel'){
            PriceChannel::calculate($priceChannelPeriod, $this->tableName, false);
            Sma::calculate(
                'close',
                2,
                'sma1',
                $tableName,
                false); // Calculate SMA together with price channel. This sam used as a filter.
        }
        if ($this->indicator == 'macd') {
            Macd::calculate($macdSettings = [
                'ema1Period' => $macdSettings['emaPeriod'],
                'ema2Period' => $macdSettings['macdLinePeriod'],
                'ema3Period' => $macdSettings['macdSignalLinePeriod']],
                $this->botSettings,
                true);
        }
    }
}





