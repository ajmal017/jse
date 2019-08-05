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

        $bot = Bot::where('id', $botId);
        $accountId = $bot->value('account_id');
        $symbolId = $bot->value('symbol_id');
        $account = Account::where('id', $accountId);
        $symbol = Symbol::where('id', $symbolId);

        $accountSettingsObject = array(
            'botTitle' => $bot->value('db_table_name'),
            'barsToLoad' => $bot->value('bars_to_load'),
            'timeFrame' => $bot->value('time_frame'),
            'api' => $account->value('api'),
            'apiSecret' => $account->value('api_secret'),
            'isTestnet' => $account->value('is_testnet'),
            'executionSymbolName' => $symbol->value('execution_symbol_name'),
            'historySymbolName' => $symbol->value('history_symbol_name'),
            'commission' => $symbol->value('commission'),
            'frontEndId' => $bot->value('front_end_id'),
            'volume' => $bot->value('volume'),
            'rateLimit' => $bot->value('rate_limit'),
            'signalTable' => 'signal_' . $botId,
            'isPlaceAsMarket' => $bot->value('place_as_market'),

            /**
             * If 0, a limit order will be placed on best bid/ask.
             * Value in $.
             * if -5%, the limit order will be placed on another side of the order book.
             * And the placed limit order will be executed as market.
             */
            'offset' => $bot->value('offset'),
            'executionTime' => $bot->value('execution_time'),
            'timeRange' => $bot->value('time_range')
        );

        return $accountSettingsObject;
    }
}