<?php

use Illuminate\Support\Facades\Route;

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

Route::post('auth/login', 'AuthController@login');

Route::get('login/{provider}', 'AuthController@redirect');
Route::get('login/{provider}/callback','AuthController@callback');

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('auth/me', 'AuthController@me');
});
