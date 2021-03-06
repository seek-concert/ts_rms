{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_household_add',['item'=>$edata['item_id']])}}" class="btn">添加被征收户账号</a>
    </div>

    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>序号</th>
            <th>地块</th>
            <th>位置</th>
            <th>房产类型</th>
            <th>用户名</th>
            <th>产权争议|面积争议|房屋状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
            @if($code=='success')
                @foreach($sdata as $infos)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$infos->itemland->address}}</td>
                        <td>{{$infos->itembuilding->building}}栋{{$infos->household->unit}}单元{{$infos->household->floor}}楼{{$infos->household->number}}号</td>
                        <td>{{$infos->household->type}}</td>
                        <td>{{$infos->household->username}}</td>
                        <td>{{$infos->dispute}}|{{$infos->area_dispute}}|{{$infos->status}}</td>
                        <td>
                            <a href="{{route('g_householddetail_info',['id'=>$infos->household_id,'item'=>$infos->item_id])}}" class="btn btn-sm">查看详情</a>
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

@endsection

{{-- 样式 --}}
@section('css')

@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script>

    </script>

@endsection