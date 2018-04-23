{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')

    <div class="row">
        <div class="well well-sm">
            <a class="btn" href="{{route('g_household',['item'=>$sdata['item_id']])}}"><i class="ace-icon fa fa-arrow-left bigger-110"></i>返回</a>
            <a class="btn" href="{{route('g_household_edit',['id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                修改基本信息
            </a>
            @if(blank($edata['household_detail']))
                <a class="btn" href="{{route('g_householddetail_add',['household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                    添加详细信息
                </a>
            @else
                <a class="btn" href="{{route('g_householddetail_edit',['id'=>$edata['household_detail']->id,'item'=>$sdata->item_id])}}">
                    <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                    修改详细信息
                </a>
            @endif
            <a href="{{route('g_householdbuilding_add',['item'=>$edata['item_id'],'household_id'=>$sdata->id])}}" class="btn">添加房屋建筑</a>
            <a href="{{route('g_householdassets_add',['item'=>$edata['item_id'],'household_id'=>$sdata->id])}}" class="btn">添加资产信息</a>
            <a href="{{route('g_householdmember_add',['item'=>$edata['item_id'],'household_id'=>$sdata->id])}}" class="btn">添加家庭成员</a>
            <a href="{{route('g_householdobject_add',['item'=>$edata['item_id'],'household_id'=>$sdata->id])}}" class="btn">添加其他补偿事项</a>
        </div>

        <div class="well-sm">
            <div class="tabbable">
                <ul class="nav nav-tabs" id="myTab">
                    <li class="active">
                        <a data-toggle="tab" href="#household" aria-expanded="true">
                            <i class="green ace-icon fa fa-building bigger-120"></i>
                            基本信息
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#itembuildings" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            详细信息
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#householdbuilding" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            房屋建筑
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#householdassets" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            资产信息
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#member" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            家庭成员
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#objects" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            其他补偿事项
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div id="household" class="tab-pane fade active in">
                        <div class="profile-user-info profile-user-info-striped">

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 地块： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->itemland->address}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 楼栋： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->itembuilding->building}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 单元号： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->unit}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 楼层： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->floor}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房号： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->number}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 房产类型： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->type}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 用户名： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->username}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 描述： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->infos}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 创建时间： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->created_at}}</span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name"> 更新时间： </div>
                                <div class="profile-info-value">
                                    <span class="editable editable-click">{{$sdata->updated_at}}</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div id="itembuildings" class="tab-pane fade">
                        @if(isset($edata['household_detail']))
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权争议： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 面积争议： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->area_dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 状态： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->status}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋产权证号： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->register}}</span>
                                    </div>
                                </div>


                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 建筑面积： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->reg_outer}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 阳台面积： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->balcony}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 批准用途： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->defbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 实际用途： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->realbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 经营项目： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->business}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 资产评估： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->has_assets}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 征收意见： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->agree}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 补偿方式： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->repay_way}}</span>
                                    </div>
                                </div>

                                @if($edata['household_detail']->getOriginal('repay_way')==1)
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->house_price}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源面积： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->house_area}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源数量： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->house_num}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源地址： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->house_addr}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-增加面积单价： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->more_price}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源户型： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$edata['household_detail']->layout->name}}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 其他意见： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->opinion}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件人： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->receive_man}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件电话： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->receive_tel}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件地址： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->receive_addr}}</span>
                                    </div>
                                </div>

                                @if(isset($edata['household_detail']->picture))
                                    @foreach($edata['household_detail']->picture as $names=>$picturepic)
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> {{$edata['detail_filecates'][$names]}}： </div>
                                            <div class="profile-info-value">
                                            <span class="editable editable-click">
                                                 <ul class="ace-thumbnails clearfix img-content viewer">
                                                     @foreach($picturepic as $pics)
                                                         <li>
                                                            <div>
                                                                <img width="120" height="120" src="{!! $pics !!}" alt="加载失败">
                                                                <div class="text">
                                                                    <div class="inner">
                                                                        <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                         </li>
                                                     @endforeach
                                                </ul>
                                            </span>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 被征收人签名： </div>
                                    <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                             @if(isset($edata['household_detail']->sign))
                                                 <li>
                                                <div>
                                                    <img width="120" height="120" src="{{$edata['household_detail']->sign}}" alt="加载失败">
                                                    <div class="text">
                                                        <div class="inner">
                                                            <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            @endif
                                        </ul>
                                    </span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 创建时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['household_detail']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="profile-user-info profile-user-info-striped">
                                <span>暂无详细信息</span>&nbsp; <a class="btn" href="{{route('g_householddetail_add',['household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                                    添加详细信息
                                </a>
                            </div>
                        @endif
                    </div>

                    <div id="householdbuilding" class="tab-pane fade">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>名称</th>
                                <th>地块</th>
                                <th>楼栋</th>
                                <th>楼层</th>
                                <th>朝向</th>
                                <th>结构</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($edata['householdbuilding'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->name}}</td>
                                        <td>{{$infos->itemland->address}}</td>
                                        <td>{{$infos->itembuilding->building}}</td>
                                        <td>{{$infos->floor}}</td>
                                        <td>{{$infos->direct}}</td>
                                        <td>{{$infos->buildingstruct->name}}</td>
                                        <td>{{$infos->state->name}}</td>
                                        <td>
                                            <a href="{{route('g_householdbuilding_info',['id'=>$infos->id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">查看详情</a>
                                            <a class="btn btn-sm" data-toggle="modal" onclick="del_data('{{$infos->id}}','{{route('g_householdbuilding_del')}}')" data-target="#myModal">删除</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ count($edata['householdbuilding']) }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                    </div>

                    <div id="householdassets" class="tab-pane fade">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>名称</th>
                                <th>计量单位</th>
                                <th>数量</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($edata['householdassets'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->name}}</td>
                                        <td>{{$infos->num_unit}}</td>
                                        <td>{{$infos->gov_num}}</td>
                                        <td>
                                            <a href="{{route('g_householdassets_info',['id'=>$infos->id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">查看详情</a>
                                            <a class="btn btn-sm" data-toggle="modal" onclick="del_data('{{$infos->id}}','{{route('g_householdassets_del')}}')" data-target="#myModal">删除</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ count($edata['householdassets']) }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                    </div>

                    <div id="member" class="tab-pane fade">
                        @if(filled($edata['householdmember']))
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="col-xs-12">
                                    @foreach($edata['householdmember'] as $householdmember)
                                        <div class="col-xs-6 col-sm-3 pricing-box">
                                            <div class="widget-box widget-color-dark">
                                                <div class="widget-header">
                                                    <h5 class="widget-title bigger lighter">{{$householdmember->holder}}</h5>
                                                    <div class="widget-toolbar">
                                                        <a href="" onclick="del_data('{{$householdmember->id}}','{{route('g_householdmember_del')}}')" data-toggle="modal" data-target="#myModal"  class="orange2">
                                                            <i class="fa fa-trash-o fa-lg"></i>
                                                            删除
                                                        </a>
                                                    </div>
                                                    <div class="widget-toolbar">
                                                        <a href="{{route('g_householdmember_edit',['item'=>$householdmember->item_id,'id'=>$householdmember->id,'household_id'=>$householdmember->household_id])}}" class="orange2">
                                                            <i class="ace-icon fa fa-edit"></i>
                                                            编辑
                                                        </a>
                                                    </div>

                                                </div>

                                                <div class="widget-body">
                                                    <div class="widget-main">
                                                        <div class="profile-user-info profile-user-info-striped">

                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 姓名： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->name}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 与户主关系： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->relation}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 身份证： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->card_num}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 电话： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->phone}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 民族： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->nation->name}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 性别： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->sex}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 年龄： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->age}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 权属分配比例： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->portion}}</span>
                                                                </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> 是否享受特殊人群优惠： </div>
                                                                <div class="profile-info-value">
                                                                    <span class="editable editable-click">{{$householdmember->crowd}}</span>
                                                                </div>
                                                            </div>
                                                            @if($householdmember->getOriginal('crowd')==1)
                                                                <br/>
                                                                @if(filled($householdmember->householdmembercrowds))
                                                                    @foreach($householdmember->householdmembercrowds as $v)
                                                                        <div class="profile-info-row">
                                                                            <div class="profile-info-name"> 特殊人群信息： </div>
                                                                            <div class="profile-info-value">
                                                                                <span class="editable editable-click">{{$v->crowd->name}}</span><br/>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <a href="{{route('g_householdmember_info',['item'=>$householdmember->item_id,'household_id'=>$householdmember->household_id,'id'=>$householdmember->id])}}" class="btn btn-block btn-inverse">
                                                            <span>查看详情</span>
                                                            <i class="ace-icon fa fa-chevron-circle-right bigger-110"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="profile-user-info profile-user-info-striped">
                                <span>暂无数据</span>
                            </div>
                        @endif
                    </div>

                    <div id="objects" class="tab-pane fade">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>其他补偿事项</th>
                                <th>数量</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($edata['householdobject'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->object->name}}</td>
                                        <td>{{$infos->number}}</td>
                                        <td>
                                            <a href="{{route('g_householdobject_info',['id'=>$infos->id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">查看详情</a>
                                            <a class="btn btn-sm" data-toggle="modal" onclick="del_data('{{$infos->id}}','{{route('g_householdobject_del')}}')" data-target="#myModal">删除</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ count($edata['householdobject']) }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{--删除确认弹窗--}}
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">删除确认</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="del_id" value="">
                    <input type="hidden" id="del_url" value="">
                    你确定要删除本条数据吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary del_ok">确定</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
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
    <script src="{{asset('js/func.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>
        $('.img-content').viewer('update');

        /*---------弹出删除确认----------*/
        function del_data(id,url) {
            $('#del_id').val(id);
            $('#del_url').val(url);
        }
        /*---------确认删除----------*/
        $('.del_ok').on('click',function(){
            $('#myModal').modal('hide');
            var del_id = $('#del_id').val();
            if(!del_id){
                toastr.error('请选择要删除的数据！');
                return false;
            }
            var del_url = $('#del_url').val();
            if(!del_url){
                toastr.error('数据异常，请确认删除地址！');
                return false;
            }
            ajaxAct(del_url,{ id:del_id,item:'{{$sdata['item']->id}}'},'post');
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
            }
            return false;
        });
    </script>
@endsection


