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

// Route::group(['middleware' => 'auth:api'], function() {

	/**
	 * Issues
	 */
	Route::group(['prefix' => 'issues'], function() {

		// Index
		Route::get('/', [
			'uses' => 'IssuesController@index'
		]);

	});

// });
