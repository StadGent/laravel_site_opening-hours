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
/** Authenticated UI API */
/*************************/
Route::group(['middleware' => 'auth:api'],
    function () {
        Route::resource('/users', 'UsersController');
        Route::resource('/openinghours', 'OpeninghoursController');
        Route::resource('/calendars', 'CalendarsController');
        Route::resource('/channels', 'ChannelController');
        Route::post('/roles', 'RolesController@update');
        Route::delete('/roles', 'RolesController@destroy');
        Route::get('/presets', 'PresetsController@index');
    }
);

/****************/
/** Public API **/
/****************/

/** Work models **/
Route::resource('/services', 'ServicesController');
Route::get('/services/{service}/channels', 'ChannelController@getFromService');

/**************************/
/** Openinghours results **/
/**************************/

/** Get openinghours of all channels for a service with a from and untill parameter **/
Route::get('/services/{service}/openinghours', 'QueryController@fromTillAction');
/** Get openinghours of all channels for a service for a predefined period **/
Route::get('/services/{service}/openinghours/day', 'QueryController@dayAction');
Route::get('/services/{service}/openinghours/week', 'QueryController@weekAction');
Route::get('/services/{service}/openinghours/month', 'QueryController@monthAction');
Route::get('/services/{service}/openinghours/year', 'QueryController@yearAction');
/** Get the current status of all channels for a service **/
Route::get('/services/{service}/open-now', 'QueryController@nowOpenAction');

/** Get openinghours of a specific channel for a service with a from and untill parameter **/
Route::get('/services/{service}/channels/{channel}/openinghours', 'QueryController@fromTillAction');
/** Get openinghours of a specific channel of a service for a predefined period **/
Route::get('/services/{service}/channels/{channel}/openinghours/day', 'QueryController@dayAction');
Route::get('/services/{service}/channels/{channel}/openinghours/week', 'QueryController@weekAction');
Route::get('/services/{service}/channels/{channel}/openinghours/month', 'QueryController@monthAction');
Route::get('/services/{service}/channels/{channel}/openinghours/year', 'QueryController@yearAction');
/** Get the current status of a specific channel for a service **/
Route::get('/services/{service}/channels/{channel}/open-now', 'QueryController@nowOpenAction');
