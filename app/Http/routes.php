<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
Route::group(['prefix' => '/v1'],  function() {
    Route::post('/user/reset-password', 'UserController@resetPassword');
    Route::post('/user/change-password', 'UserController@changePassword');
    Route::post('/user/login', 'UserController@login');
    Route::post('/user/logout', 'UserController@logout');
    Route::post('/user/register', 'UserController@register');
});