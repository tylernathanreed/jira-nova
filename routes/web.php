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
 * Authentication Routes
 */
Auth::routes([
	'register' => false,
	'reset' => false,
	'verify' => false
]);

/**
 * Authenticated Routes
 */
Route::group(['middleware' => 'auth'], function() {

	/**
	 * Issues
	 */
	Route::group(['prefix' => 'issues'], function() {

		// Issues
		Route::get('/', [
			'as' => 'issues.index',
			'uses' => 'IssuesController@index'
		]);

		// Issues
		Route::post('/', [
			'as' => 'issues.submit',
			'uses' => 'IssuesController@submit'
		]);

	});

});

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
