{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>

    </p>

    <div class="well-sm">
        <div class="tabbable">
            <ul class="nav nav-tabs" id="myTab">
                <li class="">
                    <a href="{{route('g_householdright',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-building bigger-120"></i>
                        产权争议
                    </a>
                </li>

                <li class="">
                    <a href="{{route('g_householdbuildingdeal',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        合法性认定
                    </a>
                </li>

                <li class="">
                    <a href="{{route('g_householdbuildingarea',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        面积争议
                    </a>
                </li>

                <li class="">
                    <a href="{{route('g_landlayout_reportlist',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        测绘报告
                    </a>
                </li>

                <li class="active">
                    <a data-toggle="tab" href="#householdassets" aria-expanded="true">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        资产确认
                    </a>
                </li>

                <li class="">
                    <a href="{{route('g_buildingconfirm',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        房产确认
                    </a>
                </li>

                <li class="">
                    <a href="{{route('g_public_landiist',['item'=>$edata['item_id']])}}">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        公共附属物确认
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <div id="householdbuildingarea" class="tab-pane fade">
                </div>

                <div id="householdbuildingdeal" class="tab-pane fade">
                </div>

                <div id="householdbuildingarea" class="tab-pane fade">

                </div>

                <div id="householdbuildingdeal" class="tab-pane fade">
                </div>

                <div id="householdassets" class="tab-pane fade active in">
                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th>序号</th>
                            <th>地块</th>
                            <th>楼栋</th>
                            <th>位置</th>
                            <th>房屋产权证号</th>
                            <th>征收意见</th>
                            <th>确认状态</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($code=='success')
                            @foreach($sdata as $infos)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$infos->itemland->address}}</td>
                                    <td>{{$infos->itembuilding->building}}</td>
                                    <td>
                                        {{$infos->household->unit?$infos->household->unit.'单元':''}}
                                        {{$infos->household->floor?$infos->household->floor.'楼':''}}
                                        {{$infos->household->number?$infos->household->number.'号':''}}
                                    </td>
                                    <td>{{$infos->register}}</td>
                                    <td>{{$infos->agree}}</td>
                                    <td>@if($infos->householdassetss_count) 待确认@else 已确认@endif</td>
                                    <td>
                                        <a href="{{route('g_householdassets_reportlist',['household_id'=>$infos->household_id,'item'=>$infos->item_id,])}}" class="btn btn-sm">资产详情</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ $sdata->total() }} @else 0 @endif 条数据</div>
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


@endsection

{{-- 样式 --}}
@section('css')

@endsection

{{-- 插件 --}}
@section('js')
    @parent
@endsection

