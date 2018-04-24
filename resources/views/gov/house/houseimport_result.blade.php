{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_house')}}" class="btn">返回房源</a>

    </div>
    <div class="row">
        <div class="col-xs-6">
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">导入共 <span class="red">{{$sdata['data_count'] }} </span>条房源数据</div>
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中符合格式的房源数据共 <span class="red">{{$sdata['success_count'] }}</span> 条数据，重复的房源数据共 <span class="red">{{$sdata['unique_count'] }}</span> 条，添加成功的房源数据共 <span class="red">{{$sdata['add_count'] }}</span> 条</div>
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中不符合格式的房源数据共 <span class="red">{{$sdata['error_count'] }}</span> 条数据</div>
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