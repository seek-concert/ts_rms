{{-- 继承主体 --}}
@extends('household.layout')

{{-- 页面内容 --}}
@section('content')

    <div class="row">

        <div class="well-sm">
            <div class="tabbable">
                <ul class="nav nav-tabs" id="myTab">
                    <li class="active">
                        <a data-toggle="tab" href="#itempublic" aria-expanded="false">
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
                            资产明细
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#member" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            家庭成员
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#items" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            其他补偿事项
                        </a>
                    </li>
                </ul>

                <div class="tab-content">

                    <div id="itempublic" class="tab-pane fade active in">
                        @if(isset($sdata['household_detail']))
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 地块：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->itemland->address}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 楼栋：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->itembuilding->building}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 单元号：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->unit}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 楼层：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->floor}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房号：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->number}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房产类型：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household']->type}}</span>
                                    </div>
                                </div>
                                @if($sdata['household']->type>0)
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 公产单位名称：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household']->itemland->adminunit->name}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 公产单位地址：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household']->itemland->adminunit->addre}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 公产单位联系人：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household']->itemland->adminunit->contact_man}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 公产单位联系电话：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household']->itemland->adminunit->phone}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 公产单位详细信息：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household']->itemland->adminunit->infos}}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权争议：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 面积争议：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">
                                            {{$sdata['household_detail']->area_dispute}}
                                        </span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 状态：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->state}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋产权证号：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->register}}</span>
                                    </div>
                                </div>

                              {{--  <div class="profile-info-row">
                                    <div class="profile-info-name"> 登记套内面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->reg_inner}}</span>
                                    </div>
                                </div>--}}

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 登记建筑面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->reg_outer}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 阳台面积：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->balcony}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 批准用途：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->defbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 实际用途：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->realbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 资产评估：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->has_assets}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 征收意见：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->agree}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 补偿方式：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->repay_way}}</span>
                                    </div>
                                </div>

                                @if($sdata['household_detail']->getOriginal('repay_way')==1)
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源单价：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->house_price}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源面积：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->house_area}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源数量：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->house_num}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源地址：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->house_addr}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-增加面积单价：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->more_price}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 产权调换意向<br/>-房源户型：</div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['household_detail']->layout->name}}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 其他意见：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->opinion}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件人：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->receive_man}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件电话：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->receive_tel}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 收件地址：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->receive_addr}}</span>
                                    </div>
                                </div>

                                @if(isset($sdata['household_detail']->picture))
                                    @foreach($sdata['household_detail']->picture as $names=>$picturepic)
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> {{$sdata['detail_filecates'][$names]}}： </div>
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
                                    <div class="profile-info-name"> 被征收人签名：</div>
                                    <div class="profile-info-value">
                                    <span class="editable editable-click">
                                         <ul class="ace-thumbnails clearfix img-content viewer">
                                                 <li>
                                                <div>
                                                    <img width="120" height="120"
                                                         src="{{$sdata['household_detail']->sign}}" alt="加载失败">
                                                    <div class="text">
                                                        <div class="inner">
                                                            <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 创建时间：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间：</div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$sdata['household_detail']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div id="householdbuilding" class="tab-pane fade">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>地块</th>
                                <th>楼栋</th>
                                <th>楼层</th>
                                <th>朝向</th>
                                <th>结构</th>
                                <th>登记建筑面积</th>
                                <th>阳台面积</th>
                                <th>实际建筑面积</th>
                                <th>批准用途</th>
                                <th>实际用途</th>
                                <th>状态</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($sdata['householdbuilding'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->itemland->address}}</td>
                                        <td>{{$infos->itembuilding->building}}</td>
                                        <td>{{$infos->floor}}</td>
                                        <td>{{$infos->direct}}</td>
                                        <td>{{$infos->buildingstruct->name}}</td>
                                        <td>{{$infos->reg_outer}}</td>
                                        <td>{{$infos->balcony}}</td>
                                        <td>{{$infos->real_outer}}</td>
                                        <td>{{$infos->defbuildinguse->name}}</td>
                                        <td>{{$infos->realbuildinguse->name}}</td>
                                        <td>{{$infos->state->name}}</td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">
                                    共 @if($code=='success') {{ count($sdata['householdbuilding']) }} @else 0 @endif 条数据
                                </div>
                            </div>
                        </div>

                    </div>
                    <div id="householdassets" class="tab-pane fade">
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>资产名称</th>
                                <th>资产数量</th>
                                <th>计量单位</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($sdata['householdassets'] as $infos)
                                    <tr>
                                        <td>{{$infos->name}}</td>
                                        <td>{{$infos->number}}</td>
                                        <td>{{$infos->num_unit}}</td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">
                                    共 @if($code=='success') {{ count($sdata['householdassets']) }} @else 0 @endif 条数据
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="member" class="tab-pane fade">
                        <div class="profile-user-info profile-user-info-striped">
                            <div class="col-xs-12">
                                @foreach($sdata['householdmember'] as $householdmember)
                                    <div class="col-xs-6 col-sm-3 pricing-box">
                                        <div class="widget-box widget-color-dark">
                                            <div class="widget-header">
                                                <h5 class="widget-title bigger lighter">{{$householdmember->holder}}</h5>

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

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div id="items" class="tab-pane fade">
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
                                @foreach($sdata['householdobject'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->object->name}}</td>
                                        <td>{{$infos->number}}</td>
                                        <td>
                                            <a href="{{route('h_householdobject_info',['id'=>$infos->id])}}"
                                               class="btn btn-sm">查看详情</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">
                                    共 @if($code=='success') {{ count($sdata['householdobject']) }} @else 0 @endif 条数据
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
    <link rel="stylesheet" href="{{asset('viewer/viewer.min.css')}}"/>
@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('js/func.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script>
        $('.img-content').viewer('update');
    </script>
@endsection


