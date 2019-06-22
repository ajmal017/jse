<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/21/2019
 * Time: 6:53 PM
 */

namespace App\Classes\WebSocket\Front;
use Illuminate\Support\Facades\DB;
use App\Bot;
use App\Strategy;
use App\PricechannelSettings;
use App\MacdSettings;

/**
 * Gets strategies settings from DB.
 * This object can contain a number of strategies.
 *
 * Class Strategies
 * @package App\Classes\WebSocket\Front
 * @return array
 */
class Strategies
{
    private static $strategiesSettingsObject;
    public static function getSettings($botId){
        /* get strategy_id from bots */
        $strategyId = Bot::where('id', $botId)->value('strategy_id');
        /* get strategy type from strategies using strategy_id */
        $strategyTypeId = Strategy::where('id', $strategyId)->value('strategy_type_id');

        /* Price channel */
        if ($strategyTypeId == '1'){
            /* get pricechannel_settings_id from strategies */
            $pricechannelSettingsId = Strategy::where('id', $strategyId)->value('pricechannel_settings_id');
            /* get settings row from price_channel_settings */
            $pricechannelSettingsRow = PricechannelSettings::where('id', $pricechannelSettingsId)->get();
            self::$strategiesSettingsObject = array(
                'priceChannel' => [
                    'priceChannelPeriod' => $pricechannelSettingsRow[0]->time_frame,
                    'smaFilterPeriod' => $pricechannelSettingsRow[0]->sma_filter_period
                ]
            );
        }

        /* Macd */
        if ($strategyTypeId == '2'){
            $macdSettingsId = Strategy::where('id', $strategyId)->value('macd_settings_id');
            $macdSettingsRow = MacdSettings::where('id', $macdSettingsId)->get();
            self::$strategiesSettingsObject = array(
                'macd' => [
                    'emaPeriod' => $macdSettingsRow[0]->ema_period,
                    'macdLinePeriod' => $macdSettingsRow[0]->macd_line_period,
                    'macdSignallinePeriod' => $macdSettingsRow[0]->macd_signalline_period
                ]
            );

        }

        return self::$strategiesSettingsObject;
    }
}