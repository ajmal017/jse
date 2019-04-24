<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/24/2019
 * Time: 3:43 PM
 */

namespace App\Classes\Trading;
use Illuminate\Support\Facades\DB;

class History
{
    public static function loadPeriod(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.bitmex.com/api/v1/trade/bucketed?binSize=1m&partial=false&symbol=XBTUSD&count=10&reverse=false&startTime=2019-04-01");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $bars = json_decode(curl_exec($ch));
        curl_close($ch);

        foreach($bars as $bar){
            // dump(gmdate("Y-m-d G:i:s", strtotime($bar->timestamp))); // Regular data
            // dump(strtotime($bar->timestamp)); // Timestamp
            DB::table('asset_1')->insert(array(
                'date' => gmdate("Y-m-d G:i:s", strtotime($bar->timestamp)),
                'time_stamp' => strtotime($bar->timestamp) * 1000,
                'open' => $bar->open,
                'close' => $bar->close,
                'high' => $bar->high,
                'low' => $bar->low,
                'volume' => $bar->volume,
            ));
        }
    }
}