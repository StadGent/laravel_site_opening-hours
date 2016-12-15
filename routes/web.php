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
    return view('main');
})->middleware('auth:web');

Auth::routes();

Route::get('/register/confirm/{token}', 'Auth\RegisterController@showSetPassword');
Route::post('/register/confirm/{token}', 'Auth\RegisterController@completeRegistration');

Route::get('/home', 'HomeController@index');

Route::resource('/api/users', 'UsersController');
Route::resource('/api/services', 'ServicesController');
Route::resource('/api/openinghours', 'OpeninghoursController');
Route::resource('/api/calendars', 'CalendarsController');
Route::resource('/api/channels', 'ChannelController');
Route::post('/api/roles', 'RolesController@update');
Route::delete('/api/roles', 'RolesController@destroy');
