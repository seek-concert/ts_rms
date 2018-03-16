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


    <div class="profile-user-info profile-user-info-striped">
        <div class="profile-info-row">
            <div class="profile-info-name"> 【摸底信息】 </div>
            <div class="profile-info-value">

            </div>
        </div>
        <div class="profile-info-row">
            <div class="profile-info-name"> 名称： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->name}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 计量单位： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->num_unit}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 征收端-数量： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->gov_num}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 评估端-数量： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->com_num}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 征收端-图片： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">
                    <ul class="ace-thumbnails clearfix img-content viewer">
                          @if(isset($sdata->gov_pic))
                            @foreach($sdata->gov_pic as $pic)
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

        <div class="profile-info-row">
            <div class="profile-info-name"> 评估端-图片： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">
                    <ul class="ace-thumbnails clearfix img-content viewer">
                          @if(isset($sdata->com_pic))
                            @foreach($sdata->com_pic as $pic)
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
        <div class="profile-info-row">
            <div class="profile-info-name"> 创建时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->created_at}}</span>
            </div>
        </div>
        <br/>

        <div class="profile-info-row">
            <div class="profile-info-name"> 【资产确认】 </div>
            <div class="profile-info-value">

            </div>
        </div>
        <div class="profile-info-row">
            <div class="profile-info-name"> 确认数量： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->number}}</span>
            </div>
        </div>
        <div class="profile-info-row">
            <div class="profile-info-name"> 更新时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata->updated_at}}</span>
            </div>
        </div>
    </div>

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