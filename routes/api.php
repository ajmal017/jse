<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});

Route::group(['prefix' => 'trading'], function ($router) {
    Route::get('history/{id}', 'Exchange\HistoryBars@load');
});

Route::apiResources(['exchange' => 'ExchangeController']); // http://jse.kk/api/exchange
Route::apiResources(['account' => 'AccountController']); // http://jse.kk/api/account
Route::apiResources(['symbol' => 'SymbolController']); // http://jse.kk/api/symbol
Route::apiResources(['bot' => 'BotController']); // http://jse.kk/api/bot
Route::apiResources(['strategy' => 'StrategyController']); // http://jse.kk/api/strategy
Route::apiResources(['backtest' => 'BacktestController']);


