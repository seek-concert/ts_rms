{{-- 继承布局 --}}
@extends('com.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="{{route('c_household',['item'=>$sdata['item_id']])}}">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
        @if($edata['type']==0)
            @if(filled($edata['estate']))
                <a class="btn" href="{{route('c_household_edit',['id'=>$edata['estate']->id,'household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                    <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                    修改房产信息
                </a>
             @else
                <a class="btn" href="{{route('c_household_add',['household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                    <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                    添加房产信息
                </a>
            @endif
                <a class="btn" href="{{route('c_household_buildingadd',['household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                    <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                    添加房屋建筑
                </a>
        @else
            <a class="btn" href="{{route('c_household_add',['household_id'=>$sdata->id,'item'=>$sdata->item_id])}}">
                <i class="ace-icon fa fa-pencil-square-o bigger-110"></i>
                添加资产信息
            </a>
        @endif
    </p>

    <div class="well-sm">
        <div class="tabbable">
            <ul class="nav nav-tabs" id="myTab">
                <li class="active">
                    <a data-toggle="tab" href="#household" aria-expanded="true">
                        <i class="green ace-icon fa fa-building bigger-120"></i>
                        基本信息
                    </a>
                </li>
                @if($edata['type']==0)
                    <li class="">
                        <a data-toggle="tab" href="#householdbuilding" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            房产信息
                        </a>
                    </li>

                    <li class="">
                        <a data-toggle="tab" href="#estatebuilding" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            房屋建筑
                        </a>
                    </li>
                    @else
                    <li class="">
                        <a data-toggle="tab" href="#householdassets" aria-expanded="false">
                            <i class="green ace-icon fa fa-home bigger-120"></i>
                            资产信息
                        </a>
                    </li>
                 @endif
            </ul>
            <div class="tab-content">
                <div id="household" class="tab-pane fade active in">
                    <div class="profile-user-info profile-user-info-striped">
                        <div class="profile-info-row">
                            <div class="profile-info-name"> 地块地址： </div>
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
                            <div class="profile-info-name"> 描述： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata->infos}}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($edata['type']==0)
                    <div id="householdbuilding" class="tab-pane fade">
                        @if(filled($edata['estate']))
                            <div class="profile-user-info profile-user-info-striped">
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 产权争议： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 面积争议： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->area_dispute}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋状态： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->status}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 房屋产权证号： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->register}}</span>
                                    </div>
                                </div>


                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 建筑面积： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->reg_outer}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 阳台面积： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->balcony}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 批准用途： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->defbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 实际用途： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->realbuildinguse->name}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 经营项目： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->business}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 资产评估： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->has_assets}}</span>
                                    </div>
                                </div>


                                @if(isset($edata['estate']->house_pic))
                                    @foreach($edata['estate']->house_pic as $names=>$picturepic)
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> {{$edata['filecates'][$names]}}： </div>
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
                                                 <li>
                                                <div>
                                                    <img width="120" height="120" src="{{$edata['estate']->sign}}" alt="加载失败">
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
                                    <div class="profile-info-name"> 创建时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->created_at}}</span>
                                    </div>
                                </div>

                                <div class="profile-info-row">
                                    <div class="profile-info-name"> 更新时间： </div>
                                    <div class="profile-info-value">
                                        <span class="editable editable-click">{{$edata['estate']->updated_at}}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="profile-user-info profile-user-info-striped">
                                <span>暂无房产信息</span>
                            </div>
                        @endif
                    </div>

                    <div id="estatebuilding" class="tab-pane fade">
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
                            @if(filled($edata['buildings']))
                                @foreach($edata['buildings'] as $infos)
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
                                            <a href="{{route('c_household_buildinginfo',['id'=>$infos->id,'item'=>$infos->item_id,'household_id'=>$infos->household_id])}}" class="btn btn-sm">查看详情</a>
                                            <a class="btn btn-sm" data-toggle="modal" onclick="del_data('{{$infos->id}}')" data-target="#myModal">删除</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if(filled($edata['buildings'])) {{ count($edata['buildings']) }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div id="householdassets" class="tab-pane fade">
                        @if(filled($edata['householdassets']))
                            <div class="profile-user-info profile-user-info-striped">
                                @foreach($edata['householdassets'] as $info)
                                    <div class="col-xs-6 col-sm-3 pricing-box">
                                        <div class="widget-box widget-color-dark">
                                            <div class="widget-header">
                                                <h5 class="widget-title bigger lighter">{{$info->name}}</h5>
                                                <div class="widget-toolbar">
                                                    <a href="{{route('c_household_edit',['item'=>$info->item_id,'id'=>$info->id,'household_id'=>$info->household_id])}}" class="orange2">
                                                        <i class="ace-icon fa fa-edit"></i>
                                                        编辑
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="widget-body">
                                                <div class="widget-main">

                                                    <div class="profile-user-info profile-user-info-striped">

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 数量： </div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$info->com_num}}</span>
                                                            </div>
                                                        </div>
                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 计量单位： </div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">{{$info->num_unit}}</span>
                                                            </div>
                                                        </div>

                                                        <div class="profile-info-row">
                                                            <div class="profile-info-name"> 图片： </div>
                                                            <div class="profile-info-value">
                                                                <span class="editable editable-click">
                                                                    <ul class="ace-thumbnails clearfix img-content viewer">
                                                                          @if($info->com_pic)
                                                                            @foreach($info->com_pic as $pic)
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
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="profile-user-info profile-user-info-striped">
                                <span>暂无数据</span>
                            </div>
                        @endif

                    </div>
                @endif

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
        function del_data(id) {
            $('#del_id').val(id);
        }
        /*---------确认删除----------*/
        $('.del_ok').on('click',function(){
            $('#myModal').modal('hide');
            var del_id = $('#del_id').val();
            console.log(del_id);
            if(!del_id){
                toastr.error('请选择要删除的数据！');
                return false;
            }
            ajaxAct('{{route('c_household_buildingdel')}}',{ id:del_id,item:'{{$sdata['item_id']}}'},'post');
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