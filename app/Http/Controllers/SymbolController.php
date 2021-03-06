<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Symbol;
use App\Exchange;
use App\Bot;
use App\Account;

class SymbolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Symbol::paginate();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Symbol::create([
            'exchange_id' => $request['exchange_id'],
            'execution_symbol_name' => $request['execution_symbol_name'],
            'history_symbol_name' => $request['history_symbol_name'],
            'commission' => $request['commission'],
            'is_active' => $request['is_active'],
            'memo' => $request['memo']
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        /* Make sure that symbol is not linked and the bot is not running */
        //$exchangeId = Symbol::where('id', $id)->get()[0]['exchange_id'];

        /*if (Account::where('exchange_id', $exchangeId)->exists()){

            $botId = Account::where('exchange_id', $exchangeId)->get()[0]['bot_id'];

            if(Bot::where('id', $botId)->get()[0]['status'] == 'running')
                throw new \Exception("Symbol is used in Bot: " .
                    Bot::where('id', $botId)->get()[0]['name'] . ' and can not be deleted');
        }*/

        // Look for symbol id in bots table, symbol_id column
        // If found - do not delete the symbol
        // Bot::where('symbol_id', $id)->get(['id', 'symbol_id'])
        $busyBots = "";
        $bots = Bot::where('symbol_id', $id)->get(['id', 'name']);
        foreach ($bots as $bot){
            $busyBots .= 'id: ' . $bot['id'] . ' ' . $bot['name'] . "\n";
        }

        if(Bot::where('symbol_id', $id)->exists()){
            throw new \Exception("Symbol can not be deleted. It is used in: \n" . $busyBots

            );
        }

        Symbol::findOrFail($id)->delete();
    }
}
