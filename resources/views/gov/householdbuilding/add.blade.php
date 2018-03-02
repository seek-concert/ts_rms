{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </p>


    <form class="form-horizontal" role="form" action="{{route('g_householdbuilding_add')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="item" value="{{$sdata['item_id']}}">
        <input type="hidden" name="household_id" value="{{$sdata['household']->id}}">
        <input type="hidden" name="land_id" value="{{$sdata['household']->land_id}}">
        <input type="hidden" name="building_id" value="{{$sdata['household']->building_id}}">

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="register"> 是否登记： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->register as $key => $value)
                    <label>
                        <input name="register" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="struct_id"> 结构： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="struct_id" id="struct_id">
                    <option value="">--请选择--</option>
                    @if($sdata['buildingstruct'])
                        @foreach($sdata['buildingstruct'] as $buildingstruct)
                            <option value="{{$buildingstruct->id}}">{{$buildingstruct->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="reg_inner"> 登记套内面积： </label>
            <div class="col-sm-9">
                <input type="text" id="reg_inner" name="reg_inner" value="{{old('reg_inner')}}" class="col-xs-10 col-sm-5"  placeholder="请输入登记套内面积" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="reg_outer"> 登记建筑面积： </label>
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
            <label class="col-sm-3 control-label no-padding-right" for="dispute"> 面积争议： </label>
            <div class="col-sm-9 radio">
                <label>
                    <input name="dispute" type="radio" class="ace dispute_val" value="0" checked >
                    <span class="lbl">没有争议</span>
                </label>
                <label>
                    <input name="dispute" type="radio" class="ace dispute_val" value="1" >
                    <span class="lbl">存在争议</span>
                </label>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group dispute_state" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="real_inner"> 实际套内面积： </label>
            <div class="col-sm-9">
                <input type="text" id="real_inner" name="real_inner" value="{{old('real_inner')}}" class="col-xs-10 col-sm-5"  placeholder="请输入实际套内面积" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group dispute_state" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="real_outer"> 实际建筑面积： </label>
            <div class="col-sm-9">
                <input type="text" id="real_outer" name="real_outer" value="{{old('real_outer')}}" class="col-xs-10 col-sm-5"  placeholder="请输入实际建筑面积" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="def_use"> 批准用途： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="def_use" id="def_use">
                    <option value="">--请选择--</option>
                    @if($sdata['buildinguse'])
                        @foreach($sdata['buildinguse'] as $buildinguse)
                            <option value="{{$buildinguse->id}}">{{$buildinguse->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="real_use"> 实际用途： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="real_use" id="real_use">
                    <option value="">--请选择--</option>
                    @if($sdata['buildinguse'])
                        @foreach($sdata['buildinguse'] as $buildinguse)
                            <option value="{{$buildinguse->id}}">{{$buildinguse->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="direct"> 朝向： </label>
            <div class="col-sm-9">
                <input type="text" id="direct" name="direct" value="{{old('direct')}}" class="col-xs-10 col-sm-5"  placeholder="请输入朝向" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="floor"> 楼层： </label>
            <div class="col-sm-9">
                <input type="text" id="floor" name="floor" value="{{old('floor')}}" class="col-xs-10 col-sm-5"  placeholder="请输入楼层" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <div class="widget-main padding-8">
                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        平面图形：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="layout_img[]" multiple  onchange="uplfile(this)">
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

        <div class="form-group">
            <div class="widget-main padding-8">
                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        图片及证件：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="picture[]" multiple  onchange="uplfile(this)">
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

        <div class="clearfix form-actions">
            <div class="col-md-offset-3 col-md-9">
                <button class="btn btn-info" type="button" onclick="sub_ajax(this)">
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
        /*-------- 是否存在争议 -----------*/
        $('.dispute_val').on('click',function(){
            var dispute = $('input[name=dispute]:checked').val();
            if(dispute==1){
                $('.dispute_state').css('display','block');
            }else{
                $('.dispute_state').css('display','none');
            }
        });
        /*-------- 添加 -----------*/
        function sub_ajax(obj) {
            var dispute = $('input[name=dispute]:checked').val();
            var reg_inner = $('#reg_inner').val();
            var reg_outer = $('#reg_outer').val();
            $('.dispute_state').css('display','block');
            if(dispute==0){
                $('#real_inner').val(reg_inner);
                $('#real_outer').val(reg_outer);
            }
            sub(obj);
        }
    </script>

@endsection