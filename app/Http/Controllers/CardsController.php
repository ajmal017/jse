<?php

namespace App\Http\Controllers;

use App\Symbol;
use Illuminate\Http\Request;
use App\Exchange;
use Illuminate\Support\Facades\DB;
use App\Bot;
use App\Symbols;

class CardsController extends Controller
{
    public function index()
    {
        $arr = [];
        //return Exchange::paginate();
        //return $id;
        for($i = 1; $i < 5; $i++){
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
        }

        return $arr;
    }
}
