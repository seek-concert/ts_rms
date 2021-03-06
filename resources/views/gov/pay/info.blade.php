{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="well well-sm">
        <a href="javascript:history.back();" class="btn">返回</a>

        <a href="{{route('g_paysubject_add',['item'=>$sdata['item']->id,'pay_id'=>$sdata['pay']->id])}}" class="btn">
            添加补偿科目
        </a>

        @if(in_array($sdata['household']->code,['68','76']))
            <a class="btn btn-danger" onclick="btnAct(this)" data-url="{{route('g_paysubject_recal',['item'=>$sdata['item']->id,'pay_id'=>$sdata['pay']->id])}}" data-method="post">
                <i class="ace-icon fa fa-support bigger-110"></i>
                重新计算补偿
            </a>
        @endif
        @if($sdata['pay']->getOriginal('repay_way')==1)
            <a href="{{route('g_payhouse_add',['item'=>$sdata['item']->id,'pay_id'=>$sdata['pay']->id])}}" class="btn">
                选房
            </a>
        @endif

        <a href="{{route('g_pact_add',['item'=>$sdata['item']->id,'pay_id'=>$sdata['pay']->id])}}" class="btn btn-primary">
            生成补偿安置协议
        </a>

        <a href="{{route('g_pact_add2',['item'=>$sdata['item']->id,'pay_id'=>$sdata['pay']->id])}}" class="btn btn-info2">
            生成安置补充协议
        </a>
    </div>

    <div class="row">
        <div class="col-sm-6 col-xs-12">
            <div class="widget-container-col ui-sortable">
                <div class="widget-box ui-sortable-handle">
                    <div class="widget-header">
                        <h5 class="widget-title">被征收户</h5>
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
                                    <div class="profile-info-name"> 地址： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->itemland->address}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房号： </div>
                                    <div class="profile-info-value">
                                <span class="editable editable-click">
                                    {{$sdata['household']->itembuilding->building}}栋{{$sdata['household']->unit}}单元{{$sdata['household']->floor}}楼{{$sdata['household']->number}}@if(is_numeric($sdata['household']->floor))号@endif
                                </span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 类型： </div>
                                    <div class="profile-info-value">
                                    <span class="editable editable-click">
                                        @if($sdata['household']->getOriginal('type'))
                                            公房（{{$sdata['household']->itemland->adminunit->name}}）
                                        @else
                                            私产
                                        @endif
                                    </span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name">  @if($sdata['household']->getOriginal('type')) 承租人 @else 产权人 @endif： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['holder']->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 状态： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->state->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋状况： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->status}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 批准用途： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->defbuildinguse->name}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xs-12">
            <div class="widget-container-col ui-sortable">
                <div class="widget-box ui-sortable-handle">
                    <div class="widget-header">
                        <h5 class="widget-title">兑付汇总</h5>
                        <div class="widget-toolbar">
                            <a href="{{route('g_pay_edit',['item'=>$sdata['item']->id,'id'=>$sdata['pay']->id])}}" class="orange2">
                                <i class="ace-icon fa fa-edit"></i>
                                修改兑付方式
                            </a>
                        </div>

                    </div>

                    <div class="widget-body">
                        <div class="widget-main">
                            <div class="profile-user-info profile-user-info-striped">

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 补偿方式： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['pay']->repay_way}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 过渡方式： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['pay']->transit_way}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 搬迁方式： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['pay']->move_way}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 补偿总额： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">
                                            <strong>{{number_format($sdata['pay']->total,2)}}</strong>
                                            人民币（大写）{{bigRMB($sdata['pay']->total)}}
                                            @if($sdata['household']->getOriginal('type'))
                                                @php $household_total=$sdata['subjects']->sum('total'); @endphp
                                                <br>
                                                其中：
                                                【{{$sdata['holder']->name}}（承租人）】所得补偿款：
                                                <strong>{{number_format($household_total,2)}}</strong>
                                                人民币（大写）{{bigRMB($household_total)}}
                                            @endif
                                           
                                        </span>
                                    </div>
                                </div>

                                @if(filled($sdata['pay']->picture))
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 行政征收决定： </div>
                                        <div class="profile-info-value">
                                            <ul class="ace-thumbnails clearfix img-content">
                                                @foreach($sdata['pay']->picture as $pic)
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
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="tabbable">
        <ul class="nav nav-tabs padding-12 tab-color-blue background-blue">
            <li class="active">
                <a data-toggle="tab" href="#pay_subject" aria-expanded="true">补偿科目</a>
            </li>

            @if($sdata['pay']->getOriginal('repay_way')==1)

                <li class="">
                    <a data-toggle="tab" href="#pay_house" aria-expanded="false">产权调换房</a>
                </li>

            @endif

            @if($sdata['pay']->getOriginal('transit_way')==1)

                <li class="">
                    <a data-toggle="tab" href="#pay_transit" aria-expanded="false">临时周转房</a>
                </li>

            @endif


            <li class="">
                <a data-toggle="tab" href="#pact" aria-expanded="false">协议</a>
            </li>
        </ul>

        <div class="tab-content">
            <div id="pay_subject" class="tab-pane active">
                <table class="table table-hover table-bordered">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>名称</th>
                        <th>计算公式</th>
                        <th>补偿总额</th>
                        <th>被征收户补偿比例（%）</th>
                        <th>被征收户补偿金额</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(filled($sdata['subjects']))
                        @foreach($sdata['subjects'] as $subject)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$subject->subject->name}}</td>
                                <td>{{$subject->calculate}}</td>
                                <td>{{number_format($subject->amount,2)}}</td>
                                <td>{{number_format($subject->portion,2)}}</td>
                                <td>{{number_format($subject->total,2)}}</td>
                                <td>{{$subject->state->name}}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{route('g_paysubject_info',['id'=>$subject->id,'item'=>$sdata['item']->id])}}" class="btn btn-sm">查看详情</a>

                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>

            @if($sdata['pay']->getOriginal('repay_way')==1)

                <div id="pay_house" class="tab-pane">

                    <table class="table table-hover table-bordered treetable">
                        <thead>
                        <tr>
                            <th>房号</th>
                            <th>户型</th>
                            <th>面积</th>
                            <th>类型</th>
                            <th>市场价</th>
                            <th>安置价</th>
                            <th>安置房价</th>
                            <th>上浮房款</th>
                            <th>安置总价</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(filled($sdata['payhouses']))
                            @foreach($sdata['payhouses'] as $house)
                                <tr data-tt-id="house-{{$house->house_id}}" data-tt-parent-id="house">
                                    <td>{{$house->house->housecommunity->name}} {{$house->house->building}}栋{{$house->house->unit}}单元{{$house->house->floor}}楼{{$house->house->number}}@if(is_numeric($house->house->number))号@endif</td>
                                    <td>{{$house->house->layout->name}}</td>
                                    <td>{{$house->house->is_real}}</td>
                                    <td>{{number_format($house->area,2)}}</td>
                                    <td>{{number_format($house->market,2)}}</td>
                                    <td>{{number_format($house->price,2)}}</td>
                                    <td>{{number_format($house->amount,2)}}</td>
                                    <td>{{number_format($house->amount_plus,2)}}</td>
                                    <td>{{number_format($house->total,2)}}</td>
                                </tr>

                                @if(filled($house->housepluses))
                                    <tr data-tt-id="house-plus-{{$loop->index}}" data-tt-parent-id="house-{{$house->house_id}}">
                                        <th>上浮房款：</th>
                                        <td colspan="8">
                                            <table class="table table-hover table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>上浮面积起</th>
                                                    <th>上浮面积止</th>
                                                    <th>上浮面积</th>
                                                    <th>上浮比例(%)</th>
                                                    <th>市场价与安置价之差价</th>
                                                    <th>上浮金额</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($house->housepluses as $houseplus)
                                                    <tr>
                                                        <td>{{number_format($houseplus->start,2)}}</td>
                                                        <td>{{number_format($houseplus->end,2)}}</td>
                                                        <td>{{number_format($houseplus->area,2)}}</td>
                                                        <td>{{number_format($houseplus->rate,2)}}</td>
                                                        <td>{{number_format($houseplus->agio,2)}}</td>
                                                        <td>{{number_format($houseplus->amount,2)}}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                        </tbody>
                    </table>

                </div>

            @endif

            @if($sdata['pay']->getOriginal('transit_way')==1)

                <div id="pay_transit" class="tab-pane">

                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th>地址</th>
                            <th>房号</th>
                            <th>户型</th>
                            <th>面积</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(filled($sdata['paytransits']))
                            @foreach($sdata['paytransits'] as $transit)
                                <tr>
                                    <td>{{$transit->house->housecommunity->address}} {{$transit->house->housecommunity->name}}</td>
                                    <td>{{$transit->house->building}}栋{{$transit->house->unit}}单元{{$transit->house->floor}}楼{{$transit->house->number}}@if(is_numeric($transit->house->number))号@endif</td>
                                    <td>{{$transit->house->layout->name}}</td>
                                    <td>{{number_format($transit->area,2)}}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>

                </div>

            @endif


            <div id="pact" class="tab-pane">
                <table class="table table-hover table-bordered">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>类别</th>
                        <th>签约时间</th>
                        <th>状态</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(filled($sdata['pacts']))
                        @foreach($sdata['pacts'] as $pact)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$pact->pactcate->name}}</td>
                                <td>{{$pact->sign_at}}</td>
                                <td>{{$pact->state->name}} | {{$pact->status}}</td>
                                <td>
                                    <div class="btn-group">
                                        <a data-url="{{route('g_pact_info',['item'=>$pact->item_id,'pact_id'=>$pact->id])}}" class="btn btn-sm btn-info" onclick="layerWin(this)">查看内容</a>
                                        @if(in_array($pact->code,['170','174']))
                                        <a href="{{route('g_pact_reset_pact',['item'=>$pact->item_id,'pact_id'=>$pact->id])}}" class="btn btn-sm btn-danger">重新生成</a>
                                        @endif

                                        @if(in_array($pact->code,['171','172']))
                                            <a class="btn btn-sm btn-primary" data-toggle="modal" data-target="#model-pact-check" data-url="{{route('g_pact_check',['item'=>$pact->item_id,'pact_id'=>$pact->id])}}" onclick="setUrl(this)">审查</a>
                                        @endif

                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="model-pact-check" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title center" id="myModalLabel">审查意见</h4>
                </div>
                <div class="modal-footer center">
                    <button type="button" class="btn btn-info btn-check" onclick="pactCheck(this,1)">审查通过</button>
                    <button type="button" class="btn btn-danger btn-check" onclick="pactCheck(this,0)">审查驳回</button>
                </div>
            </div>
        </div>
    </div>

@endsection

{{-- 样式 --}}
@section('css')

    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}" />
    <link rel="stylesheet" href="{{asset('treetable/jquery.treetable.theme.default.css')}}">

@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script src="{{asset('treetable/jquery.treetable.js')}}"></script>
    <script src="{{asset('layer/layer.js')}}"></script>

    <script>
        $('.img-content').viewer();
        $(".treetable").treetable({
            expandable: true // 展示
            ,initialState :"collapsed"//默认打开所有节点
            ,stringCollapse:'关闭'
            ,stringExpand:'展开'});

        function layerWin(obj) {
            var that=$(obj);
            var lay=layer.open({
                type: 2
                ,skin:'layui-layer-lan'
                ,title:'协议详情'
                ,area: ['500px', '300px']
                ,maxmin:true
                ,content: that.data('url')
            });
            layer.full(lay);
        }

        function setUrl(obj) {
            $('#model-pact-check').find('button.btn-check').data('url',$(obj).data('url'));
        }
        function pactCheck(obj,result) {
            var btn=$('#model-pact-check').find('button.btn-check');
            if(btn.data('loading') || btn.hasClass('disabled')){
                return false;
            }
            btn.data('loading',true).addClass('disabled');
            toastr.info('请稍等！处理中……');
            ajaxAct($(obj).data('url'),{result:result},'get');
            if(ajaxResp.code=='success'){
                toastr.success(ajaxResp.message);
                if(ajaxResp.url){
                    setTimeout(function () {
                        location.href=ajaxResp.url;
                    },1000);
                }else{
                    setTimeout(function () {
                        location.reload();
                    },1000);
                }
            }else{
                toastr.error(ajaxResp.message);
                if(ajaxResp.url){
                    setTimeout(function () {
                        location.href=ajaxResp.url;
                    },1000);
                }else{
                    btn.data('loading',false).removeClass('disabled');
                }
            }
        }
    </script>

@endsection