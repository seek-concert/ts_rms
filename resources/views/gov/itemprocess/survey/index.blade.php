{{-- 继承aceAdmin后台布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <div class="widget-box widget-color-red">
        <div class="widget-header">
            <h4 class="widget-title lighter smaller">待处理工作</h4>
            <div class="widget-toolbar">
                <a href="{{route('g_householdright',['item'=>$sdata['item']->id])}}" class="btn btn-xs btn-info">查看明细</a>

                <a href="#" data-action="collapse">
                    <i class="ace-icon fa fa-chevron-up"></i>
                </a>
            </div>
        </div>

        <div class="widget-body">
            <div class="widget-main padding-8">
                <table class="table table-hover table-bordered">
                    <thead>
                    <tr>
                        <th>产权争议</th>
                        <th>合法性认定</th>
                        <th>面积争议</th>
                        <th>资产确认</th>
                        <th>房产确认</th>
                        <th>公共附属物确定</th>
                        <th>还未确权被征收户户数</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{$sdata['dispute_num']}}</td>
                        <td>{{$sdata['legal_num']}}</td>
                        <td>{{$sdata['area_dispute_num']}}</td>
                        <td>{{$sdata['assets_num']}}</td>
                        <td>{{$sdata['building_num']}}</td>
                        <td>{{$sdata['public_num']}}</td>
                        <td>{{$sdata['household_nums']}}</td>
                    </tr>
                    </tbody>
                </table>
                @if($sdata['item']->process_id==25 && $sdata['item']->code=='1' && $sdata['dispute_num']==0 && $sdata['legal_num']==0 && $sdata['area_dispute_num']==0 && $sdata['assets_num']==0 && $sdata['public_num']==0 && $sdata['building_num']==0)
                    @if($sdata['affirmation']==63)
                        <a class="btn btn-danger" onclick="btnAct(this)" data-url="{{route('g_survey',['item'=>$sdata['item']->id])}}" data-method="post">
                            <i class="ace-icon fa fa-support bigger-110"></i>
                            入户调查数据提交审查
                        </a>
                    @else
                        <a class="btn btn-danger red" >
                            <i class="ace-icon fa fa-support bigger-110"></i>
                            等待被征收户确权中
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="widget-container-col ui-sortable">
        <div class="widget-box ui-sortable-handle">
            <div class="widget-header">
                <h5 class="widget-title">项目地块</h5>

                <div class="widget-toolbar">
                    <a href="{{route('g_itemland',['item'=>$sdata['item']->id])}}" class="btn btn-xs btn-info">查看明细</a>

                    <a href="#" data-action="collapse">
                        <i class="ace-icon fa fa-chevron-up"></i>
                    </a>
                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main">
                    <div class="row">
                        <div class="col-xs-12 col-sm-9">
                            <table class="table table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th>地址</th>
                                    <th>性质</th>
                                    <th>占地面积</th>
                                    <th>户数</th>
                                    <th>私有户</th>
                                    <th>公房户</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($sdata['lands'] as $land)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$land->address}}</td>
                                        <td>{{$land->landprop->name}}</td>
                                        <td>{{$land->area}}</td>
                                        <td>{{$land->households_count}}</td>
                                        <td>{{$land->privates_count}}</td>
                                        <td>{{$land->publics_count}}</td>
                                    </tr>

                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xs-12 col-sm-3" id="land-prop-pie" style="min-height: 300px;">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="widget-container-col ui-sortable">
        <div class="widget-box ui-sortable-handle">
            <div class="widget-header">
                <h5 class="widget-title">被征收户</h5>

                <div class="widget-toolbar">
                    <a href="{{route('g_householddetail',['item'=>$sdata['item']->id])}}" class="btn btn-xs btn-info">查看明细</a>

                    <a href="#" data-action="collapse">
                        <i class="ace-icon fa fa-chevron-up"></i>
                    </a>
                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main row">

                    <div class="col-xs-6 col-sm-3 pricing-box">
                        <div class="widget-box widget-color-orange">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">房屋状况</h5>
                            </div>

                            <div class="widget-body">
                                <div class="widget-main" style="min-width: 300px;min-height: 300px" id="status-household">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-6 col-sm-3 pricing-box">
                        <div class="widget-box widget-color-blue">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">征收意见</h5>
                            </div>

                            <div class="widget-body">
                                <div class="widget-main" style="min-width: 300px;min-height: 300px" id="agree-household">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-6 col-sm-3 pricing-box">
                        <div class="widget-box widget-color-green">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">补偿方式</h5>
                            </div>

                            <div class="widget-body">
                                <div class="widget-main" style="min-width: 300px;min-height: 300px" id="repay-household">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-6 col-sm-3 pricing-box">
                        <div class="widget-box widget-color-grey">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">批准用途</h5>
                            </div>

                            <div class="widget-body">
                                <div class="widget-main" style="min-width: 300px;min-height: 300px" id="defuse-household">

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
    @parent
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script src="{{asset('echarts/echarts.common.min.js')}}"></script>
    <script>
        $('.img-content').viewer();

        // 土地性质
        var land_props=@json($sdata['land_props']);
        var items=[];
        var values=[];
        $.each(land_props,function (index,info) {
            items.push(info.landprop.name);
            values.push({value:info.prop_area,name:info.landprop.name});
        });
        echarts.init(document.getElementById('land-prop-pie')).setOption({
            title : {
                text: '土地性质',
                x:'center'
            },
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} ㎡ <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:values,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });
        // 房屋状况
        var status_households=@json($sdata['status_households']);
        items=[];
        values=[];
        $.each(status_households,function (index,info) {
            items.push(info.status);
            values.push({value:info.status_num,name:info.status});
        });
        echarts.init(document.getElementById('status-household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:values,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });
        // 征求意见
        var agree_households=@json($sdata['agree_households']);
        items=[];
        values=[];
        $.each(agree_households,function (index,info) {
            items.push(info.agree);
            values.push({value:info.agree_num,name:info.agree});
        });
        echarts.init(document.getElementById('agree-household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:values,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });
        // 补偿方式
        var repay_households=@json($sdata['repay_households']);
        items=[];
        values=[];
        $.each(repay_households,function (index,info) {
            items.push(info.repay_way);
            values.push({value:info.repay_num,name:info.repay_way});
        });
        echarts.init(document.getElementById('repay-household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:values,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });
        // 批准用途
        var defuse_households=@json($sdata['defuse_households']);
        items=[];
        values=[];
        $.each(defuse_households,function (index,info) {
            items.push((info.defbuildinguse.name || '其他'));
            values.push({value:info.defuse_num,name:(info.defbuildinguse.name || '其他')});
        });
        echarts.init(document.getElementById('defuse-household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['50%', '60%'],
                    data:values,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        });
    </script>

@endsection