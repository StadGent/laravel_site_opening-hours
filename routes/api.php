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

Route::group(['middleware'=>'auth:api'],
    function (){
        Route::resource ('/users', 'UsersController');
        Route::resource ('/services', 'ServicesController');
        Route::resource ('/openinghours', 'OpeninghoursController');
        Route::resource ('/calendars', 'CalendarsController');
        Route::resource ('/channels', 'ChannelController');
        Route::post     ('/roles', 'RolesController@update');
        Route::delete   ('/roles', 'RolesController@destroy');
    }
);

Route::get      ('/query', 'QueryController@query');
