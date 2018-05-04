<?php
/*
|--------------------------------------------------------------------------
| 征收管理端 限制登录路由
|--------------------------------------------------------------------------
*/
/*---------- 首页 ----------*/
Route::any('/home','HomeController@index')->name('h_home');

/*---------- 社会稳定性风险评估 ----------*/
Route::any('/itemrisk_info','ItemriskController@info')->name('h_itemrisk_info');
Route::any('/itemrisk_add','ItemriskController@add')->name('h_itemrisk_add');
Route::any('/itemrisk_edit','ItemriskController@edit')->name('h_itemrisk_edit');

/*---------- 评估公司投票 ----------*/
Route::any('/itemcompanyvote','CompanyvoteController@index')->name('h_itemcompanyvote');//投票机构
Route::any('/itemcompanyvote_info','CompanyvoteController@info')->name('h_itemcompanyvote_info');//我的投票
Route::any('/itemcompanyvote_add','CompanyvoteController@add')->name('h_itemcompanyvote_add');//被征户投票
Route::any('/itemcompanyvote_edit','CompanyvoteController@edit')->name('h_itemcompanyvote_edit');
Route::any('/itemcompany','ItemcompanyController@index')->name('h_itemcompany');//入围机构
Route::any('/company_info','CompanyController@info')->name('h_company_info');//评估机构详情

/*---------- 被征户摸底情况 ----------*/
Route::any('/householddetail','HouseholddetailController@index')->name('h_householddetail');
Route::any('/householddetail_info','HouseholddetailController@info')->name('h_householddetail_info');
Route::any('/householdbuilding_info','HouseholdbuildingController@info')->name('h_householdbuilding_info');
Route::any('/householdmember_info','HouseholdmemberController@info')->name('h_householdmember_info');
Route::any('/householdmembercrowd_info','HouseholdmembercrowdController@info')->name('h_householdmembercrowd_info');
Route::any('/householdobject_info','HouseholdobjectController@info')->name('h_householdobject_info');
Route::any('/householddetail_area','HouseholddetailController@area')->name('h_householddetail_area');   //处理面积争议

/*确权确户*/
Route::any('/householdright','HouseholdrightController@index')->name('h_householdright');
Route::any('/householdright_info','HouseholdrightController@info')->name('h_householdright_info');
Route::any('/householdright_confirm','HouseholdrightController@confirm')->name('h_householdright_confirm');

/*---------- 兑付--汇总 ----------*/
Route::any('/pay','PayController@index')->name('h_pay');
Route::any('/pay_info','PayController@info')->name('h_pay_info');
Route::any('/pay_edit','PayController@edit')->name('h_pay_edit');

/*请求签约*/
Route::any('/payhouse_add','PayhouseController@add')->name('h_payhouse_add');

/*确认签约*/
Route::any('/pay_confirm','PayController@confirm')->name('h_pay_confirm');

Route::any('/paysubject_info','PaysubjectController@info')->name('h_paysubject_info');

/*---------- 评估报告 ----------*/
Route::any('/assess_info','AssessController@info')->name('h_assess');
Route::any('/assess_confirm','AssessController@confirm')->name('h_assess_confirm');


/*个人中心*/
Route::any('/itemhousehold_info','ItemhouseholdController@info')->name('h_itemhousehold_info');
Route::any('/itemhousehold_edit','ItemhouseholdController@edit')->name('h_itemhousehold_edit');
Route::any('/itemhousehold_password','ItemhouseholdController@password')->name('h_itemhousehold_password');

/*---------- 房源 ----------*/
Route::any('/itemhouse','ItemhouseController@index')->name('h_itemhouse');
Route::any('/itemhouse_info','ItemhouseController@info')->name('h_itemhouse_info');

/*选择房源（缓存表）*/
Route::any('/payhousebak_add','PayhousebakController@add')->name('h_payhousebak_add');
Route::any('/payhousebak','PayhousebakController@index')->name('h_payhousebak');
Route::any('/payhousebak_remove','PayhousebakController@remove')->name('h_payhousebak_remove');

/*通知公告*/
Route::any('/news_info','NewsController@info')->name('h_news_info');

/*面积争议确认*/
Route::any('/buildingarea_confirm','HouseholdbuildingareaController@confirm')->name('h_buildingarea_confirm');




