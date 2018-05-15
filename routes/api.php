<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::any('/test','household\HomeController@index');


Route::namespace('api')->group(function (){
    Route::any('/login','IndexController@index')->name('login'); //登录
    Route::any('/home', function (Request $request) {
        return $request->path();
    });

});
