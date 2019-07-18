<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/21/2019
 * Time: 7:56 PM
 */

namespace App\Classes\WebSocket\Front;
use App\Bot;
use App\Account;
use App\Symbol;


class TradingAccount
{
    public static function getSettings($botId){
        $accountId = Bot::where('id', $botId)->value('account_id');
        $symbolId = Bot::where('id', $botId)->value('symbol_id');
        
        $accountSettingsObject = array(
            'botTitle' => Bot::where('id', $botId)->value('db_table_name'),
            'barsToLoad' => Bot::where('id', $botId)->value('bars_to_load'),
            'timeFrame' => Bot::where('id', $botId)->value('time_frame'),
            'api' => Account::where('id', $accountId)->value('api'),
            'apiSecret' => Account::where('id', $accountId)->value('api_secret'),
            'isTestnet' => Account::where('id', $accountId)->value('is_testnet'),
            'executionSymbolName' => Symbol::where('id', $symbolId)->value('execution_symbol_name'),
            'historySymbolName' => Symbol::where('id', $symbolId)->value('history_symbol_name'),
            'commission' => Symbol::where('id', $symbolId)->value('commission'),
            'frontEndId' => Bot::where('id', $botId)->value('front_end_id'),
            'volume' => Bot::where('id', $botId)->value('volume'),
            'rateLimit' => Bot::where('id', $botId)->value('rate_limit'),
            'signalTable' => 'signal_' . $botId
        );
        return $accountSettingsObject;
    }
}