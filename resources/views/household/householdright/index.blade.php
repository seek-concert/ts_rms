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
                            面积争议
                        </a>
                    </li>

                    @if(isset($sdata['householdright']))
                        <li class="">
                            <a data-toggle="tab" href="#householdright" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                产权争议
                            </a>
                        </li>
                    @endif

                    @if(filled($sdata['householdbuildings']))
                        <li class="">
                            <a data-toggle="tab" href="#householdbuildingdeal" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                合法性认定
                            </a>
                        </li>
                    @endif

                    @if(isset($sdata['householdassets']))
                        <li class="">
                            <a data-toggle="tab" href="#householdassets" aria-expanded="false">
                                <i class="green ace-icon fa fa-home bigger-120"></i>
                                资产确认
                            </a>
                        </li>
                    @endif

                    @if(isset($sdata['householdestate']))
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
                        @if(isset($sdata['householdbuildingarea']))
                            <div class="profile-user-info profile-user-info-striped">

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
                            @else
                            <span class="editable editable-click">暂无面积争议</span>
                        @endif
                        @if(isset($sdata['householddetail']) && $sdata['householddetail']->getOriginal('area_dispute')==2)
                            <form class="form-horizontal" role="form" action="{{route('h_buildingarea_confirm')}}"
                                  method="post">
                                {{csrf_field()}}
                                <div class="form-group">
                                    <label class="col-sm-3 control-label no-padding-right" for="area_dispute">
                                        面积争议处理结果确认： </label>
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


                    @if(isset($sdata['householdright']))
                        <div id="householdright" class="tab-pane fade ">

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 争议处理状态：{{$sdata['householddetail']->dispute}}</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        @switch($infos->getOriginal('area_dispute'))
                                            @case(1)
                                            等待测绘,请完善测绘报告
                                            @break

                                            @case(2)
                                            已测绘,等待被征收户确认
                                            @break

                                            @case(3)
                                            面积明确，处理已完成
                                            @break

                                            @case(4)
                                            争议待处理
                                            @break

                                            @default
                                            争议处理已完成
                                    @endswitch
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 争议解决结果：</div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        <ul class="ace-thumbnails clearfix img-content viewer">
                                              @if(isset($sdata->picture))
                                                @foreach($sdata->picture as $pic)
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
                    @endif

                    @if(filled($sdata['householdbuildings']))
                        <div id="householdbuildingdeal" class="tab-pane fade">
                            <table class="table table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>建筑状态</th>
                                    <th>名称</th>
                                    <th>地块</th>
                                    <th>楼栋</th>
                                    <th>批准用途</th>
                                    <th>实际用途</th>
                                    <th>结构类型</th>
                                    <th>楼层</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($sdata['householdbuildings'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->state->name}}</td>
                                        <td>{{$infos->name}}</td>
                                        <td>{{$infos->itemland->address}}</td>
                                        <td>{{$infos->itembuilding->building}}</td>
                                        <td>{{$infos->defbuildinguse->name}}</td>
                                        <td>{{$infos->realbuildinguse->name}}</td>
                                        <td>{{$infos->buildingstruct->name}}</td>
                                        <td>{{$infos->floor}}</td>
                                        <td>
                                            @if($infos->getOriginal('code') == 94 || $infos->getOriginal('code') == 95)
                                                <a href="{{route('h_householdbuildingdeal_info',['id'=>$infos->id])}}"
                                                   class="btn btn-sm">处理详情</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="dataTables_info" id="dynamic-table_info" role="status"
                                         aria-live="polite">
                                        共 @if($code=='success') {{ count($sdata['householdbuildings']) }} @else 0 @endif
                                        条数据
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
                                @if($code=='success')
                                    @foreach($sdata['householdassetss'] as $infos)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$infos->itemland->address}}</td>
                                            <td>{{$infos->itembuilding->building}}</td>
                                            <td>{{$infos->name}}</td>
                                            <td>{{$infos->num_unit}}</td>
                                            <td>{{$infos->number}}</td>
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
                @if(filled($sdata) && in_array($sdata['householddetail']->getOriginal('area_dispute'),[0,3,5]) && in_array($sdata['householddetail']->getOriginal('dispute'),[0,2]) && $sdata['building_check']==0 && $sdata['householddetail']->household->code==62 )
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
                    @if($sdata['householddetail']->household->code==63)
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


