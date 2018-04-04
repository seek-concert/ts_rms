{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="{{route('g_iteminfo_info',['item'=>$sdata->id])}}">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </p>

    <form class="form-horizontal" role="form" action="{{route('g_iteminfo_edit',['item'=>$sdata->id])}}" method="post">
        {{csrf_field()}}

        <div class="row">
            <div class="col-sm-6">
                <div class="widget-box widget-color-blue2">
                    <div class="widget-header">
                        <h4 class="widget-title lighter smaller">基本信息</h4>
                    </div>
                    <div class="widget-body">
                        <div class="widget-main padding-8">

                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="name"> 名称： </label>
                                <div class="col-sm-9">
                                    <input type="text" id="name" name="name" value="{{$sdata->name}}" class="col-xs-10 col-sm-10" required>
                                </div>
                            </div>
                            <div class="space-4"></div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="place">征收范围：</label>
                                <div class="col-sm-9">
                                    <textarea id="place" name="place" class="col-xs-10 col-sm-10" >{{$sdata->place}}</textarea>
                                </div>
                            </div>
                            <div class="space-4"></div>

                            <div class="form-group img-box">
                                <label class="col-sm-3 control-label no-padding-right">
                                    征收范围红线地图<br>
                                    <span class="btn btn-xs">
                                        <span>上传图片</span>
                                        <input type="file" accept="image/*" class="hidden" data-name="map" onchange="uplfile(this)">
                                    </span>
                                </label>
                                <div class="col-sm-9">
                                    <ul class="ace-thumbnails clearfix img-content">
                                        <li>
                                            <div>
                                                <img width="120" height="120" src="{{$sdata->map}}" alt="加载失败">
                                                <input type="hidden" name="map" value="{{$sdata->map}}">
                                                <div class="text">
                                                    <div class="inner">
                                                        <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                        <a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="space-4"></div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="infos">描述：</label>
                                <div class="col-sm-9">
                                    <textarea id="infos" name="infos" class="col-xs-10 col-sm-10" >{{$sdata->infos}}</textarea>
                                </div>
                            </div>
                            <div class="space-4"></div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="widget-box widget-color-green2">
                    <div class="widget-header">
                        <h4 class="widget-title lighter smaller">
                            审查资料
                        </h4>
                    </div>

                    <div class="widget-body">
                        <div class="widget-main padding-8">

                            @foreach($edata as $filecate)
                                <div class="form-group img-box">
                                    <label class="col-sm-3 control-label no-padding-right">
                                        {{$filecate->name}}<br>
                                        <span class="btn btn-xs">
                                        <span>上传图片</span>
                                        <input type="file" accept="image/*" class="hidden" data-name="picture[{{$filecate->filename}}][]" multiple onchange="uplfile(this)">
                                    </span>
                                    </label>
                                    <div class="col-sm-9">
                                        <ul class="ace-thumbnails clearfix img-content">
                                            @if(filled($sdata->picture))
                                            @foreach($sdata->picture[$filecate->filename] as $pic)
                                                <li>
                                                    <div>
                                                        <img width="120" height="120" src="{{$pic}}" alt="加载失败">
                                                        <input type="hidden" name="picture[{{$filecate->filename}}][]" value="{{$pic}}">
                                                        <div class="text">
                                                            <div class="inner">
                                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                                <a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                                <div class="space-4 header green"></div>
                            @endforeach

                        </div>
                    </div>
                </div>
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
    <script src="{{asset('viewer/viewer.min.js')}}"></script>

    <script src="{{asset('js/func.js')}}"></script>
    <script>
        $('#name').focus();
        $('.img-content').viewer();
    </script>

@endsection