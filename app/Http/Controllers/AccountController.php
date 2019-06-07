<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Account;
use App\Exchange;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Account::paginate();
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
        Account::create([
            'exchange_id' => $request['id'],
            'name' => Exchange::where('id', $request['id'])->value('name'),
            'api' => $request['api'],
            'api_secret' => $request['api_secret'],
            'memo' => $request['memo'],
        ]);

        // Account id in Exchnages
        // use $accountId
        //
        // We added a new record to Account table using exchnageId
        // How can i find it?
        //
        // Take the same exchnageId = $requesr['id'];
        // And query Account table -> you will get the desired Account id -> which should be placed in Exchnage table
        // account_id - is a primary key
        /*$accountId =
            Account::where('exchange_id', $request['id'])->value('id');

        // Update link: Exchnage -> accoint_id in Exchnages table
        Exchange::where('id', $request['id'])
            ->update([
            'account_id' => $accountId
         ]);*/
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
     */
    public function destroy($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();
        return ['message' => 'Account deleted'];
    }
}
