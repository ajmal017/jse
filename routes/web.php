<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/* Get exchanges list used in the app. Not all available exchanges from ccxc. Called from accounts.vue */
Route::get('/api/exchangeslist', 'ExchangesList@index');

/* Get signal tables. Called from ChartSignalsTable.vue */
Route::get('/api/signalstable/{id}', 'SignalsTableController@index');

/* Get status of a worker. Called from Bots.vue */
Route::get('/api/workerstatus/{id}', 'WorkerStatusController@get');

