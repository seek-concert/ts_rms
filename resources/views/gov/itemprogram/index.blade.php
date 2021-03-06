{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        @if (blank($sdata['program']))
            <a href="{{route('g_itemprogram_add',['item'=>$sdata['item_id']])}}" class="btn">添加征收方案</a>
        @else
            <a href="{{route('g_itemprogram_edit',['item'=>$sdata['item_id']])}}" class="btn">修改征收方案</a>
        @endif
            <a href="{{route('g_itemsubject',['item'=>$sdata['item_id']])}}" class="btn">补偿科目</a>
            <a href="{{route('g_itemcrowd',['item'=>$sdata['item_id']])}}" class="btn">特殊人群的优惠上浮</a>
            <a href="{{route('g_itemhouserate',['item'=>$sdata['item_id']])}}" class="btn">产权调换房的优惠上浮</a>
            <a href="{{route('g_itemreward',['item'=>$sdata['item_id']])}}" class="btn">产权调换的签约奖励</a>
            <a href="{{route('g_itemobject',['item'=>$sdata['item_id']])}}" class="btn">其他补偿事项</a>

            @if($sdata['item']->process_id==36 && $sdata['item']->code=='1')
                <a class="btn btn-danger" onclick="btnAct(this)" data-url="{{route('g_program_to_check',['item'=>$sdata['item_id']])}}" data-method="post">
                    <i class="ace-icon fa fa-check-circle bigger-110"></i>
                    正式方案提交审查
                </a>
            @endif

    </div>

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
                @if(filled($sdata['program']))
                <textarea name="content" id="content" style="width:100%;min-height: 360px;">{{$sdata['program']->content}}</textarea>
                @endif
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
                    @if(filled($sdata['subjects']))
                        @foreach($sdata['subjects'] as $subject)
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
                        @if(filled($sdata['program']))
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
                                                    <td colspan="5">{{$sdata['program']->name}}</td>
                                                </tr>
                                                <tr>
                                                    <th>项目期限</th>
                                                    <td colspan="5">{{$sdata['program']->item_end}}</td>
                                                </tr>
                                                <tr>
                                                    <th rowspan="2">补偿比例</th>
                                                    <th colspan="2">产权人（%）</th>
                                                    <th colspan="3">承租人（%）</th>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">{{$sdata['program']->portion_holder}}</td>
                                                    <td colspan="3">{{$sdata['program']->portion_renter}}</td>
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
                                                    <td>{{$sdata['program']->move_base}}</td>
                                                    <td>{{$sdata['program']->move_house}}</td>
                                                    <td>{{$sdata['program']->move_office}}</td>
                                                    <td>{{$sdata['program']->move_business}}</td>
                                                    <td>{{$sdata['program']->move_factory}}</td>
                                                </tr>
                                                <tr>
                                                    <th rowspan="2">临时安置费</th>
                                                    <th>最低标准（元/月）</th>
                                                    <th colspan="2">住宅（元/㎡/月）</th>
                                                    <th colspan="2">非住宅（元/㎡/月）</th>
                                                </tr>
                                                <tr>
                                                    <td>{{$sdata['program']->transit_base}}</td>
                                                    <td colspan="2">{{$sdata['program']->transit_house}}</td>
                                                    <td colspan="2">{{$sdata['program']->transit_other}}</td>
                                                </tr>
                                                <tr>
                                                    <th rowspan="2" colspan="2">临时安置费的补助时长（月）</th>
                                                    <th colspan="2">现房</th>
                                                    <th colspan="2">期房</th>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">{{$sdata['program']->transit_real}}</td>
                                                    <td colspan="3">{{$sdata['program']->transit_future}}</td>
                                                </tr>
                                                <tr>
                                                    <th rowspan="2" colspan="2">货币补偿的额外奖励</th>
                                                    <th colspan="2">住宅（元/㎡）</th>
                                                    <th colspan="2">非住宅（%）</th>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">{{$sdata['program']->reward_house}}</td>
                                                    <td colspan="3">{{$sdata['program']->reward_other}}</td>
                                                </tr>
                                                <tr>
                                                    <th colspan="2">房屋与登记相符奖励</th>
                                                    <td colspan="4">{{$sdata['program']->reward_real}} 元/㎡</td>
                                                </tr>
                                                <tr>
                                                    <th colspan="2">按约搬迁奖励</th>
                                                    <td colspan="4">{{$sdata['program']->reward_move}} 元</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endif
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
                                            @if(filled($sdata['crowds']))
                                                @foreach($sdata['crowds'] as $crowd)
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
                                            @if(filled($sdata['house_rates']))
                                                @foreach($sdata['house_rates'] as $house_rate)
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
                                            @if(filled($sdata['rewards']))
                                                @foreach($sdata['rewards'] as $reward)
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
                                            @if(filled($sdata['objects']))
                                                @foreach($sdata['objects'] as $object)
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
            readonly:true
            ,toolbars:null
            ,wordCount:false
        });
    </script>
@endsection