{{-- 继承布局 --}}
@extends('household.home')


{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">

        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>

    </div>


    @if(filled($sdata))

        <div class="profile-user-info profile-user-info-striped">

            <div class="profile-info-row">
                <div class="profile-info-name"> 房屋图片：</div>
                <div class="profile-info-value">
                        <span class="editable editable-click">
                             <ul class="ace-thumbnails clearfix img-content viewer">
                                  @if(isset($sdata['picture']))
                                     @foreach($sdata['picture'] as $pic)
                                         <li>
                                    <div>
                                        <img width="120" height="120" src="{!! $pic !!}" alt="加载失败">
                                        <div class="text">
                                            <div class="inner">
                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                     @endforeach
                                 @endif
                            </ul>
                        </span>
                </div>
            </div>
        </div>
    @endif
@endsection

{{-- 样式 --}}
@section('css')
    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}" />
@endsection

{{-- 插件 --}}
@section('js')
    <script src="{{asset('js/func.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>
        $('.img-content').viewer('update');
    </script>
@endsection