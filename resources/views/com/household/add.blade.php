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


    <form class="form-horizontal" role="form" action="{{route('c_household_add')}}" method="post">
        {{csrf_field()}}
        @if($sdata['type']==0)
            <input type="hidden" name="item" value="{{$sdata['item_id']}}">
            <input type="hidden" name="household_id" value="{{$sdata['household']->id}}">
            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="land_id"> 地块： </label>
                <div class="col-sm-9">
                    <input type="hidden" name="land_id" value="{{$sdata['household']->land_id}}">
                    <input type="text" id="land_id" value="{{$sdata['household']->itemland->address}}" class="col-xs-10 col-sm-5"  readonly>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="building_id"> 楼栋： </label>
                <div class="col-sm-9">
                    <input type="hidden" name="building_id" value="{{$sdata['household']->building_id}}">
                    <input type="text" id="building_id" value="{{$sdata['household']->itembuilding->building}}" class="col-xs-10 col-sm-5"  readonly>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="dispute"> 产权争议： </label>
                <div class="col-sm-9 radio">
                    @foreach($sdata['models']->dispute as $key => $value)
                        <label>
                            <input name="dispute" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                            <span class="lbl">{{$value}}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="area_dispute"> 面积争议： </label>
                <div class="col-sm-9 radio">
                    @foreach($sdata['models']->area_dispute as $key => $value)
                        @if($key!=2)
                        <label>
                            <input name="area_dispute" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                            <span class="lbl">{{$value}}</span>
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="status"> 房屋状态： </label>
                <div class="col-sm-9 radio">
                    @foreach($sdata['models']->status as $key => $value)
                        <label>
                            <input name="status" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                            <span class="lbl">{{$value}}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="register"> 房屋产权证号： </label>
                <div class="col-sm-9">
                    <input type="text" id="register" name="register" value="{{old('register')}}" class="col-xs-10 col-sm-5"  placeholder="请输入房屋产权证号" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="reg_outer"> 建筑面积： </label>
                <div class="col-sm-9">
                    <input type="text" id="reg_outer" name="reg_outer" value="{{old('reg_outer')}}" class="col-xs-10 col-sm-5"  placeholder="请输入登记建筑面积" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="balcony"> 阳台面积： </label>
                <div class="col-sm-9">
                    <input type="text" id="balcony" name="balcony" value="{{old('balcony')}}" class="col-xs-10 col-sm-5"  placeholder="请输入阳台面积" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="def_use"> 批准用途： </label>
                <div class="col-sm-9">
                    <select class="col-xs-5 col-sm-5" name="def_use" id="def_use">
                        <option value="0">--请选择--</option>
                            @foreach($sdata['defuse'] as $defuse)
                                <option value="{{$defuse->id}}">{{$defuse->name}}</option>
                            @endforeach
                    </select>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="real_use"> 实际用途： </label>
                <div class="col-sm-9">
                    <select class="col-xs-5 col-sm-5" name="real_use" id="real_use">
                        <option value="0">--请选择--</option>
                        @foreach($sdata['defuse'] as $defuse)
                            <option value="{{$defuse->id}}">{{$defuse->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="business"> 经营项目： </label>
                <div class="col-sm-9">
                    <input type="text" id="business" name="business" value="{{old('business')}}" class="col-xs-10 col-sm-5"  placeholder="请输入经营项目" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="has_assets"> 资产评估： </label>
                <div class="col-sm-9 radio">
                    @foreach($sdata['models']->has_assets as $key => $value)
                        <label>
                            <input name="has_assets" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                            <span class="lbl">{{$value}}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <div class="widget-main padding-8">
                    @foreach($sdata['filecates'] as $filecate)
                        <div class="form-group img-box">
                            <label class="col-sm-3 control-label no-padding-right">
                                {{$filecate->name}}<br>
                                <span class="btn btn-xs">
                                        <span>上传图片</span>
                                        <input type="file" accept="image/*" class="hidden" data-name="house_pic[{{$filecate->filename}}][]" multiple onchange="uplfile(this)">
                                    </span>
                            </label>
                            <div class="col-sm-9">
                                <ul class="ace-thumbnails clearfix img-content">

                                </ul>
                            </div>
                        </div>
                        <div class="space-4 header green"></div>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <div class="widget-main padding-8">
                    <div class="form-group img-box">
                        <label class="col-sm-3 control-label no-padding-right">
                            被征收人签名：<br>
                            <span class="btn btn-xs">
                                <span>上传图片</span>
                                <input type="file" accept="image/*" class="hidden" data-name="sign"  onchange="uplfile(this)">
                            </span>
                        </label>
                        <div class="col-sm-9">
                            <ul class="ace-thumbnails clearfix img-content viewer">

                            </ul>
                        </div>
                    </div>

                    <div class="space-4 header green"></div>

                </div>
            </div>
        @else
            <input type="hidden" name="item" value="{{$sdata['item_id']}}">
            <input type="hidden" name="household_id" value="{{$sdata['household']->id}}">
            <input type="hidden" name="land_id" value="{{$sdata['household']->land_id}}">
            <input type="hidden" name="building_id" value="{{$sdata['household']->building_id}}">

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="name"> 名称： </label>
                <div class="col-sm-9">
                    <input type="text" id="name" name="name" value="{{old('name')}}" class="col-xs-10 col-sm-5"  placeholder="请输入名称" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="num_unit"> 计量单位： </label>
                <div class="col-sm-9">
                    <input type="text" id="num_unit" name="num_unit" value="{{old('num_unit')}}" class="col-xs-10 col-sm-5"  placeholder="请输入计量单位" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <label class="col-sm-3 control-label no-padding-right" for="com_num"> 数量： </label>
                <div class="col-sm-9">
                    <input type="number" id="com_num" name="com_num" value="{{old('com_num')}}" class="col-xs-10 col-sm-5"  placeholder="请输入数量" required>
                </div>
            </div>
            <div class="space-4"></div>

            <div class="form-group">
                <div class="widget-main padding-8">
                    <div class="form-group img-box">
                        <label class="col-sm-3 control-label no-padding-right">
                            图片：<br>
                            <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="com_pic[]" multiple  onchange="uplfile(this)">
                        </span>
                        </label>
                        <div class="col-sm-9">
                            <ul class="ace-thumbnails clearfix img-content viewer">
                            </ul>
                        </div>
                    </div>
                    <div class="space-4 header green"></div>
                </div>
            </div>

        @endif

        <div class="clearfix form-actions">
            <div class="col-md-offset-3 col-md-9">
                <button class="btn btn-info" type="button" onclick="sub(this)">
                    <i class="ace-icon fa fa-check bigger-110"></i>
                    保存
                </button>
                &nbsp;&nbsp;&nbsp;
                <button class="btn" type="reset">
                    <i class="ace-icon fa fa-undo bigger-110"></i>
                    重置
                </button>
            </div>
        </div>
    </form>


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

    </script>

@endsection