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

/*************************/
/*  Authenticated UI API  */
/*************************/

Route::group(['prefix' => 'ui', 'middleware' => 'auth:api'], function () {
    Route::resource('/calendars', 'UI\CalendarsController');
    Route::resource('/channels', 'UI\ChannelController');
    Route::resource('/channels/getChannelsByService', 'UI\ChannelController@getFromService');
    Route::resource('/openinghours', 'UI\OpeninghoursController');
    Route::get('/presets', 'UI\PresetsController@index');
    Route::post('/roles', 'UI\RolesController@update');
    Route::delete('/roles', 'UI\RolesController@destroy');
    Route::resource('/services', 'UI\ServicesController');
    Route::resource('/users', 'UI\UsersController');
    Route::resource('/users/getUsersByService', 'UI\UsersController@getUsersByService');
});

/****************/
/*  Public API  */
/****************/

/* Work models **/
Route::get('/services', 'ServicesController@index');
Route::get('/services/create', function () {
    throw new UnexpectedValueException();
});
Route::get('/services/{service}/edit', function () {
    throw new UnexpectedValueException();
});
Route::get('/services/{service}', 'ServicesController@show');
Route::get('/services/{service}/channels', 'ChannelController@getFromService');

/**************************/
/*  Openinghours results  */
/**************************/

/* Get openinghours of all channels for a service with a from and untill parameter */
Route::get('/services/{service}/openinghours', 'QueryController@fromTillAction');
/* Get openinghours of all channels for a service for a predefined period */
Route::get('/services/{service}/openinghours/day', 'QueryController@dayAction');
Route::get('/services/{service}/openinghours/week', 'QueryController@weekAction');
Route::get('/services/{service}/openinghours/month', 'QueryController@monthAction');
Route::get('/services/{service}/openinghours/year', 'QueryController@yearAction');
/* Get the current status of all channels for a service */
Route::get('/services/{service}/open-now', 'QueryController@nowOpenAction');

/* Get openinghours of a specific channel for a service with a from and untill parameter */
Route::get('/services/{service}/channels/{channel}/openinghours', 'QueryController@fromTillAction');
/* Get openinghours of a specific channel of a service for a predefined period */
Route::get('/services/{service}/channels/{channel}/openinghours/day', 'QueryController@dayAction');
Route::get('/services/{service}/channels/{channel}/openinghours/week', 'QueryController@weekAction');
Route::get('/services/{service}/channels/{channel}/openinghours/month', 'QueryController@monthAction');
Route::get('/services/{service}/channels/{channel}/openinghours/year', 'QueryController@yearAction');
/* Get the current status of a specific channel for a service */
Route::get('/services/{service}/channels/{channel}/open-now', 'QueryController@nowOpenAction');
