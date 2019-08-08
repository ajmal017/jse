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
    private static $startTime;
    private static $endTime;

    public static function loadPeriod($botSettings){
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
        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong. Die. History.php' );
        DB::table($botSettings['botTitle'])->truncate();

        foreach(array_reverse($bars) as $bar){
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

    public static function loadStep($botSettings){

        $barsToLoad = 10;
        $timeFrame = $botSettings['timeFrame'];
        $symbol = $botSettings['historySymbolName'];
        /* Get the last loaded date. Next history portion will be loaded from it */
        $lastDate = DB::table($botSettings['botTitle'])
            ->orderBy('id', 'desc')
            ->take(1)
            ->value('time_stamp');
        $lastDateEncoded = urlencode(date('c', $lastDate / 1000));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            "https://www.bitmex.com/api/v1/trade/bucketed?binSize=$timeFrame&partial=true&symbol=$symbol&count=$barsToLoad&reverse=false&startTime=$lastDateEncoded");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $bars = json_decode(curl_exec($ch));
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);
        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong. Die. History.php' );
        foreach($bars as $bar){
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
        return([
            'barsLoaded' => DB::table($botSettings['botTitle'])->count(),
            'startDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'asc')->take(1)->value('date'),
            'endDate' => DB::table($botSettings['botTitle'])->orderBy('id', 'desc')->take(1)->value('date')
        ]);

    }

}