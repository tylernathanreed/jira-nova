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

/**
 * Jira API
 */
Route::group(['prefix' => 'jira-api'], function() {

	/**
	 * Issues
	 */
	Route::group(['prefix' => 'issues'], function() {

		// Index
		Route::get('/', [
			'uses' => 'IssuesController@index'
		]);

	});

});

/**
 * Unauthenticated Routes
 */
Route::get('/', 'PagesController@index')->name('pages.index');
