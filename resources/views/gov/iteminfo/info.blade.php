{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')


        @if($sdata['item']->schedule_id==1 && $sdata['item']->process_id==1 && $sdata['item']->code=='2')
        <p>
            <a class="btn" href="{{route('g_iteminfo_edit',['item'=>$sdata['item']->id])}}">
                <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                修改
            </a>

            <a class="btn" onclick="btnAct(this)" data-url="{{route('g_check_to_dept_check',['item'=>$sdata['item']->id])}}">
                <i class="ace-icon fa fa-cloud-upload bigger-110"></i>
                提交部门审查
            </a>

        </p>

        @elseif($sdata['item']->schedule_id==1 && $sdata['item']->process_id==4 && $sdata['item']->code=='2')
            <p><a class="btn" onclick="btnAct(this)" data-url="{{route('g_check_to_dept_check',['item'=>$sdata['item']->id])}}">
                <i class="ace-icon fa fa-cloud-upload bigger-110"></i>
                提交部门审查
            </a></p>

        @elseif($sdata['item']->schedule_id==1 && $sdata['item']->process_id==3 && $sdata['item']->code=='22')
            <p><a class="btn" onclick="btnAct(this)" data-url="{{route('g_check_to_gov_check',['item'=>$sdata['item']->id])}}">
                    <i class="ace-icon fa fa-cloud-upload bigger-110"></i>
                    提交区政府审查
                </a></p>
        @endif



    <div class="profile-user-info profile-user-info-striped">

        <div class="profile-info-row">
            <div class="profile-info-name"> 名称： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->name}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 征收范围： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->place}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 征收范围红线地图： </div>
            <div class="profile-info-value">
                <ul class="ace-thumbnails clearfix img-content">
                    <li>
                        <div>
                            <img width="120" height="120" src="{{$sdata['item']->map}}" alt="加载失败">
                            <div class="text">
                                <div class="inner">
                                    <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 描述： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->infos}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 状态： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">
                    {{$sdata['item']->schedule->name}} - {{$sdata['item']->process->name}} ({{$sdata['item']->state->name}})
                </span>
            </div>
        </div>

        @if(filled($sdata['item']->picture))
        @foreach($sdata['item']->picture as $name=>$pictures)
            <div class="profile-info-row">
                <div class="profile-info-name"> {{$edata[$name] or '项目审查资料'}}： </div>
                <div class="profile-info-value">
                    <ul class="ace-thumbnails clearfix img-content">
                        @foreach($pictures as $pic)
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
        @endforeach
        @endif

        <div class="profile-info-row">
            <div class="profile-info-name"> 创建时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->created_at}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 更新时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->updated_at}}</span>
            </div>
        </div>

        <div class="profile-info-row">
            <div class="profile-info-name"> 删除时间： </div>
            <div class="profile-info-value">
                <span class="editable editable-click">{{$sdata['item']->deleted_at}}</span>
            </div>
        </div>

    </div>

        <div class="widget-container-col ui-sortable">
            <div class="widget-box ui-sortable-handle">
                <div class="widget-header">
                    <h5 class="widget-title">被征收户信息饼状图</h5>
                </div>

                <div class="widget-body">
                    <div class="widget-main row">

                        <div class="col-xs-6 col-sm-3 pricing-box">
                            <div class="widget-box widget-color-orange">
                                <div class="widget-header">
                                    <h5 class="widget-title bigger lighter">摸底调查状态</h5>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main" style="min-width: 300px;min-height: 300px" id="survey_nums_household">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-6 col-sm-3 pricing-box">
                            <div class="widget-box widget-color-blue">
                                <div class="widget-header">
                                    <h5 class="widget-title bigger lighter">确权确户状态</h5>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main" style="min-width: 300px;min-height: 300px" id="property_household">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-6 col-sm-3 pricing-box">
                            <div class="widget-box widget-color-green">
                                <div class="widget-header">
                                    <h5 class="widget-title bigger lighter">房产评估状况</h5>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main" style="min-width: 300px;min-height: 300px" id="com_household">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-6 col-sm-3 pricing-box">
                            <div class="widget-box widget-color-grey">
                                <div class="widget-header">
                                    <h5 class="widget-title bigger lighter">协议签约比例</h5>
                                </div>

                                <div class="widget-body">
                                    <div class="widget-main" style="min-width: 300px;min-height: 300px" id="contract_household">

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
    <script src="{{asset('js/func.js')}}"></script>

    <script>
        $('.img-content').viewer();

        @if($code=='success')
        // 摸底调查
        var survey_nums_household=@json($sdata['survey_nums']);
        items=[];
        values=[];
        $.each(survey_nums_household,function (index,info) {
            items.push((info.name_info|| '其他'));
            values.push({value:info.survey_num,name:(info.name_info|| '其他')});
        });
        echarts.init(document.getElementById('survey_nums_household')).setOption({
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
        //确权确户
        var property_household=@json($sdata['property_nums']);
        items=[];
        values=[];
        $.each(property_household,function (index,info) {
            items.push((info.name_info|| '其他'));
            values.push({value:info.property_num,name:(info.name_info|| '其他')});
        });
        echarts.init(document.getElementById('property_household')).setOption({
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
        //评估状况
        var com_household=@json($sdata['com_count']);
        items=[];
        values=[];
        $.each(com_household,function (index,info) {
            items.push((info.state.name|| '其他'));
            values.push({value:info.com_count,name:(info.state.name|| '其他')});
        });
        echarts.init(document.getElementById('com_household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['55%', '60%'],
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
        //协议签约
        var contract_household=@json($sdata['contract_count']);
        items=[];
        values=[];
        $.each(contract_household,function (index,info) {
            items.push((info.state.name|| '其他'));
            values.push({value:info.contract_count,name:(info.state.name|| '其他')});
        });
        echarts.init(document.getElementById('contract_household')).setOption({
            tooltip : {
                trigger: 'item',
                formatter: "{b}：<br/>{c} 户 <br/>({d}%)"
            },
            series : [
                {
                    name: null,
                    type: 'pie',
                    radius : '55%',
                    center: ['55%', '60%'],
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
        @endif
    </script>

@endsection