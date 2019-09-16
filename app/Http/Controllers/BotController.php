<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Bot;
use App\Job;

class BotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Bot::paginate();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
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
        // Unlink
        Bot::where('id', $request['botId'])->update([
            $request['unlinkField'] => null
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
        $bot = Bot::findOrFail($id);
        $this->validate($request, [
            'time_frame' => ['required', Rule::in(['1', '5', '15', '30', '60'])],
            'bars_to_load' => 'required|max:100'
        ]);

        $row = DB::table('bots')
            ->where('id',$id)
            ->get()[0];

        /**
         * Check whether que worker are running or not.
         * Rate limit values are set in Front.php and LimitRest.php
         */
        if (time() - $row->front_worker_update_time > 20){
            return response('Front worker is offline! Id: ' . $row->id . '<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        }

        if (time() - $row->execution_worker_update_time > 20){
            return response('Execution worker is offline! Id: ' . $row->id . '<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        }

        if (time() - $row->que_worker_update_time > 20){
            return response('Que worker is offline! Id: ' . $row->id . '<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        }

        /* Check whether there are record in jobs table. If so - the que worker does not work */
        // Disabled. This check can not pass after que worker discover has benn added. There jobs all the time.
        /*if (Job::all()->count() != 0){
            return response('Jobs table is not empty!<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        } else {
            $bot->update($request->all());
        }*/

        $bot->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
