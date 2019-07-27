<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::group([
    'namespace' => 'Laravel\Nova\Http\Controllers',
    'domain' => config('nova.domain', null),
    'as' => 'nova.api.',
    'prefix' => 'nova-api',
    'middleware' => 'nova'
], function() {
    Route::post('/metrics', 'DashboardMetricController@index');
    Route::post('/metrics/{metric}', 'DashboardMetricController@show');
    Route::post('/{resource}/metrics', 'MetricController@index');
    Route::post('/{resource}/metrics/{metric}', 'MetricController@show');
    Route::post('/{resource}/{resourceId}/metrics/{metric}', 'DetailMetricController@show');
});