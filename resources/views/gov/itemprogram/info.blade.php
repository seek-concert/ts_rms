{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        @if (blank($sdata))
            <a href="{{route('g_itemprogram_add',['item'=>$edata['item_id']])}}" class="btn">添加征收方案</a>
        @else
            <a href="{{route('g_itemprogram_edit',['id'=>$sdata->id,'item'=>$sdata['item_id']])}}" class="btn">修改征收方案</a>
        @endif

    </div>

    @if (filled($sdata))
        <div class="widget-container-col ui-sortable" id="widget-container-col-1">
            <div class="widget-box ui-sortable-handle" id="widget-box-1">
                <div class="widget-header">
                    <h5 class="widget-title">征收意见稿</h5>
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
                                        <div class="profile-info-name"> 方案名称： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->name}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 项目期限： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->item_end}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 被征收人比例（%）： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->portion_holder}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 承租人比例（%）： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->portion_renter}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 搬迁补助基本费用： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->move_base}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 住宅搬迁补助单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->move_house}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 办公搬迁补助单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->move_office}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 商服搬迁补助单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->move_business}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 临时安置基本费用： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_base}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 生产加工搬迁补助单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->move_factory}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 临时安置基本费用： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_base}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 临时安置单价-住宅： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_house}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 临时安置单价-非住宅： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_other}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name">现房临时安置时长（月）： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_real}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name">期房临时安置时长（月）： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->transit_future}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name">住宅签约奖励单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->reward_house}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name">非住宅签约奖励比例（%）： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->reward_other}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name">房屋奖励单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->reward_move}}</span>
                                        </div>
                                    </div>


                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 内容： </div>
                                        <div class="profile-info-value">
                                            <textarea name="content" id="content" >{{$sdata->content}}</textarea>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 状态： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->state->name}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 添加时间： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->created_at}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 修改时间： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->updated_at}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 删除时间： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata->deleted_at}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    <script src="{{asset('ueditor/ueditor.config.js')}}"></script>
    <script src="{{asset('ueditor/ueditor.all.min.js')}}"></script>
    <script>
        var ue = UE.getEditor('content',{
            toolbars:[],
            //关闭字数统计
            wordCount:false,
            //关闭elementPath
            elementPathEnabled:false,
            //默认的编辑区域高度
            initialFrameWidth:800,
            readonly : true
        });
    </script>
@endsection