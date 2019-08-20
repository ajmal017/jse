<?php

namespace App\Http\Controllers;

use App\Symbol;
use Illuminate\Http\Request;
use App\Exchange;
use Illuminate\Support\Facades\DB;
use App\Bot;
use App\Symbols;
use App\Account;
use App\Strategy;

class CardsController extends Controller
{
    public function index()
    {
        $arr = [];
        /*for($i = 1; $i < 5; $i++){
            $netProfit = DB::table('signal_' . $i);
            $arr[] = [
                'netProfit' => $netProfit
                    ->where('type', 'signal')
                    ->orderBy('id', 'desc')
                    ->value('net_profit'),
                'tradesQuantity' => $netProfit
                    ->where('type', 'signal')
                    ->count(),
                'executionSymbolName' =>
                    Symbol::where('id', Bot::where('id', $i)->value('symbol_id'))->value('execution_symbol_name')
            ];
        }*/

        $records = Bot::get();
        foreach($records as $record){
            $signal = DB::table('signal_' . $record->id);


            $exchangeId = Account::where('bot_id', $record->id)->value('exchange_id');
            $exchangeName = Exchange::where('id', $exchangeId)->value('name');

            $strategyName = Strategy::where('id', $record->strategy_id)->value('name');

            $arr[] = [
                'id' => $record->id,
                'name' => $record->name,
                'status' => $record->status,
                'netProfit' => $signal
                    ->where('type', 'signal')
                    ->orderBy('id', 'desc')
                    ->value('net_profit'),
                'tradesQuantity' => $signal
                    ->where('type', 'signal')
                    ->count(),
                'executionSymbolName' =>
                    Symbol::where('id', Bot::where('id', $record->id)
                        ->value('symbol_id'))
                        ->value('execution_symbol_name'),
                'exchnageName' => $exchangeName,
                'strategyName' => $strategyName
            ];
        }

        return $arr;
    }
}
