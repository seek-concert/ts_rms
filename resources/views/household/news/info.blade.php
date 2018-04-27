{{-- 继承布局 --}}
@extends('household.layout')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </div>
    <h3 class="header smaller green">公告详情</h3>
    <div class="row">
        <div class="col-sm-5 col-xs-12">
            <div class="widget-container-col ui-sortable">
                <div class="widget-box ui-sortable-handle">
                    <div class="widget-header">
                        <h5 class="widget-title">基本信息</h5>
                    </div>

                    <div class="widget-body">
                        <div class="widget-main">

                            <div class="profile-user-info profile-user-info-striped">

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 项目： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['item']->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 分类： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->newscate->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 名称： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 发布时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->release_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 关键词： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->keys}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 摘要： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->infos}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 附件： </div>
                                    <div class="profile-info-value">
                                        <ul class="ace-thumbnails clearfix img-content">
                                            @foreach($sdata['news']->picture as $pic)
                                                <li>
                                                    <div>
                                                        <img width="120" height="120" src="{{$pic}}" alt="加载失败">
                                                        <div class="text">
                                                            <div class="inner">
                                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach

                                        </ul>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 是否置顶： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->is_top}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 状态： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->state->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 创建时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['news']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-7 col-xs-12">
            <div class="widget-container-col ui-sortable">
                <div class="widget-box ui-sortable-handle">
                    <div class="widget-header">
                        <h5 class="widget-title">具体内容</h5>
                    </div>

                    <div class="widget-body">
                        <div class="widget-main">
                           {!! $sdata['news']->content !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (filled($sdata['program']))
        <h3 class="header smaller red">征收方案</h3>
        <div class="tabbable">
            <ul class="nav nav-tabs padding-12 tab-color-blue background-blue">
                <li class="active">
                    <a data-toggle="tab" href="#program_content" aria-expanded="true">征收方案</a>
                </li>
                <li class="">
                    <a data-toggle="tab" href="#subject" aria-expanded="false">补偿科目</a>
                </li>
                <li class="">
                    <a data-toggle="tab" href="#program_other" aria-expanded="false">重要数据</a>
                </li>
            </ul>

            <div class="tab-content">
                <div id="program_content" class="tab-pane active" >
                       {!!$sdata['program']['itemprogram']->content!!}
                </div>

                <div id="subject" class="tab-pane" >
                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th></th>
                            <th>名称</th>
                            <th>补偿说明</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(filled($sdata['program']['subjects']))
                            @foreach($sdata['program']['subjects'] as $subject)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$subject->subject->name}}</td>
                                    <td>{{$subject->infos}}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>

                <div id="program_other" class="tab-pane" >
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                                <div class="widget-container-col ui-sortable">
                                    <div class="widget-box ui-sortable-handle">
                                        <div class="widget-header">
                                            <h5 class="widget-title"></h5>

                                            <div class="widget-toolbar">
                                                <a href="#" data-action="collapse">
                                                    <i class="ace-icon fa fa-chevron-up"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <table class="table table-hover table-bordered">
                                                    <tbody>
                                                    <tr>
                                                        <th>方案名称</th>
                                                        <td colspan="5">{{$sdata['program']['itemprogram']->name}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>项目期限</th>
                                                        <td colspan="5">{{$sdata['program']['itemprogram']->item_end}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2">补偿比例</th>
                                                        <th colspan="2">产权人（%）</th>
                                                        <th colspan="3">承租人（%）</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{$sdata['program']['itemprogram']->portion_holder}}</td>
                                                        <td colspan="3">{{$sdata['program']['itemprogram']->portion_renter}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2">搬迁补助费</th>
                                                        <th>最低标准（元/次）</th>
                                                        <th>住宅（元/㎡/次）</th>
                                                        <th>办公（元/㎡/次）</th>
                                                        <th>商服（元/㎡/次）</th>
                                                        <th>生产加工（元/㎡/次）</th>
                                                    </tr>
                                                    <tr>
                                                        <td>{{$sdata['program']['itemprogram']->move_base}}</td>
                                                        <td>{{$sdata['program']['itemprogram']->move_house}}</td>
                                                        <td>{{$sdata['program']['itemprogram']->move_office}}</td>
                                                        <td>{{$sdata['program']['itemprogram']->move_business}}</td>
                                                        <td>{{$sdata['program']['itemprogram']->move_factory}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2">临时安置费</th>
                                                        <th>最低标准（元/月）</th>
                                                        <th colspan="2">住宅（元/㎡/月）</th>
                                                        <th colspan="2">非住宅（元/㎡/月）</th>
                                                    </tr>
                                                    <tr>
                                                        <td>{{$sdata['program']['itemprogram']->transit_base}}</td>
                                                        <td colspan="2">{{$sdata['program']['itemprogram']->transit_house}}</td>
                                                        <td colspan="2">{{$sdata['program']['itemprogram']->transit_other}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2" colspan="2">临时安置费的补助时长（月）</th>
                                                        <th colspan="2">现房</th>
                                                        <th colspan="2">期房</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{$sdata['program']['itemprogram']->transit_real}}</td>
                                                        <td colspan="3">{{$sdata['program']['itemprogram']->transit_future}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2" colspan="2">货币补偿的额外奖励</th>
                                                        <th colspan="2">住宅（元/㎡）</th>
                                                        <th colspan="2">非住宅（%）</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{$sdata['program']['itemprogram']->reward_house}}</td>
                                                        <td colspan="3">{{$sdata['program']['itemprogram']->reward_other}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="2">房屋与登记相符奖励</th>
                                                        <td colspan="4">{{$sdata['program']['itemprogram']->reward_real}} 元/㎡</td>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="2">按约搬迁奖励</th>
                                                        <td colspan="4">{{$sdata['program']['itemprogram']->reward_move}} 元</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-container-col ui-sortable">
                                <div class="widget-box ui-sortable-handle">
                                    <div class="widget-header">
                                        <h5 class="widget-title">临时安置费特殊人群的优惠上浮</h5>

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
                                                    <th>分类</th>
                                                    <th>特殊人群</th>
                                                    <th>上浮比例（%）</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(filled($sdata['program']['crowds']))
                                                    @foreach($sdata['program']['crowds'] as $crowd)
                                                        <tr>
                                                            <td>{{$loop->iteration}}</td>
                                                            <td>{{$crowd->cate->name}}</td>
                                                            <td>{{$crowd->crowd->name}}</td>
                                                            <td>{{$crowd->rate}}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-container-col ui-sortable">
                                <div class="widget-box ui-sortable-handle">
                                    <div class="widget-header">
                                        <h5 class="widget-title">产权调换房优惠上浮</h5>

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
                                                    <th>起始面积</th>
                                                    <th>截止面积</th>
                                                    <th>上浮比例（%）</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(filled($sdata['program']['house_rates']))
                                                    @foreach($sdata['program']['house_rates'] as $house_rate)
                                                        <tr>
                                                            <td>{{$loop->iteration}}</td>
                                                            <td>{{$house_rate->start_area}}</td>
                                                            <td>{{$house_rate->end_area?:'--'}}</td>
                                                            <td>{{$house_rate->rate?:'--'}}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                </tbody>
                                                <tfoot>
                                                <tr>
                                                    <td colspan="4">
                                                        注：超过最高优惠上浮面积后，超出部分按评估市场价结算！
                                                    </td>
                                                </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-container-col ui-sortable">
                                <div class="widget-box ui-sortable-handle">
                                    <div class="widget-header">
                                        <h5 class="widget-title">产权调换的签约奖励</h5>

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
                                                    <th>起始时间</th>
                                                    <th>结束时间</th>
                                                    <th>补偿单价（住宅）</th>
                                                    <th>补偿比例（非住宅）</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(filled($sdata['program']['rewards']))
                                                    @foreach($sdata['program']['rewards'] as $reward)
                                                        <tr>
                                                            <td>{{$loop->iteration}}</td>
                                                            <td>{{$reward->start_at}}</td>
                                                            <td>{{$reward->end_at}}</td>
                                                            <td>{{$reward->price}} 元/㎡</td>
                                                            <td>{{$reward->portion}} %</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="widget-container-col ui-sortable">
                                <div class="widget-box ui-sortable-handle">
                                    <div class="widget-header">
                                        <h5 class="widget-title">其他补偿事项</h5>

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
                                                    <th>名称</th>
                                                    <th>计量单位</th>
                                                    <th>补偿单价</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @if(filled($sdata['program']['objects']))
                                                    @foreach($sdata['program']['objects'] as $object)
                                                        <tr>
                                                            <td>{{$loop->iteration}}</td>
                                                            <td>{{$object->object->name}}</td>
                                                            <td>{{$object->object->num_unit}}</td>
                                                            <td>{{$object->price}}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                </tbody>
                                            </table>
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

    @if(filled($sdata['itemdraft']))
        <h3 class="header smaller red">征收意见稿</h3>
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
                        <div class="user-profile">
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 名称： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['itemdraft']->name}}</span>
                                    </div>
                                </div>
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 内容： </div>
                                    <div class="profile-info-value">
                                      {!!$sdata['itemdraft']->content!!}
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
    @parent
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script src="{{asset('laydate/laydate.js')}}"></script>
    {{--<script src="{{asset('ueditor/ueditor.config.js')}}"></script>--}}
    {{--<script src="{{asset('ueditor/ueditor.all.min.js')}}"></script>--}}
    {{--<script>--}}
         {{--UE.getEditor('content', {readonly:true,toolbars:null,wordCount:false});--}}
         {{--UE.getEditor('content1', {readonly:true,toolbars:null,wordCount:false});--}}
         {{--UE.getEditor('content2', {readonly:true,toolbars:null,wordCount:false});--}}
        {{--$('.img-content').viewer();--}}
    {{--</script>--}}

@endsection