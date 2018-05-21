{{-- 继承主体 --}}
@extends('household.layout')

{{-- 页面内容 --}}
@section('content')

    <div class="row">

        <div class="well-sm">
            <div class="tabbable">
                <ul class="nav nav-tabs" id="myTab">
                    <li class="active">
                        <a data-toggle="tab" href="#householdbuildingarea" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            建筑争议处理
                        </a>
                    </li>

                    @if(isset($sdata['householdright']))
                        <li class="">
                            <a data-toggle="tab" href="#householdright" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                产权争议处理
                            </a>
                        </li>
                    @endif

                    <li class="">
                        <a data-toggle="tab" href="#householddetail" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                           被征户信息
                        </a>
                    </li>

                    @if(filled($sdata['householdassets']))
                        <li class="">
                            <a data-toggle="tab" href="#householdassets" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                资产确认
                            </a>
                        </li>
                    @endif

                    @if(!is_null($sdata['householdestate']))
                        <li class="">
                            <a data-toggle="tab" href="#buildingconfirm" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                房产确认
                            </a>
                        </li>
                    @endif

                    @if(isset($sdata['itempublics']))
                        <li class="">
                            <a data-toggle="tab" href="#pulblic" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                公共附属物
                            </a>
                        </li>
                    @endif
                </ul>


                <div class="tab-content">

                    <div id="householdbuildingarea" class="tab-pane fade active in">
                        <div class="row">
                            @if(isset($sdata['householdbuildings']))
                                <div class="col-xs-12" style="margin-bottom: 1%">
                                    @foreach($sdata['householdbuildings'] as $infos)
                                        <div class="col-xs-6 col-sm-3 pricing-box">
                                            <div class="widget-box widget-color-dark">
                                                <div class="widget-header">
                                                    <h5 class="widget-title bigger lighter">{{$infos->name}}</h5>
                                                </div>

                                                <div class="widget-body">
                                                    <div class="widget-main">

                                                        <div class="profile-user-info profile-user-info-striped">

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 地块：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$infos->itemland->address}}</span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 楼栋号：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$infos->itembuilding->building?$infos->itembuilding->building.'栋':''}}</span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 状态：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$infos->state->name}}</span>
                        @if(in_array($infos->code,[94,95]))     <a href="{{route('h_householdbuildingdeal_info')}}" >查看详情>></a>
                        @endif


                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 建筑面积：</div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$infos->real_outer}}</span>
                                                                </div>
                                                            </div>

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 测绘报告：</div>
                                                                <div class="col-sm-9">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                        @if($infos->landlayout->picture)
                                                                            @foreach($infos->landlayout->picture as $pic)
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
                                                                </div>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(isset($sdata['householdbuildingarea']))
                                <div class="profile-user-info profile-user-info-striped"  style="margin-bottom: 1%">

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 争议解决结果：</div>
                                        <div class="profile-info-value">
                                        <span class="editable editable-click">
                                            <ul class="ace-thumbnails clearfix img-content viewer">
                                                  @if(isset($sdata['householdbuildingarea']->picture))
                                                    @foreach($sdata['householdbuildingarea']->picture as $pic)
                                                        <li>
                                                            <div>
                                                                <img width="120" height="120" src="{!! $pic !!}"
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
                                            <span class="editable editable-click">{{$sdata['householdbuildingarea']->created_at}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 更新时间：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['householdbuildingarea']->updated_at}}</span>
                                        </div>
                                    </div>

                                </div>
                            @endif
                            @if(isset($sdata['householddetail']) && $sdata['householddetail']->getOriginal('area_dispute')==2)
                                    <div class="space-4"></div>
                                <h3 class="header smaller light-blue">&nbsp; &nbsp;  面积争议确认</h3>
                                <form class="form-horizontal" role="form" action="{{route('h_buildingarea_confirm')}}"
                                      method="post">
                                    {{csrf_field()}}
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label no-padding-right" for="area_dispute">
                                            面积争议处理结果确认：
                                        </label>
                                        <div class="col-sm-9 radio">
                                            <label>
                                                <input name="area_dispute" type="radio" class="ace" value="3">
                                                <span class="lbl">面积明确</span>
                                            </label>
                                            <label>
                                                <input name="area_dispute" type="radio" class="ace" value="4">
                                                <span class="lbl">有争议</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="space-4"></div>

                                    <div class="clearfix form-actions">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button class="btn btn-info" type="button" onclick="sub(this)">
                                                <i class="ace-icon fa fa-check bigger-110"></i>
                                                保存
                                            </button>
                                            &nbsp;&nbsp;&nbsp
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>

                    </div>

                    @if(isset($sdata['householdright']))
                        <div id="householdright" class="tab-pane fade ">
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 争议处理状态：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 争议解决结果：</div>
                                    <div class="profile-info-value">
                                         <span class="editable editable-click">
                                        <ul class="ace-thumbnails clearfix img-content viewer">
                                              @if(isset($sdata['householdright']->picture))
                                                @foreach($sdata['householdright']->picture as $pic)
                                                    <li>
                                                        <div>
                                                            <img width="120" height="120" src="{!! $pic !!}" alt="加载失败">
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
                                        <span class="editable editable-click">{{$sdata['householdright']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdright']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>
                            <h3 class="header smaller light-green">&nbsp; &nbsp;  处理详情</h3>
                            <table class="table table-hover table-bordered" style="margin-top: 1%">
                                <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>姓名</th>
                                    <th>身份证</th>
                                    <th>权属类型</th>
                                    <th>权属分配比例</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(filled($sdata['member']))
                                    @foreach($sdata['member'] as $infos)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$infos->name}}</td>
                                            <td>{{$infos->card_num}}</td>
                                            <td>
                                                {{$infos->holder}}
                                            </td>
                                            <td>{{$infos->portion}}%</td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>

                    @endif

                    @if(isset($sdata['householddetail']))
                        <div id="householddetail" class="tab-pane fade ">
                        <div class="profile-user-info profile-user-info-striped">
                            <div class="profile-info-row">
                                <div class="profile-info-name"> 地块：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->itemland->address}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 地址：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        {{$sdata['householddetail']->itembuilding->building}}栋{{$sdata['householddetail']->unit}}单元 {{$sdata['householddetail']->floor}}楼{{$sdata['householddetail']->number}}号
                                    </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房产类型：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->type}}</span>
                                </div>
                            </div>
                            @if($sdata['householddetail']->type>0)
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 公产单位名称：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->itemland->adminunit->name}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 公产单位地址：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->itemland->adminunit->addre}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 公产单位联系人：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->itemland->adminunit->contact_man}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 公产单位联系电话：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->itemland->adminunit->phone}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 公产单位详细信息：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->itemland->adminunit->infos}}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 产权争议：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->dispute}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 面积争议：</div>
                                <div class="profile-info-value">
                                        <span class="editable editable-click">
                                            {{$sdata['householddetail']->area_dispute}}
                                        </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 状态：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->state}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房屋产权证号：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->register}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 登记建筑面积：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->reg_outer}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 阳台面积：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->balcony}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 批准用途：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->defbuildinguse->name}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 实际用途：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->realbuildinguse->name}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 资产评估：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->has_assets}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 征收意见：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->agree}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 补偿方式：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->repay_way}}</span>
                                </div>
                            </div>

                            @if($sdata['householddetail']->getOriginal('repay_way')==1)
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-房源单价：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->house_price}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-房源面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->house_area}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-房源数量：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->house_num}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-房源地址：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->house_addr}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-增加面积单价：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->more_price}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权调换意向<br/>-房源户型：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householddetail']->layout->name}}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 其他意见：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->opinion}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 收件人：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->receive_man}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 收件电话：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->receive_tel}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 收件地址：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->receive_addr}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房源户型图：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                             @if(isset($sdata['householddetail']->layout_img))
                                                 @foreach($sdata['householddetail']->layout_img as $layoutpic)
                                                     <li>
                                                    <div>
                                                        <img width="120" height="120" src="{!! $layoutpic !!}"
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
                                <div class="profile-info-name"> 房屋证件：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                              @if(isset($sdata['householddetail']->picture))
                                                 @foreach($sdata['householddetail']->picture as $name=>$pictures)
                                                     @foreach($pictures as $pic)
                                                         <li>
                                                            <div>
                                                                <img width="120" height="120" src="{!! $pic !!}" alt="加载失败">
                                                                <div class="text">
                                                                    <div class="inner">
                                                                        <a onclick="preview(this)"><i
                                                                                    class="fa fa-search-plus"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                     @endforeach
                                                 @endforeach
                                             @endif
                                        </ul>
                                    </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房屋图片：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                              @if(isset($sdata['householddetail']->house_img))
                                                 @foreach($sdata['householddetail']->house_img as $housepic)
                                                     <li>
                                                <div>
                                                    <img width="120" height="120" src="{!! $housepic !!}" alt="加载失败">
                                                    <div class="text">
                                                        <div class="inner">
                                                            <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
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
                                <div class="profile-info-name"> 被征收人签名：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                                 <li>
                                                <div>
                                                    <img width="120" height="120"
                                                         src="{{$sdata['householddetail']->sign}}" alt="加载失败">
                                                    <div class="text">
                                                        <div class="inner">
                                                            <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
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
                                    <span class="editable editable-click">{{$sdata['householddetail']->created_at}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 更新时间：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['householddetail']->updated_at}}</span>
                                </div>
                            </div>
                        </div>
                        </div>
                    @endif

                    @if(isset($sdata['householdassets']))
                        <div id="householdassets" class="tab-pane fade">

                            <table class="table table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>地块</th>
                                    <th>楼栋</th>
                                    <th>名称</th>
                                    <th>计量单位</th>
                                    <th>确认数量</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(filled($sdata['householdassets']))
                                    @foreach($sdata['householdassets'] as $infos)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$infos->itemland->address}}</td>
                                            <td>{{$infos->itembuilding->building}}栋</td>
                                            <td>{{$infos->name}}</td>
                                            <td>{{$infos->num_unit}}</td>
                                            <td>{{$infos->number?$infos->number:'待确认'}}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="dataTables_info" id="dynamic-table_info" role="status"
                                         aria-live="polite">
                                        共 @if($code=='success') {{ count($sdata) }} @else 0 @endif 条数据
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endif

                    @if(isset($sdata['householdestate']))
                        <div id="buildingconfirm" class="tab-pane fade">
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权争议：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 面积争议：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->area_dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋状态：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->status}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋产权证号：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->register}}</span>
                                    </div>
                                </div>


                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 建筑面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->reg_outer}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 阳台面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->balcony}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 批准用途：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->defbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 实际用途：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->realbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 经营项目：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->business}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 资产评估：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->has_assets}}</span>
                                    </div>
                                </div>
                                @if(filled($sdata['householdestate']->picture))
                                    @foreach($sdata['householdestate']->picture as $names=>$picturepic)
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> {{$sdata['detail_filecates'][$names]}}：
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
                                                         src="{{$sdata['householdestate']->sign}}"
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
                                        <span class="editable editable-click">{{$sdata['householdestate']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['householdestate']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($sdata['itempublics']))
                        <div id="pulblic" class="tab-pane fade">
                            <div class="col-xs-12">
                                @foreach($sdata['itempublics'] as $infos)
                                    <div class="col-xs-6 col-sm-3 pricing-box">
                                        <div class="widget-box widget-color-dark">
                                            <div class="widget-header">
                                                <h5 class="widget-title bigger lighter">{{$infos->name}}</h5>
                                            </div>

                                            <div class="widget-body">
                                                <div class="widget-main">

                                                    <div class="profile-user-info profile-user-info-striped">

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 楼栋号：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->itembuilding->building?$infos->itembuilding->building.'栋':''}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 类型：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">@if($infos->itembuilding->building>0)
                                                                        楼栋公共附属物@else 地块公共附属物 @endif</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 名称：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->name}}</span>
                                                            </div>
                                                        </div>
                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 计量单位：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->num_unit}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 数量：</div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$infos->number}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 图片：</div>
                                                            <div class="col-sm-9">
                                                                <ul class="ace-thumbnails clearfix img-content viewer">
                                                                    @if($infos->gov_pic)
                                                                        @foreach($infos->gov_pic as $pic)
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
                                                            </div>
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="dataTables_info" id="dynamic-table_info" role="status"
                                         aria-live="polite">
                                        共 @if($code=='success') {{count($sdata['itempublics'])}} @else 0 @endif 条数据
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @if(filled($sdata['householddetail']) && in_array($sdata['householddetail']->getOriginal('area_dispute'),[0,3,5]) && in_array($sdata['householddetail']->getOriginal('dispute'),[0,2]) && $sdata['building_check']==0 && $sdata['householddetail']->household->code==62 )
                    <form class="form-horizontal" role="form" action="{{route('h_householdright_confirm')}}"
                          method="post">
                        {{csrf_field()}}
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-5 col-md-7">
                                <button class="btn btn-info" type="button" onclick="sub(this)">
                                    <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                                    确权确户
                                </button>
                            </div>
                        </div>
                    </form>
                    @endif
                    @if(filled($sdata['householddetail']) && $sdata['householddetail']->household->code==63)
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-5 col-md-7">
                        <button class="btn btn-success" type="button" disabled>
                            <i class="ace-icon fa fa-check-square bigger-110"></i>
                            已确权确户
                        </button>
                        </div>
                    </div>
                    @endif
            </div>
        </div>
    </div>
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
    </script>
@endsection


