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

Route::get('/', 'HomeController@index')->middleware('auth:web');

Auth::routes();

Route::get('/register/confirm/{token}', 'Auth\RegisterController@showSetPassword');
Route::post('/register/confirm/{token}', 'Auth\RegisterController@completeRegistration');
