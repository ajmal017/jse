<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/25/2019
 * Time: 11:09 AM
 */

namespace App\Classes\WebSocket;
use App\Classes\Trading\CandleMaker;
use Illuminate\Console\Command;

class ConsoleWebSocket
{
    public static function messageParse(array $message, Command $command, CandleMaker $candleMaker){
        $candleMaker->index(
            $message['data'][0]['lastPrice'],
            $message['data'][0]['timestamp'],
            1, // Trade volume. Not used
            //$chart, // Classes\Chart $chart Chart class instance
            //$this->settings, // @param collection $settings Row of settings from DB
            $command);
    }
}