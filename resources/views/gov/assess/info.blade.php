{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        <a href="javascript:history.back();" class="btn">返回</a>
        @switch($sdata['assess']->code)
            @case(132)
                @if($sdata['household']->code==64)
                <a class="btn btn-success" type="button" href="{{route('g_assess_check',['id'=>$sdata['assess']->id,'item'=>$sdata['assess']->item_id])}}">
                    <i class="ace-icon fa fa-check bigger-110"></i>
                    审查评估报告
                </a>
                @endif
            @break
            @case(133)
            <a class="btn btn-success" type="button" disabled>
                <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                评估报告已通过审查
            </a>
            @break
            @case(134)
            <a class="btn btn-danger" type="button" disabled>
                <i class="ace-icon fa fa-close bigger-110"></i>
                评估报告已驳回
            </a>
            @break
            @case(135)
            <a class="btn btn-danger" type="button" disabled>
                <i class="ace-icon fa fa-close bigger-110"></i>
                被征户已反对
            </a>
            @break
            @case(136)
            <a class="btn btn-success" type="button">
                <i class="ace-icon fa fa-check-square-o bigger-110"></i>
                被征户已同意
            </a>
            @break
        @endswitch
    </div>

    <div class="widget-container-col ui-sortable">
        <div class="widget-box ui-sortable-handle">
            <div class="widget-header">
                <h5 class="widget-title">评估汇总</h5>
            </div>

            <div class="widget-body">
                <div class="widget-main row">
                    <div class="col-sm-6 col-xs-12">
                        <div class="profile-user-info profile-user-info-striped">

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 地址： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['assess']->itemland->address}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房号： </div>
                                <div class="profile-info-value">
                                <span class="editable editable-click">
                                    {{$sdata['assess']->itembuilding->building}}栋{{$sdata['assess']->household->unit}}单元{{$sdata['assess']->household->floor}}楼{{$sdata['assess']->household->number}}号
                                </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 类型： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        {{$sdata['assess']->household->type}}
                                    </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 是否有资产： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        {{$sdata['assess']->household->householddetail->has_assets}}
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-sm-6 col-xs-12">
                        <div class="profile-user-info profile-user-info-striped">

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房产评估： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{number_format($sdata['assess']->estate,2)}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 资产评估： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{number_format($sdata['assess']->assets,2)}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 评估总价： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{number_format($sdata['assess']->assets+$sdata['assess']->estate,2)}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 状态： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata['assess']->state->name}}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tabbable">
        <ul class="nav nav-tabs padding-12 tab-color-blue background-blue">
            <li class="active">
                <a data-toggle="tab" href="#estate" aria-expanded="true">房产评估</a>
            </li>

            @if($sdata['assess']->household->householddetail->getOriginal('has_assets')==1)
            <li class="">
                <a data-toggle="tab" href="#assets" aria-expanded="false">资产评估</a>
            </li>
            @endif
        </ul>

        <div class="tab-content">
            <div id="estate" class="tab-pane active">
                @if(filled($sdata['estate']))
                <div class="profile-user-info profile-user-info-striped">

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 评估机构： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{$sdata['estate']->company->name}}</span>
                        </div>
                    </div>

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 主要房屋评估总价： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{number_format($sdata['estate']->main_total,2)}}</span>
                        </div>
                    </div>

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 附属物评估总价： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{number_format($sdata['estate']->tag_total,2)}}</span>
                        </div>
                    </div>
                    
                    <div class="profile-info-row">
                        <div class="profile-info-name"> 状态： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{$sdata['estate']->state->name}}</span>
                        </div>
                    </div>

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 评估报告： </div>
                        <div class="profile-info-value">
                            <ul class="ace-thumbnails clearfix img-content">
                                @if(filled($sdata['estate']->picture))
                                    @foreach($sdata['estate']->picture as $pic)
                                        <li>
                                            <div>
                                                <img width="120" height="120" src="{{$pic}}" alt="加载失败">
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
                        </div>
                    </div>
                </div>
                
                <table class="table table-hover table-bordered">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>名称</th>
                        <th>用途</th>
                        <th>结构</th>
                        <th>朝向</th>
                        <th>面积</th>
                        <th>评估单价</th>
                        <th>评估总价</th>
                        <th>状态</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(filled($sdata['estate']->estatebuildings))
                        @foreach($sdata['estate']->estatebuildings as $estatebuilding)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$estatebuilding->name}}</td>
                                <td>{{$estatebuilding->realbuildinguse->name}}</td>
                                <td>{{$estatebuilding->buildingstruct->name}}</td>
                                <td>{{$estatebuilding->direct}}</td>
                                <td>{{number_format($estatebuilding->real_outer,2)}}</td>
                                <td>{{number_format($estatebuilding->price,2)}}</td>
                                <td>{{number_format($estatebuilding->amount,2)}}</td>
                                <td>{{$estatebuilding->state->name}}</td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>

                @else

                    暂无有效的房产评估数据
                @endif
            </div>

            @if($sdata['assess']->household->householddetail->getOriginal('has_assets')==1)
            <div id="assets" class="tab-pane">
                @if(filled($sdata['assets']))
                <div class="profile-user-info profile-user-info-striped">

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 评估机构： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{$sdata['assets']->company->name}}</span>
                        </div>
                    </div>

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 状态： </div>
                        <div class="profile-info-value">
                            <span class="editable editable-click">{{$sdata['assets']->state->name}}</span>
                        </div>
                    </div>

                    <div class="profile-info-row">
                        <div class="profile-info-name"> 评估报告： </div>
                        <div class="profile-info-value">
                            <ul class="ace-thumbnails clearfix img-content">
                                @if(filled($sdata['assets']->picture))
                                @foreach($sdata['assets']->picture as $pic)
                                    <li>
                                        <div>
                                            <img width="120" height="120" src="{{$pic}}" alt="加载失败">
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
                        </div>
                    </div>
                </div>
                @else

                    暂无有效的资产评估数据
                @endif
            </div>
            @endif
        </div>
    </div>

@endsection

{{-- 样式 --}}
@section('css')
    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}" />

@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>
        $('.img-content').viewer();
    </script>

@endsection