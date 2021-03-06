{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')


    <div class="well well-sm">
        <a href="{{route('g_landprop_add')}}" class="btn">添加土地性质</a>
    </div>

    <table class="table table-hover table-bordered treetable" id="tree-landprop">
        <thead>
        <tr>
            <th>土地性质 > 土地来源 > 土地权益状况</th>
            <th>描述</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
            @if($code=='success')
                @foreach($sdata as $prop)
                    <tr data-tt-id="prop-{{$prop->id}}" data-tt-parent-id="0" @if(count($prop->landsources)) data-tt-branch="true" @endif>
                        <td>{{$prop->name}}</td>
                        <td>{{$prop->infos}}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{route('g_landprop_edit',['id'=>$prop->id])}}" class="btn btn-xs">编辑</a>
                                <a href="{{route('g_landsource_add',['prop_id'=>$prop->id])}}" class="btn btn-xs">添加土地来源</a>
                            </div>
                        </td>
                    </tr>
                    @foreach($prop->landsources as $source)
                        <tr data-tt-id="prop-{{$prop->id}}-source-{{$source->id}}" data-tt-parent-id="prop-{{$prop->id}}" @if(count($source->landstates)) data-tt-branch="true" @endif>
                            <td>{{$source->name}}</td>
                            <td>{{$source->infos}}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{route('g_landsource_edit',['id'=>$source->id])}}" class="btn btn-xs">编辑</a>
                                    <a href="{{route('g_landstate_add',['source_id'=>$source->id])}}" class="btn btn-xs">添加土地权益状况</a>
                                </div>
                            </td>
                        </tr>
                        @foreach($source->landstates as $state)
                            <tr data-tt-id="prop-{{$prop->id}}-source-{{$source->id}}-state-{{$state->id}}" data-tt-parent-id="prop-{{$prop->id}}-source-{{$source->id}}" >
                                <td>{{$state->name}}</td>
                                <td>{{$state->infos}}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{route('g_landstate_edit',['id'=>$state->id])}}" class="btn btn-xs">编辑</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            @endif
        </tbody>
    </table>


@endsection

{{-- 样式 --}}
@section('css')
    <link rel="stylesheet" href="{{asset('treetable/jquery.treetable.theme.default.css')}}">
@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('treetable/jquery.treetable.js')}}"></script>
    <script>
        $("#tree-landprop").treetable({
            expandable: true // 展示
            ,initialState :"collapsed"//默认打开所有节点
            ,stringCollapse:'关闭'
            ,stringExpand:'展开'
        });
    </script>

@endsection