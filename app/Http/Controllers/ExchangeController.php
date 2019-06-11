<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exchange;

class ExchangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Exchange::paginate();
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
        Exchange::create([
            'name' => $request['name'],
            'url' => 'http://www.google.com',
            'live_api_path' => 'http://www.google.com',
            'testnet_api_path' => 'http://www.google.com',
            'status' => 'online'
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
        return \ccxt\Exchange::$exchanges;
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
        $exchange = Exchange::findOrFail($id);
        $this->validate($request,[
            'name' => 'required|string|max:20',
            'url' => 'url|required',
            'live_api_path' => 'url|required',
            'testnet_api_path' => 'url|required',
            'memo' => 'string|max:50'
        ]);

        $exchange->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $exchange = Exchange::findOrFail($id);
        $exchange->delete();
        return ['message' => 'Exchnage deleted'];
    }
}
