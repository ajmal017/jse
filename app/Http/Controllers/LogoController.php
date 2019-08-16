<?php

namespace App\Http\Controllers;

use App\Classes\LogToFile;
use Illuminate\Http\Request;
use App\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return([
            'logoFileName' => DB::table('settings')->where('key', 'logo')->value('value'),
            'appName' => DB::table('settings')->where('key', 'app_name')->value('value')
        ]);
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
        // Get the filename
        $existingLogo = DB::table('settings')
            ->where('key', 'logo')
            ->value('value');

        // Delete the filename
        unlink(public_path() . '/' . $existingLogo);

        $exploded = explode(',', $request->image);
        $decoded = base64_decode($exploded[1]);

        if(Str::contains($exploded[0], 'jpg'))
            $extension = 'jpg';
        else
            $extension = 'png';

        $fileName = Str::random(10) . '.' . $extension;
        $path = public_path() . '/' . $fileName;
        file_put_contents($path, $decoded);

        DB::table('settings')
            ->where('key', 'logo')
            ->delete();

        DB::table('settings')
            ->insert([
                'key' => 'logo',
                'value' => $fileName
            ]);

        return $decoded;
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
        DB::table('settings')
            ->where('key','app_name')
            ->update([
                'value' => $request['appName']
            ]);

        //return $request;
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
