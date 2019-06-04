<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exchange;

class ExchangesList extends Controller
{
    public function index()
    {
        /*$signal = Signal::where('id', $id)->get();
        // 10 clients bug is here
        // return(['execution' => Execution::latest()->where('signal_id', $id)->paginate(10), 'signal' => $signal]);
        // LogToFile::add(__FILE__, json_encode(Execution::latest()->where('signal_id', $id)->get()));
        return(['execution' => Execution::latest()->where('signal_id', $id)->get(), 'signal' => $signal]);*/

        return Exchange::paginate();
    }
}
