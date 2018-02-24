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


    <form class="form-horizontal" role="form" action="{{route('g_itemnotice_edit')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="id" value="{{$sdata->id}}">
        <input type="hidden" name="item" value="{{$sdata->item_id}}">
        <input type="hidden" name="cate_id" value="{{$sdata->cate_id}}">

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="cate_id"> 通知分类： </label>
            <div class="col-sm-9">
                <input type="text" class="col-xs-10 col-sm-5" id="cate_id" value="{{$sdata->newscate->name}}" readonly>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="infos"> 通知摘要：</label>
            <div class="col-sm-9">
                <textarea id="infos" name="infos" class="col-xs-10 col-sm-5" placeholder="请输入通知摘要" >{{$sdata->infos}}</textarea>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="widget-body">
            <div class="widget-main padding-8">

                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        地块图片：<br>
                        <span class="btn btn-xs">
                                        <span>上传图片</span>
                                        <input type="file" accept="image/*" class="hidden" data-name="picture[]" multiple  onchange="uplfile(this)">
                                    </span>
                    </label>
                    <div class="col-sm-9">
                        <ul class="ace-thumbnails clearfix img-content viewer">
                            @if($sdata->picture)
                                @foreach($sdata->picture as $pic)
                                    <li>
                                        <div>
                                            <img width="120" height="120" src="{!! $pic !!}" alt="加载失败">
                                            <input type="hidden" name="picture[]" value="{!! $pic !!}">
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
        $('.img-content').viewer('update');
    </script>

@endsection