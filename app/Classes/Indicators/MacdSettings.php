<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 4/30/2019
 * Time: 2:48 PM
 */

namespace App\Classes\Indicators;


class MacdSettings
{
    public static function set($macdSettings){
        Sma::calculate('close',$macdSettings['ema1Period'], 'sma1'); // SMA1
        Sma::calculate('close',$macdSettings['ema2Period'], 'sma2'); // SMA2
        Ema::calculate('close', $macdSettings['ema1Period'], 'sma1', 'ema1'); // EMA1
        Ema::calculate('close', $macdSettings['ema2Period'], 'sma2', 'ema2'); // EMA2
        Macd::calculate();
        Sma::calculate('macd_line', $macdSettings['ema3Period'], 'macd_signal_line'); // MACD signal line as SMA from MACD line
    }
}