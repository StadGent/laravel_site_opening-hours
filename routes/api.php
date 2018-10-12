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

Route::group(['prefix' => 'v1'], function () {

    /*************************/
    /*  Authenticated UI API  */
    /*************************/
    
    Route::group(['prefix' => 'ui', 'middleware' => 'auth:api'], function () {
        // calendars
        Route::post('/calendars', 'UI\CalendarsController@store');
        Route::put('/calendars/{calendar}', 'UI\CalendarsController@update');
        Route::patch('/calendars/{calendar}', 'UI\CalendarsController@update');
        Route::delete('/calendars/{calendar}', 'UI\CalendarsController@destroy');

        // channels
        Route::get('/services/{service}/channels', 'UI\ChannelController@getFromService');
        Route::post('/services/{service}/channels', 'UI\ChannelController@store');
        Route::put('/services/{service}/channels/{channel}', 'UI\ChannelController@update');
        Route::delete('/services/{service}/channels/{channel}', 'UI\ChannelController@destroy');

        // openinghours
        Route::get('/openinghours/{openinghours}', 'UI\OpeninghoursController@show');
        Route::post('/openinghours', 'UI\OpeninghoursController@store');
        Route::put('/openinghours/{openinghours}', 'UI\OpeninghoursController@update');
        Route::patch('/openinghours/{openinghours}', 'UI\OpeninghoursController@update');
        Route::delete('/openinghours/{openinghours}', 'UI\OpeninghoursController@destroy');

        // Presets (refactor to holidays)
        Route::get('/presets', 'UI\PresetsController@index');

        // roles
        Route::patch('/roles', 'UI\RolesController@update');
        Route::delete('/roles', 'UI\RolesController@destroy');

        // services
        Route::get('/services', 'UI\ServicesController@index');
        Route::put('/services/{service}', 'UI\ServicesController@update');
        Route::patch('/services/{service}', 'UI\ServicesController@update');

        // users
        Route::get('/users', 'UI\UsersController@index');
        Route::get('/users/{user}', 'UI\UsersController@show');
        Route::delete('/users/{user}', 'UI\UsersController@destroy');
        // subset
        Route::get('/services/{service}/users', 'UI\UsersController@getFromService');
        Route::post('/inviteuser', 'UI\UsersController@invite');
    });

    /****************/
    /*  Public API  */
    /****************/

    /* Work models **/
    Route::get('/services', 'ServicesController@index');
    Route::get('/services/{service}', 'ServicesController@show');
    Route::get('/services/{service}/channels', 'ChannelController@getFromService');
    Route::get('/services/{service}/channels/{channel}', 'ChannelController@show');

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

    /* Types */
    Route::get('/types', 'TypeController@index');
});
