{{-- 继承布局 --}}
@extends('household.layout')


{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">

        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>

    </div>
    <div class="col-xs-12 col-sm-12">
        <div class="widget-container-col ui-sortable">
            <div class="widget-box ui-sortable-handle">
                <div class="widget-header">
                    <h5 class="widget-title">面积争议详情</h5>

                    <div class="widget-toolbar">
                        <a href="#" data-action="collapse">
                            <i class="ace-icon fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>

                <div class="widget-body">
                    <div class="widget-main">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>面积争议</th>
                                <th>地块</th>
                                <th>楼栋</th>
                                <th>位置</th>
                                <th>房屋产权证号</th>
                                <th>征收意见</th>
                                <th>处理状态</th>
                            </tr>
                            </thead>
                            <tbody>

                            @if($code=='success')
                                @foreach($sdata as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->area_dispute}}</td>
                                        <td>{{$infos->itemland->address}}</td>
                                        <td>{{$infos->itembuilding->building}}</td>
                                        <td>
                                            {{$infos->household->unit?$infos->household->unit.'单元':''}}
                                            {{$infos->household->floor?$infos->household->floor.'楼':''}}
                                            {{$infos->household->number?$infos->household->number.'号':''}}
                                        </td>
                                        <td>{{$infos->register}}</td>
                                        <td>{{$infos->agree}}</td>
                                        <td>@if($infos->getOriginal('area_dispute')==1)
                                                等待测绘,请完善测绘报告
                                            @endif
                                            @if($infos->getOriginal('area_dispute')==2)
                                                已测绘,等待被征收户确认
                                            @endif
                                            @if($infos->getOriginal('area_dispute')==3)
                                                面积明确，处理已完成
                                            @endif
                                            @if($infos->getOriginal('area_dispute')==4)
                                                争议待处理
                                            @endif
                                            @if($infos->getOriginal('area_dispute')==5)
                                                争议处理已完成
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">
                                    共 @if($code=='success') {{ $sdata->total() }} @else 0 @endif 条数据，等待处理 <span
                                            class="red">{{$edata['wait_num']}}</span> 条
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="dataTables_paginate paging_simple_numbers" id="dynamic-table_paginate">
                                    @if($code=='success') {{ $sdata->links() }} @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12">
        <div class="widget-container-col ui-sortable">
            <div class="widget-box ui-sortable-handle">
                <div class="widget-header">
                    <h5 class="widget-title">争议面积结果确认</h5>

                    <div class="widget-toolbar">
                        <a href="#" data-action="collapse">
                            <i class="ace-icon fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="widget-body">
                    <div class="widget-main">
                        <form class="form-horizontal" role="form" action="{{route('h_householddetail_area')}}"
                              method="post">
                            {{csrf_field()}}

                            {{--<input type="hidden" name="id" value="{{$sdata->id}}">--}}
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"
                                       for="repay_way">面积争议结果确认： </label>
                                <div class="col-sm-9 radio">
                                    <label>
                                        <input name="area_dispute" type="radio" class="ace" value="3">
                                        <span class="lbl">面积确认</span>
                                    </label>
                                    <label>
                                        <input name="area_dispute" type="radio" class="ace" value="4">
                                        <span class="lbl">存在争议</span>
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
                                    &nbsp;&nbsp;&nbsp;
                                    <button class="btn" type="reset">
                                        <i class="ace-icon fa fa-undo bigger-110"></i>
                                        重置
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
@endsection