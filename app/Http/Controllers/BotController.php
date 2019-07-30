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
            'time_frame' => ['required', Rule::in(['1', '5'])],
        ]);

        $row = DB::table('bots')
            ->where('id',1)
            ->get()[0];

        /* Check whether que worker are running or not */
        if (time() - $row->front_worker_update_time > 10){
            return response('Front worker is offline!<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        }

        if (time() - $row->execution_worker_update_time > 10){
            return response('Execution worker is offline!<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        }

        /* Check whether there are record in jobs table. If so - the que worker does not work */
        if (Job::all()->count() != 0){
            return response('Jobs table is not empty!<br>' . __FILE__, 422)
                ->header('Content-Type', 'text/plain');
        } else {
            $bot->update($request->all());
        }
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
