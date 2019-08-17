<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/24/2019
 * Time: 3:43 PM
 */

namespace App\Classes\Trading;
use App\Classes\LogToFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Load historical data from bitmex and store in the DB.
 * Symbol: XBTUSD, ETHUSD
 * Sample request: https://www.bitmex.com/api/v1/trade/bucketed?binSize=5m&partial=false&symbol=XBTUSD&count=50&reverse=true
 * @link https://www.bitmex.com/api/explorer/?reverse=#!/Trade/Trade_getBucketed
 * ISO 8601 date. 1969-12-31T18:33:28 / 2019-07-25T16:00:00.000Z
 *
 * Class History
 * @package App\Classes\Trading
 */
class History
{
    private static $lastDate;
    private static $startTime;
    private static $lastDateEncoded;

    /**
     * The history loaded to the chart when bot starts.
     *
     * @param $botSettings
     * @throws \Exception
     */
    public static function loadPeriod($botSettings)
    {
        $barsToLoad = $botSettings['barsToLoad'];
        $timeFrame = $botSettings['timeFrame'] . 'm';
        $symbol = $botSettings['historySymbolName'];
        $url = "https://www.bitmex.com/api/v1/trade/bucketed?binSize=$timeFrame&partial=false&symbol=$symbol&count=$barsToLoad&reverse=true";
        dump('History.php', __LINE__);
        dump($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $bars = json_decode(curl_exec($ch));

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);
        if (!$bars){
            dump($bars);
            throw new \Exception('History is not loaded. Symbol may be wrong. Die. History.php');
        }
        DB::table($botSettings['botTitle'])->truncate();

        foreach (array_reverse($bars) as $bar) {
            DB::table($botSettings['botTitle'])->insert(array(
                'symbol' => $symbol,
                'date' => gmdate("Y-m-d G:i:s", strtotime($bar->timestamp)), // Regular date
                'time_stamp' => strtotime($bar->timestamp) * 1000, // Timestamp
                'open' => $bar->open,
                'close' => $bar->close,
                'high' => $bar->high,
                'low' => $bar->low,
                'volume' => $bar->volume,
            ));
        }
    }

    /**
     * The history loaded into the back tester.
     *
     * @param $botSettings
     * @return array
     * @throws \Exception
     */
    public static function loadStep($botSettings)
    {
        $barsToLoad = 250;
        $timeFrame = $botSettings['timeFrame'];
        //$timeFrame = '5m'; // 1m/5m/1h/1d
        $symbol = $botSettings['historySymbolName'];

        /* Get the latest loaded date. Next history portion will be loaded from it */
        self::$lastDate = DB::table($botSettings['botTitle'])
            ->orderBy('id', 'desc')
            ->take(1)
            ->value('date');

        /* Get the current date when a first portion of bars is loaded */
        if(!self::$lastDate) {
            //self::$startTime = time(); // Timestamp 10 digits
            self::$startTime = $botSettings['startTime'];
            /* Step back three hours (or other time). History data appears at the servers not in the real-time */
            self::$startTime = date('Y-m-d\TH:i:s', strtotime(self::$startTime)); // - 3600
        }

        /**
         * When the first portion of bars is already loaded to DB.
         * Add one step. Otherwise, between groups, you will have two bars with the same time
         */
        if(self::$lastDate) {
            if($timeFrame == '1m') $addedTime = '+1 minute';
            if($timeFrame == '5m') $addedTime = '+5 minutes';
            if($timeFrame == '1h') $addedTime = '+1 hour';
            if($timeFrame == '1d') $addedTime = '+1 day';
            self::$startTime = date('Y-m-d\TH:i:s', strtotime(self::$lastDate . $addedTime));
        }

        $url = "https://www.bitmex.com/api/v1/trade/bucketed?binSize=$timeFrame&partial=false&symbol=$symbol&reverse=false&count=$barsToLoad&startTime=" . self::$startTime;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            $url); // 2019-08-04
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $bars = json_decode(curl_exec($ch));

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong or no bars for requested date.');

        foreach ($bars as $bar) {
            DB::table($botSettings['botTitle'])
                ->where('id', 1)
                ->insert(array(
                'symbol' => $symbol,
                'date' => gmdate("Y-m-d G:i:s", strtotime($bar->timestamp)), // Regular date
                'time_stamp' => strtotime($bar->timestamp) * 1000, // Timestamp
                'open' => $bar->open,
                'close' => $bar->close,
                'high' => $bar->high,
                'low' => $bar->low,
                'volume' => $bar->volume,
            ));
        }
        return ([
            'barsLoaded' => DB::table($botSettings['botTitle'])->count(),
            //'startDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'asc')->take(1)->value('date'),
            'endDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->value('date')
        ]);

    }
}
