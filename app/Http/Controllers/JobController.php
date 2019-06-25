<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Job;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /**
         * Job unserialization.
         * Some fields are not json encoded but serialized.
         */
        $jobsObject = array();
        $records = DB::table('jobs')->get();
        $payload = null;
        $serializer = new \App\Classes\System\SerializeExtention();
        foreach ($records as $record){
            $payload = json_decode($record->payload);
            $finishArray = $serializer->toArray($payload);
            $command = unserialize($payload->data->command);

            $finishArray['id'] = $record->id;
            $finishArray['queue'] = $record->queue;
            $finishArray['attempts'] = $record->attempts;
            $finishArray['reserved_at'] = $record->reserved_at;
            $finishArray['available_at'] = $record->available_at;
            $finishArray['created_at'] = $record->created_at;


            $finishArray['data'] = $serializer->toArray($command);
            $finishArray['data']['chained'] = null;

            array_push($jobsObject, $finishArray);
        }
        return $jobsObject;
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
        //
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
        DB::table('jobs')->truncate();
    }
}
