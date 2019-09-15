<?php

use Illuminate\Http\Request;

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});

Route::group(['prefix' => 'trading'], function () {
    Route::get('history/{id}', 'Exchange\HistoryBars@load');
    Route::get('markets/{id}', 'Exchange\MarketsController@load');
});

Route::apiResources(['exchange' => 'ExchangeController']); // http://jse.kk/api/exchange
Route::apiResources(['account' => 'AccountController']); // http://jse.kk/api/account
Route::apiResources(['symbol' => 'SymbolController']); // http://jse.kk/api/symbol
Route::apiResources(['bot' => 'BotController']); // http://jse.kk/api/bot
Route::apiResources(['strategy' => 'StrategyController']); // http://jse.kk/api/strategy
Route::apiResources(['backtest' => 'BacktestController']);
Route::apiResources(['job' => 'JobController']); // http://jse.kk/api/job
Route::apiResources(['logo' => 'LogoController']); // http://jse.kk/api/logo
Route::apiResources(['user' => 'UserController']); // http://jse.kk/api/user



