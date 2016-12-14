<?php


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

Route::resource('/users', 'UsersController');
Route::resource('/services', 'ServicesController');
Route::resource('/openinghours', 'OpeninghoursController');
Route::resource('/calendars', 'CalendarsController');
Route::resource('/channels', 'ChannelController');
Route::resource('/roles', 'RolesController');
