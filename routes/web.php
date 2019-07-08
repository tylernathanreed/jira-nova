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

Route::get('/', [
	'as' => 'issues.index',
	'uses' => 'IssuesController@index'
]);

Route::post('/', [
	'as' => 'issues.submit',
	'uses' => 'IssuesController@submit'
]);

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
