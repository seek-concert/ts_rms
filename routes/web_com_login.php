<?php
/*
|--------------------------------------------------------------------------
| 征收管理端 限制登录路由
|--------------------------------------------------------------------------
*/
/*---------- 首页 ----------*/
Route::any('/home','HomeController@index')->name('c_home');

/*---------- 个人中心 ----------*/
Route::get('/userself','UserselfController@index')->name('c_userself'); // 个人信息
Route::any('/userself_edit','UserselfController@edit')->name('c_userself_edit'); // 修改信息
Route::any('/userself_pwd','UserselfController@password')->name('c_userself_pwd'); // 修改密码
