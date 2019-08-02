<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Nova API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register additional API routes for Nova. These
| routes are loaded by the service provider of this tool. They will
| be available for all tools to use, not only this one. Have fun!
|
*/

Route::post('/metrics', 'DashboardMetricController@index');
Route::post('/metrics/{metric}', 'DashboardMetricController@show');
Route::post('/{resource}/metrics', 'MetricController@index');
Route::post('/{resource}/metrics/{metric}', 'MetricController@show');
Route::post('/{resource}/{resourceId}/metrics/{metric}', 'DetailMetricController@show');