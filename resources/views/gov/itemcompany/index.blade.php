{{-- 继承aceAdmin后台布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_itemcompany_add',['item'=>$edata['item_id'],'type'=>0])}}" class="btn">选定房产评估机构</a>
        <a href="{{route('g_itemcompany_add',['item'=>$edata['item_id'],'type'=>1])}}" class="btn">选定资产评估机构</a>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <div class="widget-box widget-color-blue2">
                <div class="widget-header">
                    <h4 class="widget-title lighter smaller">房产评估机构：</h4>
                </div>
                <div class="widget-body">
                    <div class="widget-main padding-8">

                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>类型</th>
                                <th>评估机构名称</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($sdata as $infos)
                                    @if($infos->getOriginal('type')==0)
                                    <tr>
                                        <td>{{$infos->type}}</td>
                                        <td>{{$infos->company->name}}</td>
                                        <td>
                                            <a href="{{route('g_itemcompany_info',['id'=>$infos->id,'item'=>$infos->item_id])}}" class="btn btn-sm">查看详情</a>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ $edata['typecount'] }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="widget-box widget-color-green2">
                <div class="widget-header">
                    <h4 class="widget-title lighter smaller">资产评估机构：</h4>
                </div>
                <div class="widget-body">
                    <div class="widget-main padding-8">

                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>类型</th>
                                <th>评估机构名称</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($sdata as $infos)
                                    @if($infos->getOriginal('type')==1)
                                        <tr>
                                            <td>{{$infos->type}}</td>
                                            <td>{{$infos->company->name}}</td>
                                            <td>
                                                <a href="{{route('g_itemcompany_info',['id'=>$infos->id,'item'=>$infos->item_id])}}" class="btn btn-sm">查看详情</a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ $edata['typecounts'] }} @else 0 @endif 条数据</div>
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

