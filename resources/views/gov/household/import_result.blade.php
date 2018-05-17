{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_household',['item'=>$sdata['item_id']])}}" class="btn">返回被征户</a>

    </div>
    <div class="row">
        <div class="col-xs-6">
            <h1>被征户数据导入结果</h1>
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共获取到 <span class="red">{{$sdata['total_count'] }} </span>条被征户数据</div>
            <br/><div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中符合格式的被征户数据共 <span class="red">{{$sdata['format_count'] }}</span> 条，重复的被征户数据共 <span class="red">{{$sdata['repeat_count'] }}</span> 条，
                <br/>添加成功的征户数据共 <span class="red">{{$sdata['add_count'] }}</span> 条</div>
            <br/><div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">其中不符合格式的被征户数据共 <span class="red">{{$sdata['error_count'] }}</span> 条</div>
            @if($sdata['error_count']>0)
            <form action="{{route('g_household_export_error',['item'=>$sdata['item_id'],'file_url'=>$sdata['file_url']])}}" method="post" class="btn btn-xs export_errordata">
                {{csrf_field()}}
                <span id="export_errordata">导出不符合格式的数据</span>
            </form>
            @endif
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