<?php
/*=========  【征收管理端】  ==========*/
/*============================================ 【基础资料】 ================================================*/
/*---------- 公房单位 ----------*/
Route::get('/adminunit','AdminunitController@index')->name('g_adminunit');
Route::any('/adminunit_add','AdminunitController@add')->name('g_adminunit_add');
Route::get('/adminunit_info','AdminunitController@info')->name('g_adminunit_info');
Route::any('/adminunit_edit','AdminunitController@edit')->name('g_adminunit_edit');
/*---------- 银行 ----------*/
Route::get('/bank','BankController@index')->name('g_bank');
Route::any('/bank_add','BankController@add')->name('g_bank_add');
Route::get('/bank_info','BankController@info')->name('g_bank_info');
Route::any('/bank_edit','BankController@edit')->name('g_bank_edit');
/*---------- 建筑结构类型 ----------*/
Route::get('/buildingstruct','BuildingstructController@index')->name('g_buildingstruct');
Route::any('/buildingstruct_add','BuildingstructController@add')->name('g_buildingstruct_add');
Route::get('/buildingstruct_info','BuildingstructController@info')->name('g_buildingstruct_info');
Route::any('/buildingstruct_edit','BuildingstructController@edit')->name('g_buildingstruct_edit');
/*---------- 建筑用途 ----------*/
Route::get('/buildinguse','BuildinguseController@index')->name('g_buildinguse');
Route::any('/buildinguse_add','BuildinguseController@add')->name('g_buildinguse_add');
Route::get('/buildinguse_info','BuildinguseController@info')->name('g_buildinguse_info');
Route::any('/buildinguse_edit','BuildinguseController@edit')->name('g_buildinguse_edit');
/*---------- 特殊人群 ----------*/
Route::get('/crowd','CrowdController@index')->name('g_crowd');
Route::any('/crowd_add','CrowdController@add')->name('g_crowd_add');
Route::get('/crowd_info','CrowdController@info')->name('g_crowd_info');
Route::any('/crowd_edit','CrowdController@edit')->name('g_crowd_edit');
/*---------- 必备附件分类 ----------*/
Route::get('/filecate','FilecateController@index')->name('g_filecate');
Route::any('/filecate_add','FilecateController@add')->name('g_filecate_add');
Route::any('/filecate_edit','FilecateController@edit')->name('g_filecate_edit');
/*---------- 房屋户型 ----------*/
Route::get('/layout','LayoutController@index')->name('g_layout');
Route::any('/layout_add','LayoutController@add')->name('g_layout_add');
Route::get('/layout_info','LayoutController@info')->name('g_layout_info');
Route::any('/layout_edit','LayoutController@edit')->name('g_layout_edit');
/*---------- 民族 ----------*/
Route::get('/nation','NationController@index')->name('g_nation');
Route::any('/nation_add','NationController@add')->name('g_nation_add');
Route::get('/nation_info','NationController@info')->name('g_nation_info');
Route::any('/nation_edit','NationController@edit')->name('g_nation_edit');
/*---------- 其他补偿事项 ----------*/
Route::get('/object','ObjectController@index')->name('g_object');
Route::any('/object_add','ObjectController@add')->name('g_object_add');
Route::get('/object_info','ObjectController@info')->name('g_object_info');
Route::any('/object_edit','ObjectController@edit')->name('g_object_edit');
/*---------- 社会风险评估调查话题 ----------*/
Route::get('/topic','TopicController@index')->name('g_topic');
Route::any('/topic_add','TopicController@add')->name('g_topic_add');
Route::get('/topic_info','TopicController@info')->name('g_topic_info');
Route::any('/topic_edit','TopicController@edit')->name('g_topic_edit');
/*---------- 土地性质 ----------*/
Route::get('/landprop','LandpropController@index')->name('g_landprop');
Route::any('/landprop_add','LandpropController@add')->name('g_landprop_add');
Route::any('/landprop_edit','LandpropController@edit')->name('g_landprop_edit');
/*---------- 土地来源 ----------*/
Route::any('/landsource_add','LandsourceController@add')->name('g_landsource_add');
Route::any('/landsource_edit','LandsourceController@edit')->name('g_landsource_edit');
/*---------- 土地权益状况 ----------*/
Route::any('/landstate_add','LandstateController@add')->name('g_landstate_add');
Route::any('/landstate_edit','LandstateController@edit')->name('g_landstate_edit');
/*============================================ 【房源】 ================================================*/
/*---------- 房源管理机构 ----------*/
Route::get('/housecompany','HousecompanyController@index')->name('g_housecompany');
Route::any('/housecompany_add','HousecompanyController@add')->name('g_housecompany_add');
Route::get('/housecompany_info','HousecompanyController@info')->name('g_housecompany_info');
Route::any('/housecompany_edit','HousecompanyController@edit')->name('g_housecompany_edit');
Route::any('/housecompany_del','HousecompanyController@del')->name('g_housecompany_del');
/*---------- 房源社区 ----------*/
Route::any('/housecommunity','HousecommunityController@index')->name('g_housecommunity');
Route::any('/housecommunity_add','HousecommunityController@add')->name('g_housecommunity_add');
Route::get('/housecommunity_info','HousecommunityController@info')->name('g_housecommunity_info');
Route::any('/housecommunity_edit','HousecommunityController@edit')->name('g_housecommunity_edit');
Route::any('/housecommunity_del','HousecommunityController@del')->name('g_housecommunity_del');
/*---------- 房源户型图 ----------*/
Route::any('/houselayoutimg','HouselayoutimgController@index')->name('g_houselayoutimg');
Route::any('/houselayoutimg_add','HouselayoutimgController@add')->name('g_houselayoutimg_add');
Route::get('/houselayoutimg_info','HouselayoutimgController@info')->name('g_houselayoutimg_info');
Route::any('/houselayoutimg_edit','HouselayoutimgController@edit')->name('g_houselayoutimg_edit');
Route::any('/houselayoutimg_del','HouselayoutimgController@del')->name('g_houselayoutimg_del');
/*---------- 房源 ----------*/
Route::any('/house','HouseController@index')->name('g_house');
Route::any('/house_add','HouseController@add')->name('g_house_add');
Route::get('/house_info','HouseController@info')->name('g_house_info');
Route::any('/house_edit','HouseController@edit')->name('g_house_edit');
Route::any('/house_del','HouseController@del')->name('g_house_del');
/*---------- 房源-评估单价 ----------*/
Route::any('/houseprice','HousepriceController@index')->name('g_houseprice');
Route::any('/houseprice_add','HousepriceController@add')->name('g_houseprice_add');
Route::get('/houseprice_info','HousepriceController@info')->name('g_houseprice_info');
Route::any('/houseprice_edit','HousepriceController@edit')->name('g_houseprice_edit');
/*---------- 房源-购置管理费单价 ----------*/
Route::get('/housemanageprice','HousemanagepriceController@index')->name('g_housemanageprice');
Route::any('/housemanageprice_add','HousemanagepriceController@add')->name('g_housemanageprice_add');
Route::get('/housemanageprice_info','HousemanagepriceController@info')->name('g_housemanageprice_info');
Route::any('/housemanageprice_edit','HousemanagepriceController@edit')->name('g_housemanageprice_edit');

/*============================================ 【评估机构】 ================================================*/
/*---------- 评估机构 ----------*/
Route::any('/company','CompanyController@index')->name('g_company');
Route::any('/company_add','CompanyController@add')->name('g_company_add');
Route::get('/company_info','CompanyController@info')->name('g_company_info');
Route::any('/company_edit','CompanyController@edit')->name('g_company_edit');
Route::any('/company_del','CompanyController@del')->name('g_company_del');
Route::any('/company_status','CompanyController@status')->name('g_company_status');
/*---------- 评估机构(操作员) ----------*/
Route::get('/companyuser','CompanyuserController@index')->name('g_companyuser');
Route::get('/companyuser_info','CompanyuserController@info')->name('g_companyuser_info');
/*---------- 评估机构(评估师) ----------*/
Route::get('/companyvaluer','CompanyvaluerController@index')->name('g_companyvaluer');
Route::get('/companyvaluer_info','CompanyvaluerController@info')->name('g_companyvaluer_info');
/*============================================ 【项目】 ================================================*/
/*+++++++++++++++ 【调查建档】 ++++++++++++++++*/
/*---------- 项目-地块 ----------*/
Route::any('/itemland','ItemlandController@index')->name('g_itemland');
Route::any('/itemland_add','ItemlandController@add')->name('g_itemland_add');
Route::get('/itemland_info','ItemlandController@info')->name('g_itemland_info');
Route::any('/itemland_edit','ItemlandController@edit')->name('g_itemland_edit');
Route::any('/itemland_del','ItemlandController@del')->name('g_itemland_del');
/*---------- 项目-地块楼栋 ----------*/
Route::any('/itembuilding','ItembuildingController@index')->name('g_itembuilding');
Route::any('/itembuilding_add','ItembuildingController@add')->name('g_itembuilding_add');
Route::get('/itembuilding_info','ItembuildingController@info')->name('g_itembuilding_info');
Route::any('/itembuilding_edit','ItembuildingController@edit')->name('g_itembuilding_edit');
Route::any('/itembuilding_del','ItembuildingController@del')->name('g_itembuilding_del');
/*---------- 项目-公共附属物 ----------*/
Route::any('/itempublic','ItempublicController@index')->name('g_itempublic');
Route::any('/itempublic_add','ItempublicController@add')->name('g_itempublic_add');
Route::get('/itempublic_info','ItempublicController@info')->name('g_itempublic_info');
Route::any('/itempublic_edit','ItempublicController@edit')->name('g_itempublic_edit');
Route::any('/itempublic_del','ItempublicController@del')->name('g_itempublic_del');
/*---------- 项目-地块户型 ----------*/
Route::any('/landlayout','LandlayoutController@index')->name('g_landlayout');
Route::any('/landlayout_add','LandlayoutController@add')->name('g_landlayout_add');
Route::get('/landlayout_info','LandlayoutController@info')->name('g_landlayout_info');
Route::any('/landlayout_edit','LandlayoutController@edit')->name('g_landlayout_edit');
Route::any('/landlayout_del','LandlayoutController@del')->name('g_landlayout_del');

/*---------- 项目-被征收户账号 ----------*/
Route::any('/household','HouseholdController@index')->name('g_household');
Route::any('/household_add','HouseholdController@add')->name('g_household_add');
Route::any('/household_edit','HouseholdController@edit')->name('g_household_edit');
Route::any('/household_del','HouseholdController@del')->name('g_household_del');
/*---------- 项目-被征收户详细信息 ----------*/
Route::any('/householddetail','HouseholddetailController@index')->name('g_householddetail');
Route::any('/householddetail_add','HouseholddetailController@add')->name('g_householddetail_add');
Route::any('/householddetail_info','HouseholddetailController@info')->name('g_householddetail_info');
Route::any('/householddetail_edit','HouseholddetailController@edit')->name('g_householddetail_edit');
/*---------- 项目-被征户-家庭成员 ----------*/
Route::any('/householdmember','HouseholdmemberController@index')->name('g_householdmember');
Route::any('/householdmember_add','HouseholdmemberController@add')->name('g_householdmember_add');
Route::get('/householdmember_info','HouseholdmemberController@info')->name('g_householdmember_info');
Route::any('/householdmember_edit','HouseholdmemberController@edit')->name('g_householdmember_edit');
Route::any('/householdmember_del','HouseholdmemberController@del')->name('g_householdmember_del');
/*---------- 项目-被征户-家庭成员（特殊人群） ----------*/
Route::any('/householdmembercrowd_add','HouseholdmembercrowdController@add')->name('g_householdmembercrowd_add');
Route::get('/householdmembercrowd_info','HouseholdmembercrowdController@info')->name('g_householdmembercrowd_info');
Route::any('/householdmembercrowd_edit','HouseholdmembercrowdController@edit')->name('g_householdmembercrowd_edit');
Route::any('/householdmembercrowd_del','HouseholdmembercrowdController@del')->name('g_householdmembercrowd_del');
/*---------- 项目-被征户-其他补偿事项 ----------*/
Route::any('/householdobject','HouseholdobjectController@index')->name('g_householdobject');
Route::any('/householdobject_add','HouseholdobjectController@add')->name('g_householdobject_add');
Route::get('/householdobject_info','HouseholdobjectController@info')->name('g_householdobject_info');
Route::any('/householdobject_edit','HouseholdobjectController@edit')->name('g_householdobject_edit');
Route::any('/householdobject_del','HouseholdobjectController@del')->name('g_householdobject_del');
/*---------- 项目-被征户-房屋建筑 ----------*/
Route::any('/householdbuilding','HouseholdbuildingController@index')->name('g_householdbuilding');
Route::any('/householdbuilding_add','HouseholdbuildingController@add')->name('g_householdbuilding_add');
Route::get('/householdbuilding_info','HouseholdbuildingController@info')->name('g_householdbuilding_info');
Route::any('/householdbuilding_edit','HouseholdbuildingController@edit')->name('g_householdbuilding_edit');
Route::any('/householdbuilding_del','HouseholdbuildingController@del')->name('g_householdbuilding_del');
/*---------- 项目-被征户-资产 ----------*/
Route::any('/householdassets','HouseholdassetsController@index')->name('g_householdassets');
Route::any('/householdassets_add','HouseholdassetsController@add')->name('g_householdassets_add');
Route::get('/householdassets_info','HouseholdassetsController@info')->name('g_householdassets_info');
Route::any('/householdassets_edit','HouseholdassetsController@edit')->name('g_householdassets_edit');
Route::any('/householdassets_del','HouseholdassetsController@del')->name('g_householdassets_del');

/*---------- 项目-被征户-产权争议解决 ----------*/
Route::any('/householdright','HouseholdrightController@index')->name('g_householdright');
Route::any('/householdright_add','HouseholdrightController@add')->name('g_householdright_add');
Route::get('/householdright_info','HouseholdrightController@info')->name('g_householdright_info');
/*---------- 项目-被征户-违建处理 ----------*/
Route::any('/householdbuildingdeal','HouseholdbuildingdealController@index')->name('g_householdbuildingdeal');
Route::any('/householdbuildingdeal_status','HouseholdbuildingdealController@status')->name('g_householdbuildingdeal_status');
Route::any('/householdbuildingdeal_add','HouseholdbuildingdealController@add')->name('g_householdbuildingdeal_add');
Route::get('/householdbuildingdeal_infos','HouseholdbuildingdealController@infos')->name('g_householdbuildingdeal_infos');
Route::get('/householdbuildingdeal_info','HouseholdbuildingdealController@info')->name('g_householdbuildingdeal_info');
/*---------- 项目-被征户-面积争议解决 ----------*/
Route::any('/householdbuildingarea','HouseholdbuildingareaController@index')->name('g_householdbuildingarea');
Route::any('/householdbuildingarea_add','HouseholdbuildingareaController@add')->name('g_householdbuildingarea_add');
Route::get('/householdbuildingarea_info','HouseholdbuildingareaController@info')->name('g_householdbuildingarea_info');
/*---------- 项目-测绘报告 ----------*/
Route::any('/landlayout_reportlist','LandlayoutController@reportlist')->name('g_landlayout_reportlist');
Route::any('/landlayout_reportadd','LandlayoutController@reportadd')->name('g_landlayout_reportadd');
Route::any('/landlayout_reportinfo','LandlayoutController@reportinfo')->name('g_landlayout_reportinfo');
/*---------- 项目-资产确认 ----------*/
Route::any('/householdassets_report','HouseholdassetsController@report')->name('g_householdassets_report');
Route::any('/householdassets_reportlist','HouseholdassetsController@reportlist')->name('g_householdassets_reportlist');
Route::any('/householdassets_reportadd','HouseholdassetsController@reportadd')->name('g_householdassets_reportadd');
Route::any('/householdassets_reportinfo','HouseholdassetsController@reportinfo')->name('g_householdassets_reportinfo');
/*---------- 项目-房产确认 ----------*/
Route::any('/buildingconfirm','HouseholddetailController@buildingconfirm')->name('g_buildingconfirm');
Route::any('/buildingrelated','HouseholddetailController@buildingrelated')->name('g_buildingrelated');
Route::any('/buildingrelated_com','HouseholddetailController@buildingrelated_com')->name('g_buildingrelated_com');
Route::any('/relatedcom_info','HouseholddetailController@relatedcom_info')->name('g_relatedcom_info');
Route::any('/buildingconfirm_info','HouseholddetailController@buildingconfirm_info')->name('g_buildingconfirm_info');
Route::any('/edit_status','HouseholddetailController@edit_status')->name('g_edit_status');
/*---------- 项目-公共附属物确认 ----------*/
Route::any('/landiist','ItempublicController@landiist')->name('g_public_landiist');
Route::any('/publiclist','ItempublicController@publiclist')->name('g_publiclist');
Route::any('/publicinfo','ItempublicController@publicinfo')->name('g_publicinfo');

/*+++++++++++++++ 【征收决定】 ++++++++++++++++*/
/*---------- 项目-自选社会风险评估调查话题 ----------*/
Route::any('/itemtopic','ItemtopicController@index')->name('g_itemtopic');
Route::any('/itemtopic_add','ItemtopicController@add')->name('g_itemtopic_add');
Route::get('/itemtopic_info','ItemtopicController@info')->name('g_itemtopic_info');
Route::any('/itemtopic_edit','ItemtopicController@edit')->name('g_itemtopic_edit');
/*---------- 项目-其他补偿事项单价 ----------*/
Route::any('/itemobject','ItemobjectController@index')->name('g_itemobject');
Route::any('/itemobject_add','ItemobjectController@add')->name('g_itemobject_add');
Route::get('/itemobject_info','ItemobjectController@info')->name('g_itemobject_info');
Route::any('/itemobject_edit','ItemobjectController@edit')->name('g_itemobject_edit');
/*---------- 项目-补偿科目说明 ----------*/
Route::any('/itemsubject','ItemsubjectController@index')->name('g_itemsubject');
Route::any('/itemsubject_add','ItemsubjectController@add')->name('g_itemsubject_add');
Route::get('/itemsubject_info','ItemsubjectController@info')->name('g_itemsubject_info');
Route::any('/itemsubject_edit','ItemsubjectController@edit')->name('g_itemsubject_edit');

/*+++++++++++++++ 【通知公告】 ++++++++++++++++*/
/*---------- 项目-内部通知 ----------*/
Route::any('/itemnotice','ItemnoticeController@index')->name('g_itemnotice');
Route::any('/itemnotice_add','ItemnoticeController@add')->name('g_itemnotice_add');
Route::get('/itemnotice_info','ItemnoticeController@info')->name('g_itemnotice_info');
Route::any('/itemnotice_edit','ItemnoticeController@edit')->name('g_itemnotice_edit');

/*+++++++++++++++ 【房源控制】 ++++++++++++++++*/
/*---------- 项目-冻结房源 ----------*/
Route::any('/itemhouse','ItemhouseController@index')->name('g_itemhouse');
Route::any('/itemhouse_add','ItemhouseController@add')->name('g_itemhouse_add');
Route::any('/itemhouse_del','ItemhouseController@del')->name('g_itemhouse_del');  // 释放房源

/*+++++++++++++++ 【入围机构】 ++++++++++++++++*/
/*---------- 项目-选定评估机构 ----------*/
Route::any('/itemcompany','ItemcompanyController@index')->name('g_itemcompany');
Route::any('/itemcompany_add','ItemcompanyController@add')->name('g_itemcompany_add');
Route::get('/itemcompany_info','ItemcompanyController@info')->name('g_itemcompany_info');
Route::any('/itemcompany_edit','ItemcompanyController@edit')->name('g_itemcompany_edit');


/*+++++++++++++++ 【Excel导出导入】 ++++++++++++++++*/
/*---------- 房源 ----------*/
Route::any('/house_export','HouseController@house_export')->name('g_house_export');
/*---------- 房源导入示例 ----------*/
Route::any('/house_import_demo','HouseController@house_import_demo')->name('g_house_import_demo');
/*---------- 房源导入 ----------*/
Route::any('/import_house','HouseController@import_house')->name('g_import_house');
/*---------- 房源导出失败文件 ----------*/
Route::any('/export_errordata','HouseController@export_errordata')->name('g_export_errordata');