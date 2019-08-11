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
    private static $endTime;
    private static $lastDateEncoded;

    public static function loadPeriod($botSettings)
    {
        $barsToLoad = $botSettings['barsToLoad'];
        $timeFrame = $botSettings['timeFrame'] . 'm';
        $symbol = $botSettings['historySymbolName'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            "https://www.bitmex.com/api/v1/trade/bucketed?binSize=$timeFrame&partial=false&symbol=$symbol&count=$barsToLoad&reverse=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $bars = json_decode(curl_exec($ch));

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);
        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong. Die. History.php');
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

    public static function loadStep($botSettings)
    {

        $barsToLoad = 4;
        //$timeFrame = $botSettings['timeFrame'];
        $timeFrame = '1m'; // 1m/5m/1h/1d
        $symbol = $botSettings['historySymbolName'];

        /* Get the last loaded date. Next history portion will be loaded from it */
        self::$lastDate = DB::table($botSettings['botTitle'])
            ->orderBy('id', 'desc')
            ->take(1)
            ->value('date');

        if(!self::$lastDate){
            self::$endTime = time();
            self::$endTime = date('Y-m-d\TH:i:s', self::$endTime - 86400);
        } else {
            /* Add one step. Otherwise, betwwen groups, you will have two bars with the same time */
            self::$endTime = date('Y-m-d\TH:i:s', strtotime(self::$lastDate . "+1 minute"));
        }

        $url = "https://www.bitmex.com/api/v1/trade/bucketed?binSize=$timeFrame&partial=false&symbol=$symbol&reverse=false&count=$barsToLoad&startTime=" . self::$endTime;

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
        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong. Die. History.php');
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
            'startDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'asc')->take(1)->value('date'),
            'endDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->value('date')
        ]);

    }
}
