{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="widget-container-col ui-sortable" id="widget-container-col-1">
        <div class="widget-box ui-sortable-handle" id="widget-box-1">
            <div class="widget-header">
                <h5 class="widget-title">项目概述</h5>

                <div class="widget-toolbar">
                    <a href="#" data-action="collapse">
                        <i class="ace-icon fa fa-chevron-up"></i>
                    </a>

                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    <div class="user-profile row">
                        <div class="col-xs-12 col-sm-9">
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 项目名称： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata->name}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 征收范围： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata->place}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 项目描述： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata->infos}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 项目负责人： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click"></span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 项目进度： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click"></span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 总户数： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-xs-12 col-sm-3 center">
                            <div>
                                <h6>征收范围红线地图：</h6>
                                <ul class="ace-thumbnails clearfix img-content profile-picture">
                                    <li>
                                        <div>
                                            <img width="120" height="120" src="{{$sdata->map}}" alt="{{$sdata->map}}">
                                            <div class="text">
                                                <div class="inner">
                                                    <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <p>
                                    <a href="{{route('g_iteminfo_info')}}">查看详情 <i class="fa fa-angle-double-right"></i></a>
                                </p>
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

    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}" />

@endsection

{{-- 插件 --}}
@section('js')
    <script src="{{asset('viewer/viewer.min.js')}}"></script>

    <script src="{{asset('js/func.js')}}"></script>
    <script>
        $('.img-content').viewer();
    </script>

@endsection