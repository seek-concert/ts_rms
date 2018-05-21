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



Route::namespace('household')->prefix('app')->group(function (){
    Route::any('/test','IndexController@test');

    Route::any('/time',function (){
        return  response()->json(['time'=>date('Y-m-d H:i:s'),'code'=>'test']) ;
    });


    Route::any('/','IndexController@index')->name('a_index'); //登录页
    Route::any('/login','IndexController@login')->name('a_login'); //登录
    Route::any('/logout','IndexController@logout')->name('a_logout'); //退出

    Route::middleware('CheckApi')->group(function (){

        /*---------- 工具 ----------*/
        Route::any('/error','ToolsController@error')->name('a_error'); // 错误提示
        Route::any('/upl','ToolsController@upl')->name('a_upl'); // 文件上传

        /*首页*/
        Route::any('/home','HomeController@index')->name('a_home');

        /*通知公告*/
        Route::any('/news_info','NewsController@info')->name('a_news_info');

        /*评估机构投票*/
        Route::any('/vote','CompanyvoteController@index')->name('a_vote');
        Route::any('/vote_add','CompanyvoteController@add')->name('a_vote_add');
        Route::any('/vote_edit','CompanyvoteController@edit')->name('a_vote_edit');
        Route::any('/vote_info','CompanyvoteController@info')->name('a_vote_info');
        Route::any('/company_info','CompanyController@info')->name('a_company_info');//评估机构详情

        /*入围机构*/
        Route::any('/itemcompany','ItemcompanyController@index')->name('a_itemcompany');

        /*产权*/
        Route::any('/householddetail','HouseholddetailController@index')->name('a_householddetail');
        Route::any('/householddetail_info','HouseholddetailController@info')->name('a_householddetail_info');
        Route::any('/householdbuilding_info','HouseholdbuildingController@info')->name('a_householdbuilding_info');
        Route::any('/householdmember_info','HouseholdmemberController@info')->name('a_householdmember_info');
        Route::any('/householdmembercrowd_info','HouseholdmembercrowdController@info')->name('a_householdmembercrowd_info');
        Route::any('/householdobject_info','HouseholdobjectController@info')->name('a_householdobject_info');
        Route::any('/householddetail_area','HouseholddetailController@area')->name('a_householddetail_area');   //处理面积争议

        /*确权确户*/
        Route::any('/householdright','HouseholdrightController@index')->name('a_householdright');
        Route::any('/householdright_info','HouseholdrightController@info')->name('a_householdright_info');
        Route::any('/householdright_confirm','HouseholdrightController@confirm')->name('a_householdright_confirm');
        Route::any('/householdbuildingdeal_info','HouseholdbuildingdealController@info')->name('a_householdbuildingdeal_info');

        /*选择房源（缓存表）*/
        Route::any('/payhousebak_add','PayhousebakController@add')->name('a_payhousebak_add');
        Route::any('/payhousebak','PayhousebakController@index')->name('a_payhousebak');
        Route::any('/payhousebak_remove','PayhousebakController@remove')->name('a_payhousebak_remove');

        /*意见调查*/
        Route::any('/itemrisk_info','ItemriskController@info')->name('a_itemrisk_info');
        Route::any('/itemrisk_add','ItemriskController@add')->name('a_itemrisk_add');
        Route::any('/itemrisk_edit','ItemriskController@edit')->name('a_itemrisk_edit');

        /*征收补偿*/
        Route::any('/pay','PayController@index')->name('a_pay');
        Route::any('/pay_info','PayController@info')->name('a_pay_info');
        Route::any('/pay_edit','PayController@edit')->name('a_pay_edit');

        /*请求签约*/
        Route::any('/payhouse_add','PayhouseController@add')->name('a_payhouse_add');

        /*确认签约*/
        Route::any('/pay_confirm','PayController@confirm')->name('a_pay_confirm');

        /*评估报告*/
        Route::any('/assess_info','AssessController@info')->name('a_assess');
        Route::any('/assess_confirm','AssessController@confirm')->name('a_assess_confirm');

        /*个人中心*/
        Route::any('/itemhousehold_info','ItemhouseholdController@info')->name('a_itemhousehold_info');
        Route::any('/itemhousehold_edit','ItemhouseholdController@edit')->name('a_itemhousehold_edit');
        Route::any('/itemhousehold_password','ItemhouseholdController@password')->name('a_itemhousehold_password');

        /*---------- 房源 ----------*/
        Route::any('/itemhouse','ItemhouseController@index')->name('a_itemhouse');
        Route::any('/itemhouse_info','ItemhouseController@info')->name('a_itemhouse_info');

        /*面积争议确认*/
        Route::any('/buildingarea_confirm','HouseholdbuildingareaController@confirm')->name('a_buildingarea_confirm');

    });

});
