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
    // calendars
    Route::put('/calendars/{calendar}', 'UI\CalendarsController@update')->middleware('hasRoleInService');
    Route::patch('/calendars/{calendar}', 'UI\CalendarsController@update')->middleware('hasRoleInService');
    Route::delete('/calendars/{calendar}', 'UI\CalendarsController@destory')->middleware('hasRoleInService');

    // channels
    Route::post('/channels/{channel}', 'UI\ChannelController@store')->middleware('hasRoleInService');
    Route::put('/channels/{channel}', 'UI\ChannelController@update')->middleware('hasRoleInService');
    Route::patch('/channels/{channel}', 'UI\ChannelController@update')->middleware('hasRoleInService');
    Route::delete('/channels/{channel}', 'UI\ChannelController@destory')->middleware('hasRoleInService');
    // subset
    Route::get('/services/{service}/channels', 'UI\ChannelController@getFromService');

    // openinghours
    Route::get('/openinghours/{openinghours}', 'UI\OpeninghoursController@show');
    Route::post('/openinghours/{openinghours}', 'UI\OpeninghoursController@store')->middleware('hasRoleInService');
    Route::put('/openinghours/{openinghours}', 'UI\OpeninghoursController@update')->middleware('hasRoleInService');
    Route::patch('/openinghours/{openinghours}', 'UI\OpeninghoursController@update')->middleware('hasRoleInService');
    Route::delete('/openinghours/{openinghours}', 'UI\OpeninghoursController@destory')->middleware('hasRoleInService');

    // Presets (refactor to holidays)
    Route::get('/presets', 'UI\PresetsController@index');

    // roles
    Route::put('/roles', 'UI\RolesController@update');
    Route::patch('/roles', 'UI\RolesController@update');
    Route::delete('/roles', 'UI\RolesController@destroy');

    // services
    Route::get('/services', 'UI\ServicesController@index');
    Route::get('/services/{service}', 'UI\ServicesController@show');
    Route::put('/services/{service}', 'UI\ServicesController@update')->middleware('hasRoleInService');
    Route::patch('/services/{service}', 'UI\ServicesController@update')->middleware('hasRoleInService');

    // users
    Route::get('/users', 'UI\UsersController@index')->middleware('admin');
    Route::post('/users', 'UI\UsersController@store')->middleware('admin');
    Route::delete('/users/{user}', 'UI\UsersController@destory')->middleware('admin');
    // subset
    Route::get('/services/{service}/users', 'UI\UsersController@getFromService')
        ->middleware('hasRoleInService');
});

/****************/
/*  Public API  */
/****************/

/* Work models **/
Route::get('/services', 'ServicesController@index');
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
