<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 11:09 AM
 */

namespace App\Classes\WebSocket;
use App\Classes\Trading\CandleMaker;
use App\Classes\Trading\Chart;
use Illuminate\Console\Command;

class ConsoleWebSocket
{
    public static function messageParse(array $message, Command $command, CandleMaker $candleMaker, Chart $chart, $indicatorPeriod){
        $candleMaker->index(
            $message['data'][0]['lastPrice'], // Tick price
            $message['data'][0]['timestamp'], // Tick timestamp
            1, // Trade volume. Not used
            $chart, // Classes\Chart $chart Chart class instance
            $command, // Console instance
            $indicatorPeriod
        );
    }
}