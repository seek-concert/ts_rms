{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </p>


    <form class="form-horizontal" role="form" action="{{route('g_householdobject_add')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="item" value="{{$sdata['item_id']}}">
        <input type="hidden" name="household_id" value="{{$sdata['household_id']}}">
        <input type="hidden" name="land_id" value="{{$sdata['household']->land_id}}">
        <input type="hidden" name="building_id" value="{{$sdata['household']->building_id}}">

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="object_id"> 其他补偿事项： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="object_id" id="object_id">
                    <option value="">--请选择--</option>
                    @if($sdata['object'])
                        @foreach($sdata['object'] as $object)
                            <option value="{{$object->id}}">{{$object->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="number"> 数量： </label>
            <div class="col-sm-9">
                <input type="number" id="number" name="number" value="{{old('number')}}" class="col-xs-10 col-sm-5"  placeholder="请输入数量" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <div class="widget-main padding-8">
                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        图片：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="picture[]" multiple  onchange="uplfile(this)">
                        </span>
                    </label>
                    <div class="col-sm-9">
                        <ul class="ace-thumbnails clearfix img-content viewer">
                        </ul>
                    </div>
                </div>
                <div class="space-4 header green"></div>
            </div>
        </div>

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


@endsection

{{-- 样式 --}}
@section('css')
    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}" />
@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('js/func.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>

    </script>

@endsection