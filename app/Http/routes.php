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
Route::group(['prefix' => 'api/v1'],  function() {
    Route::get('login', 'UserController@login');
    Route::get('logout', 'UserController@logout');
    Route::post('register', 'UserController@register');
    Route::post('reset-password', 'UserController@resetPassword');
    Route::post('change-password', 'UserController@changePassword');
    Route::get('payment-methods', 'PaymentMethodController@getPaymentMethods');
    Route::get('payment-method', 'PaymentMethodController@getPaymentMethod');
    Route::put('payment-method', 'PaymentMethodController@updatePaymentMethod');
    Route::delete('payment-method', 'PaymentMethodController@deletePaymentMethod');
});