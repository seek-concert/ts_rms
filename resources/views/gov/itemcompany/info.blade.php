{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>

        <a class="btn" href="{{route('g_itemcompany_edit',['id'=>$sdata['itemcompany']->id,'item'=>$sdata['item_id']])}}">
            <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
            修改
        </a>

        @if(blank($sdata['itemcompany']->picture))
            <a class="btn" href="{{route('g_itemcompany_pic',['id'=>$sdata['itemcompany']->id,'item'=>$sdata['item_id']])}}">
                <i class="ace-icon fa fa-check bigger-110"></i>
                评估委托书
            </a>
        @endif
    </p>


    <div class="profile-user-info profile-user-info-striped">

        <div class="profile-info-row">
            <div class="profile-info-name"> 类型： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['itemcompany']->type}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 评估机构： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['itemcompany']->company->name}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 评估委托书： </div>
            <div class="profile-info-value">
                <ul class="ace-thumbnails clearfix img-content viewer">
                    @if(filled($sdata['itemcompany']->picture))
                        @foreach($sdata['itemcompany']->picture as $pic)
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
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 评估户数： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{count($sdata['itemcompany']->households)}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 被征收户： </div>
            <div class="profile-info-value">
                @if(filled($sdata['itemcompany']->households))
                <table class="table table-hover table-bordered">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>地址</th>
                        <th>房号</th>
                        <th>用户名</th>
                        <th>资产</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sdata['itemcompany']->households as $info)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$info->household->itemland->address}}</td>
                            <td>{{$info->household->itembuilding->building?$info->household->itembuilding->building.'栋':''}}{{$info->household->unit?$info->household->unit.'单元':''}}{{$info->household->floor?$info->household->floor.'楼':''}}{{$info->household->number?$info->household->number.'号':''}}</td>
                            <td>{{$info->household->username}}</td>
                            <td>{{$info->household->householddetail->has_assets}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 创建时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['itemcompany']->created_at}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 更新时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['itemcompany']->updated_at}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 数据状态： </div>
            <div class="profile-info-value">
                <span class="editable editable-click"> @if($sdata['itemcompany']->deleted_at) 已删除 @else 启用中 @endif</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 删除时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['itemcompany']->deleted_at}}</span>
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