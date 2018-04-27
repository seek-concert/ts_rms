{{-- 继承主体 --}}
@extends('com.main')

{{-- 页面内容 --}}
@section('content')
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>序号</th>
            <th>地块</th>
            <th>楼栋</th>
            <th>位置</th>
            <th>类型</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
            @if($code=='success')
                @if($edata['type']==0)
                    @foreach($sdata as $infos)
                        @if(filled($infos->household->estates->code)&&$infos->household->estates->code>=130&&$infos->household->estates->code<=136)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$infos->household->itemland->address}}</td>
                            <td>{{$infos->household->itembuilding->building}}</td>
                            <td>{{$infos->household->unit?$infos->household->unit.'单元':''}}
                                {{$infos->household->floor?$infos->household->floor.'楼':''}}
                                {{$infos->household->number?$infos->household->number.'号':''}}
                            </td>
                            <td>{{$infos->household->type}}</td>
                            <td>
                                @if($infos->household->estates->code==130)
                                    <a href="{{route('c_comassess_add',['id'=>$infos->household_id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">@if($edata['item_program'])开始评估@else 开始预评估@endif</a>
                                @else
                                    <a href="{{route('c_comassess_info',['id'=>$infos->household_id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">@if($edata['item_program'])评估详情@else 预评估详情@endif</a>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
                @else
                    @foreach($sdata as $infos)
                        @if(filled($infos->household->estates->code)&&$infos->household->estates->code>=130&&$infos->household->estates->code<=136&&$infos->household->estates->getOriginal('has_assets')==1)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$infos->household->itemland->address}}</td>
                                <td>{{$infos->household->itembuilding->building}}</td>
                                <td>{{$infos->household->unit?$infos->household->unit.'单元':''}}
                                    {{$infos->household->floor?$infos->household->floor.'楼':''}}
                                    {{$infos->household->number?$infos->household->number.'号':''}}
                                </td>
                                <td>{{$infos->household->type}}</td>
                                <td>
                                    @if($infos->household->assets->code==130)
                                        <a href="{{route('c_comassess_add',['id'=>$infos->household_id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">开始评估</a>
                                    @else
                                        <a href="{{route('c_comassess_info',['id'=>$infos->household_id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">评估详情</a>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach

                @endif
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