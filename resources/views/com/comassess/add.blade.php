{{-- 继承布局 --}}
@extends('com.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </p>

    <div class="well well-sm">
        @if($sdata['type']==0)房产评估@else资产评估@endif
    </div>

    <div class="well-sm">
        <div class="tabbable">
            <ul class="nav nav-tabs" id="myTab">
                <li class="active">
                    <a data-toggle="tab" href="#household" aria-expanded="true">
                        <i class="green ace-icon fa fa-building bigger-120"></i>
                        被征户信息
                    </a>
                </li>
                <li class="">
                    <a data-toggle="tab" href="#assess" aria-expanded="false">
                        <i class="green ace-icon fa fa-home bigger-120"></i>
                        @if($sdata['item_program'])开始评估@else 开始预评估@endif
                    </a>
                </li>
            </ul>
            <div class="tab-content">
                <div id="household" class="tab-pane fade active in">
                    <div class="profile-user-info profile-user-info-striped">

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 地块地址： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['household']->itemland->address}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 楼栋： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['household']->itembuilding->building}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 位置： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['household']->unit?$sdata['household']->unit.'单元':''}}{{$sdata['household']->floor?$sdata['household']->floor.'楼':''}}{{$sdata['household']->number?$sdata['household']->number.'号':''}}</span>
                            </div>
                        </div>


                        <div class="profile-info-row">
                            <div class="profile-info-name"> 描述： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['household']->infos}}</span>
                            </div>
                        </div>
                        <br/>
                        <div class="profile-info-row">
                            <div class="profile-info-name"> 产权争议： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->dispute}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 面积争议： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->area_dispute}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 房屋状态： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->status}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 房屋产权证号： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->register}}</span>
                            </div>
                        </div>


                        <div class="profile-info-row">
                            <div class="profile-info-name"> 建筑面积： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->reg_outer}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 阳台面积： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->balcony}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 批准用途： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->defbuildinguse->name}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 实际用途： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->realbuildinguse->name}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 经营项目： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->business}}</span>
                            </div>
                        </div>

                        <div class="profile-info-row">
                            <div class="profile-info-name"> 资产评估： </div>
                            <div class="profile-info-value">
                                <span class="editable editable-click">{{$sdata['estate']->has_assets}}</span>
                            </div>
                        </div>

                        @if(isset($sdata['estate']->house_pic))
                            @foreach($sdata['estate']->house_pic as $names=>$picturepic)
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> {{$sdata['filecates'][$names]}}： </div>
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
                                                <img width="120" height="120" src="{{$sdata['estate']->sign}}" alt="加载失败">
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
                    </div>
                </div>
                <div id="assess" class="tab-pane fade">
                    @if($sdata['type']==0)
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ count($sdata['estatebuildings']) }} @else 0 @endif 条数据</div>
                            </div>
                        </div>
                        <form class="form-horizontal" role="form" action="{{route('c_comassess_add')}}" method="post">
                            <input type="hidden" name="household_id" value="{{$sdata['estate']->household_id}}">
                            <input type="hidden" name="item" value="{{$sdata['item_id']}}">
                            <table class="table table-hover table-bordered">
                                <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>地块</th>
                                    <th>楼栋</th>
                                    <th>楼层</th>
                                    <th>朝向</th>
                                    <th>结构类型</th>
                                    <th>实际建筑面积</th>
                                    <th>实际用途</th>
                                    <th>评估单价</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($code=='success')
                                    @foreach($sdata['estatebuildings'] as $infos)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$infos->itemland->address}}</td>
                                            <td>{{$infos->itembuilding->building}}</td>
                                            <td>{{$infos->floor}}</td>
                                            <td>{{$infos->direct}}</td>
                                            <td>{{$infos->buildingstruct->name}}</td>
                                            <td>{{$infos->real_outer}}</td>
                                            <td>{{$infos->realuse->name}}</td>
                                            <td><input type="text" name="price[{{$infos->id}}]" value="" placeholder="请填写评估单价"></td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="valuer_id"> 评估师[注册号]： </label>
                                <div class="col-sm-9 checkbox">
                                    @foreach($sdata['valuer'] as $valuer)
                                        <label>
                                            <input name="valuer_id[]" type="checkbox" class="ace" value="{{$valuer->id}}">
                                            <span class="lbl">{{$valuer->name}}【{{$valuer->register}}】</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="space-4"></div>

                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info" type="button" onclick="sub(this)">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        提交评估
                                    </button>
                                    &nbsp;&nbsp;&nbsp;
                                    <button class="btn" type="reset">
                                        <i class="ace-icon fa fa-undo bigger-110"></i>
                                        重置
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <table class="table table-hover table-bordered">
                            <thead>
                            <tr>
                                <th>序号</th>
                                <th>名称</th>
                                <th>计量单位</th>
                                <th>确认数量</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($code=='success')
                                @foreach($sdata['householdassetss'] as $infos)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$infos->name}}</td>
                                        <td>{{$infos->num_unit}}</td>
                                        <td>{{$infos->number}}</td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        <form class="form-horizontal" role="form" action="{{route('c_comassess_add')}}" method="post">
                            <input type="hidden" name="household_id" value="{{$sdata['assets']->household_id}}">
                            <input type="hidden" name="item" value="{{$sdata['item_id']}}">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="total"> 资产总价： </label>
                                <div class="col-sm-9">
                                    <input type="text" id="total" name="total" value="" class="col-xs-10 col-sm-5"  placeholder="请输入资产总价" required>
                                </div>
                            </div>
                            <div class="space-4"></div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right" for="valuer_id"> 评估师[注册号]： </label>
                                <div class="col-sm-9 checkbox">
                                    @foreach($sdata['valuer'] as $valuer)
                                        <label>
                                            <input name="valuer_id[]" type="checkbox" class="ace" value="{{$valuer->id}}">
                                            <span class="lbl">{{$valuer->name}}【{{$valuer->register}}】</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="space-4"></div>

                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info" type="button" onclick="sub(this)">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        提交评估
                                    </button>
                                    &nbsp;&nbsp;&nbsp;
                                    <button class="btn" type="reset">
                                        <i class="ace-icon fa fa-undo bigger-110"></i>
                                        重置
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

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
    <script src="{{asset('js/func.js')}}"></script>
    <script>
        $('.img-content').viewer('update');
    </script>
@endsection