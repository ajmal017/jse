<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exchange;

class ExchangesList extends Controller
{
    public function index()
    {
        return Exchange::paginate();
    }
}
