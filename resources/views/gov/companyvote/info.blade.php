{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </div>

    <div class="widget-container-col ui-sortable">
        <div class="widget-box ui-sortable-handle">
            <div class="widget-header">
                <h5 class="widget-title">评估机构</h5>

                <div class="widget-toolbar">
                    <a href="#" data-action="collapse">
                        <i class="ace-icon fa fa-chevron-up"></i>
                    </a>

                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    <div class="user-profile row">
                        <div class="col-xs-12 col-sm-2 center">
                            <ul class="ace-thumbnails clearfix img-content profile-picture">
                                <li>
                                    <div>
                                        <img width="120" height="120" src="{{$sdata['company']->logo}}" alt="加载失败">
                                        <div class="text">
                                            <div class="inner">
                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-xs-12 col-sm-8">
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 名称： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['company']->name}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 地址： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['company']->address}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 电话： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['company']->phone}}</span>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="widget-container-col ui-sortable">
        <div class="widget-box ui-sortable-handle">
            <div class="widget-header">
                <h5 class="widget-title">投票详情</h5>

                <div class="widget-toolbar">
                    <a href="#" data-action="collapse">
                        <i class="ace-icon fa fa-chevron-up"></i>
                    </a>

                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th></th>
                            <th>地块</th>
                            <th>地址</th>
                            <th>用户名</th>
                            <th>投票时间</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($sdata['households'] as $vote)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$vote->household->itemland->address}}</td>
                                <td>{{$vote->household->itembuilding->building}}栋{{$vote->household->unit}}单元{{$vote->household->floor}}楼{{$vote->household->number}}号</td>
                                <td>{{$vote->household->username}}</td>
                                <td>{{$vote->created_at}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

{{-- 样式 --}}
@section('css')

@endsection

{{-- 插件 --}}
@section('js')
    @parent

@endsection