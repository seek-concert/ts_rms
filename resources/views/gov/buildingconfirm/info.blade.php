{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        <a class="btn" href="{{route('g_buildingconfirm',['item'=>$edata['item']->id])}}">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </div>

    <form class="form-horizontal" role="form" action="{{route('g_edit_status')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="item" value="{{$edata['item_id']}}">
        <input type="hidden" name="detail_id" value="{{$edata['detail_id']}}">
        <span class="red">温馨提示：只有征收管理端和评估机构端两边被征收户信息和房屋建筑信息一样（当页面不会出现<i
                    class="red fa fa-times fa-2x"></i>时）才能进行房产确认！</span>
        <div class="tabbable">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a data-toggle="tab" href="#detail" aria-expanded="true">
                        <i class="green ace-icon fa fa-building bigger-120"></i>
                        被征收户信息
                    </a>
                </li>

                <li class="">
                    <a data-toggle="tab" href="#buildings" aria-expanded="false">
                        <i class="green ace-icon fa fa-building bigger-120"></i>
                        房屋建筑信息列表
                    </a>
                </li>
            </ul>


            <div class="tab-content">
                <div id="detail" class="tab-pane fade active in">
                    <div class="profile-user-info profile-user-info-striped">
                        <div class="profile-info-row">
                            <div class="profile-info-name"> 征收意见：</div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$edata['household_detail']->agree}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 补偿方式：</div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$edata['household_detail']->repay_way}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            @if(filled($edata['household_detail']))

                                <input type="hidden" name="gov_id" value="{{$edata['household_detail']->household_id}}">
                                <div class="col-xs-6 col-sm-6 pricing-box">
                                    <div class="widget-box widget-color-dark">
                                        <div class="widget-header">
                                            <h5 class="widget-title bigger lighter">征收管理端</h5>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <div class="profile-user-info profile-user-info-striped">
                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 产权争议：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->dispute}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 面积争议：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->area_dispute}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 房屋状态：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->status}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 房屋产权证号：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->register}}</span>
                                                        </div>
                                                    </div>


                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 建筑面积：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->reg_outer}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 阳台面积：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->balcony}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 批准用途：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->defbuildinguse->name}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 实际用途：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->realbuildinguse->name}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 经营项目：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->business}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 资产评估：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->has_assets}}</span>
                                                        </div>
                                                    </div>
                                                    @if(isset($edata['household_detail']->picture))
                                                        @foreach($edata['household_detail']->picture as $names=>$picturepic)
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> {{$edata['detail_filecates'][$names]}}
                                                                    ：
                                                                </div>
                                                                <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                     <ul class="ace-thumbnails clearfix img-content viewer">
                                                                         @foreach($picturepic as $pics)
                                                                             <li>
                                                                                <div>
                                                                                    <img width="120" height="120"
                                                                                         src="{!! $pics !!}" alt="加载失败">
                                                                                    <div class="text">
                                                                                        <div class="inner">
                                                                                            <a onclick="preview(this)"><i
                                                                                                        class="fa fa-search-plus"></i></a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                             </li>
                                                                         @endforeach
                                                                    </ul>
                                                                </span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 被征收人签名：</div>
                                                        <div class="profile-info-value">
                                                        <span class="editable editable-click">
                                                             <ul class="ace-thumbnails clearfix img-content viewer">
                                                                     <li>
                                                                    <div>
                                                                        <img width="120" height="120"
                                                                             src="{{$edata['household_detail']->sign}}"
                                                                             alt="加载失败">
                                                                        <div class="text">
                                                                            <div class="inner">
                                                                                <a onclick="preview(this)"><i
                                                                                            class="fa fa-search-plus"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 创建时间：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->created_at}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 更新时间：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['household_detail']->updated_at}}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(filled($edata['estate']))
                                <div class="col-xs-6 col-sm-6 pricing-box">
                                    <div class="widget-box widget-color-dark">
                                        <div class="widget-header">
                                            <h5 class="widget-title bigger lighter">评估机构端</h5>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <input type="hidden" name="com_id" value="{{$edata['estate']->id}}">
                                                <div class="profile-user-info profile-user-info-striped">
                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 产权争议：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->dispute}}</span>
                                                            @if($edata['estate']->dispute != $edata['household_detail']->dispute)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 面积争议：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->area_dispute}}</span>
                                                            @if($edata['estate']->area_dispute != $edata['household_detail']->area_dispute)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 房屋状态：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->status}}</span>
                                                            @if($edata['estate']->status != $edata['household_detail']->status)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 房屋产权证号：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->register}}</span>
                                                            @if($edata['estate']->register != $edata['household_detail']->register)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>


                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 建筑面积：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->reg_outer}}</span>
                                                            @if($edata['estate']->reg_outer != $edata['household_detail']->reg_outer)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 阳台面积：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->balcony}}</span>
                                                            @if($edata['estate']->balcony != $edata['household_detail']->balcony)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 批准用途：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->defbuildinguse->name}}</span>
                                                            @if($edata['estate']->defbuildinguse->name != $edata['household_detail']->defbuildinguse->name)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 实际用途：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->realbuildinguse->name}}</span>
                                                            @if($edata['estate']->realbuildinguse->name != $edata['household_detail']->realbuildinguse->name)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 经营项目：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->business}}</span>
                                                            @if($edata['estate']->business != $edata['household_detail']->business)
                                                                <i class=" red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 资产评估：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->has_assets}}</span>
                                                            @if($edata['estate']->has_assets != $edata['household_detail']->has_assets)
                                                                <i class="red fa fa-times fa-2x"></i>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if(isset($edata['estate']->house_pic))
                                                        @foreach($edata['estate']->house_pic as $names=>$picturepic)
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> {{$edata['com_filecates'][$names]}}
                                                                    ：
                                                                </div>
                                                                <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                     <ul class="ace-thumbnails clearfix img-content viewer">
                                                                         @foreach($picturepic as $pics)
                                                                             <li>
                                                                                <div>
                                                                                    <img width="120" height="120"
                                                                                         src="{!! $pics !!}" alt="加载失败">
                                                                                    <div class="text">
                                                                                        <div class="inner">
                                                                                            <a onclick="preview(this)"><i
                                                                                                        class="fa fa-search-plus"></i></a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                             </li>
                                                                         @endforeach
                                                                    </ul>
                                                                </span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 被征收人签名：</div>
                                                        <div class="profile-info-value">
                                                        <span class="editable editable-click">
                                                             <ul class="ace-thumbnails clearfix img-content viewer">
                                                                     <li>
                                                                    <div>
                                                                        <img width="120" height="120"
                                                                             src="{{$edata['estate']->sign}}"
                                                                             alt="加载失败">
                                                                        <div class="text">
                                                                            <div class="inner">
                                                                                <a onclick="preview(this)"><i
                                                                                            class="fa fa-search-plus"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 创建时间：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->created_at}}</span>
                                                        </div>
                                                    </div>

                                                    <div class="profile-info-row">
                                                        <div class="profile-info-name"> 更新时间：</div>
                                                        <div class="profile-info-value">
                                                            <span class="editable editable-click">{{$edata['estate']->updated_at}}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div id="buildings" class="tab-pane fade">
                    <div class="row">
                        <div class="col-xs-12">
                            @if(filled($edata['householdbuildings'])&&filled($edata['estatebuildings']))
                                @foreach($edata['householdbuildings'] as $k=>$infos)
                                    <div class="col-xs-6 col-sm-6 pricing-box">
                                        <div class="widget-box widget-color-dark">
                                            <div class="widget-header">
                                                <h5 class="widget-title bigger lighter">征收管理端[第{{$k+1}}栋]</h5>
                                            </div>

                                            <div class="widget-body">
                                                <div class="widget-main">
                                                    <div class="profile-user-info profile-user-info-striped">
                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 状态：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->state->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 名称：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 地块地址：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->itemland->address}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 楼栋：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->itembuilding->building}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 楼层：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->floor}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 朝向：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->direct}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 结构：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->buildingstruct->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 登记建筑面积：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->reg_outer}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 阳台面积：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->balcony}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 实际建筑面积：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->real_outer}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 批准用途：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->defbuildinguse->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 实际用途：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->realbuildinguse->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 户型名称：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->landlayout->name}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 户型面积：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->landlayout->area}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 户型图：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                          @if(filled($infos->landlayout->gov_img))
                                                                            @foreach($infos->landlayout->gov_img as $pics)
                                                                                <li>
                                                                                    <div>
                                                                                        <img width="120" height="120"
                                                                                             src="{!! $pics !!}"
                                                                                             alt="加载失败">
                                                                                        <div class="text">
                                                                                            <div class="inner">
                                                                                                <a onclick="preview(this)"><i
                                                                                                            class="fa fa-search-plus"></i></a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach
                                                                        @endif
                                                                    </ul>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 图片：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                          @if(isset($infos->picture))
                                                                            @foreach($infos->picture as $pic)
                                                                                <li>
                                                                                    <div>
                                                                                        <img width="120" height="120"
                                                                                             src="{!! $pic !!}"
                                                                                             alt="加载失败">
                                                                                        <div class="text">
                                                                                            <div class="inner">
                                                                                                <a onclick="preview(this)"><i
                                                                                                            class="fa fa-search-plus"></i></a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach
                                                                        @endif
                                                                    </ul>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 创建时间：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->created_at}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 更新时间：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->updated_at}}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($infos->id ==$edata['estatebuildings'][$k]->household_building_id )
                                        <div class="col-xs-6 col-sm-6 pricing-box">
                                            <div class="widget-box widget-color-dark">
                                                <div class="widget-header">
                                                    <h5 class="widget-title bigger lighter">评估机构端[第{{$k+1}}栋]</h5>
                                                </div>

                                                <div class="widget-body">
                                                    <div class="widget-main">
                                                        <div class="profile-user-info profile-user-info-striped">
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 状态：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->state->name}}</span>
                                                                    @if($infos->state->name != $edata['estatebuildings'][$k]->state->name)
                                                                        @if($edata['estatebuildings'][$k]->code == 90 or ($infos->code == 93 || $infos->code == 95))

                                                                        @else
                                                                            <i class="red fa fa-times fa-2x"></i>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 名称：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->name}}</span>
                                                                    @if($infos->name != $edata['estatebuildings'][$k]->name)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 地块地址：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->itemland->address}}</span>
                                                                    @if($infos->itemland->address != $edata['estatebuildings'][$k]->itemland->address)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 楼栋：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->itembuilding->building}}</span>
                                                                    @if($infos->itembuilding->building != $edata['estatebuildings'][$k]->itembuilding->building)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 楼层：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->floor}}</span>
                                                                    @if($infos->floor != $edata['estatebuildings'][$k]->floor)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 朝向：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->direct}}</span>
                                                                    @if($infos->direct != $edata['estatebuildings'][$k]->direct)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 结构：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->buildingstruct->name}}</span>
                                                                    @if($infos->buildingstruct->name != $edata['estatebuildings'][$k]->buildingstruct->name)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 登记建筑面积：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->reg_outer}}</span>
                                                                    @if($infos->reg_outer != $edata['estatebuildings'][$k]->reg_outer)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 阳台面积：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->balcony}}</span>
                                                                    @if($infos->balcony != $edata['estatebuildings'][$k]->balcony)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 实际建筑面积：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->real_outer}}</span>
                                                                    @if($infos->real_outer != $edata['estatebuildings'][$k]->real_outer)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 批准用途：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->defbuildinguse->name}}</span>
                                                                    @if($infos->defbuildinguse->name != $edata['estatebuildings'][$k]->defbuildinguse->name)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 实际用途：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->realbuildinguse->name}}</span>
                                                                    @if($infos->realbuildinguse->name != $edata['estatebuildings'][$k]->realbuildinguse->name)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 户型名称：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->landlayout->name}}</span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 户型面积：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->landlayout->area}}</span>
                                                                    @if($infos->landlayout->area != $edata['estatebuildings'][$k]->landlayout->area)
                                                                        <i class="red fa fa-times fa-2x"></i>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 户型图：</div>
                                                                <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                          @if(filled($edata['estatebuildings'][$k]->landlayout->gov_img))
                                                                            @foreach($edata['estatebuildings'][$k]->landlayout->gov_img as $pics)
                                                                                <li>
                                                                                    <div>
                                                                                        <img width="120" height="120"
                                                                                             src="{!! $pics !!}"
                                                                                             alt="加载失败">
                                                                                        <div class="text">
                                                                                            <div class="inner">
                                                                                                <a onclick="preview(this)"><i
                                                                                                            class="fa fa-search-plus"></i></a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach
                                                                        @endif
                                                                    </ul>
                                                                </span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 图片：</div>
                                                                <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                          @if(isset($edata['estatebuildings'][$k]->picture))
                                                                            @foreach($edata['estatebuildings'][$k]->picture as $pic)
                                                                                <li>
                                                                                    <div>
                                                                                        <img width="120" height="120"
                                                                                             src="{!! $pic !!}"
                                                                                             alt="加载失败">
                                                                                        <div class="text">
                                                                                            <div class="inner">
                                                                                                <a onclick="preview(this)"><i
                                                                                                            class="fa fa-search-plus"></i></a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </li>
                                                                            @endforeach
                                                                        @endif
                                                                    </ul>
                                                                </span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 创建时间：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->created_at}}</span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 更新时间：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$edata['estatebuildings'][$k]->updated_at}}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>

                    </div>
                </div>
            </div>
            @if($edata['household_detail']->household->code>=62)
                <div class="clearfix form-actions" id="_sub">
                    <div class="col-md-offset-5 col-md-7">
                        <button class="btn" type="button">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            已确认信息
                        </button>
                    </div>
                </div>
            @else
                <div class="clearfix form-actions" id="_sub">
                    <div class="col-md-offset-5 col-md-7">
                        <button class="btn btn-info" type="button" onclick="sub(this)">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            确认信息
                        </button>
                    </div>
                </div>
            @endif


        </div>
    </form>
@endsection

{{-- 样式 --}}
@section('css')
    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}"/>
@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('js/func.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>
        $('.img-content').viewer('update');
        if ($('.fa-times').length == 0) {
            $('#_sub').css('display', 'block');
        } else {
            $('#_sub').css('display', 'none');
        }
    </script>
@endsection


