<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/24/2019
 * Time: 3:43 PM
 */

namespace App\Classes\Trading;
use Illuminate\Support\Facades\DB;

/**
 * Load historical data from bitmex and store in the DB.
 * Symbol: XBTUSD, ETHUSD
 * Sample request: https://www.bitmex.com/api/v1/trade/bucketed?binSize=5m&partial=false&symbol=XBTUSD&count=50&reverse=true
 * @link https://www.bitmex.com/api/explorer/?reverse=#!/Trade/Trade_getBucketed
 *
 * Class History
 * @package App\Classes\Trading
 */
class History
{
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
        if (!$bars) throw new \Exception('History is not loaded. Symbol may be wrong. Die');

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
}