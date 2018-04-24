{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_house')}}" class="btn">返回房源</a>

    </div>
    <div class="row">
        <div class="col-xs-6">
            <h1>房源导入结果</h1>
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共获取到 <span class="red">{{$sdata['data_count'] }} </span>条房源数据</div>
            <br/><div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中符合格式的房源数据共 <span class="red">{{$sdata['success_count'] }}</span> 条，重复的房源数据共 <span class="red">{{$sdata['unique_count'] }}</span> 条，
                <br/>添加成功的房源数据共 <span class="red">{{$sdata['add_count'] }}</span> 条</div>
            <br/><div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中不符合格式的房源数据共 <span class="red">{{$sdata['error_count'] }}</span> 条</div>
            <form action="{{route('g_export_errordata',['file_url'=>$sdata['file_url']])}}" method="post" class="btn btn-xs export_errordata">
                {{csrf_field()}}
                <span id="export_errordata">导出不符合格式的数据</span>
            </form>
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
        /*--------- 导出不符合格式的数据 ----------*/
        $('.export_errordata').on('click',function () {
            $('#export_errordata').parent('form').submit();
        });
    </script>
@endsection