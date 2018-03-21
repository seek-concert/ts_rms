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

                <li class="active">
                    <a data-toggle="tab" href="#householdbuildingdeal" aria-expanded="true">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        违建处理
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

                <li class="">
                    <a href="{{route('g_householdassets_report',['item'=>$edata['item_id']])}}">
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
                <div id="householdright" class="tab-pane fade">
                </div>

                <div id="householdbuildingdeal" class="tab-pane fade active in">
                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th>序号</th>
                            <th>地块</th>
                            <th>楼栋</th>
                            <th>位置</th>
                            <th>房屋产权证号</th>
                            <th>征收意见</th>
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
                                    <td>  {{$infos->household->unit?$infos->household->unit.'单元':''}}
                                        {{$infos->household->floor?$infos->household->floor.'楼':''}}
                                        {{$infos->household->number?$infos->household->number.'号':''}}</td>
                                    <td>{{$infos->register}}</td>
                                    <td>{{$infos->agree}}</td>
                                    <td>
                                        <a href="{{route('g_householdbuildingdeal_infos',['id'=>$infos->id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">建筑详情</a>
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

                <div id="householdarea" class="tab-pane fade">
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