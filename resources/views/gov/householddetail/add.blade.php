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


    <form class="form-horizontal" role="form" action="{{route('g_householddetail_add')}}" method="post">
        {{csrf_field()}}
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
                @foreach($sdata['detailmodel']->dispute as $key => $value)
                    @if($key!=2)
                    <label>
                        <input name="dispute" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="area_dispute"> 面积争议： </label>
            <div class="col-sm-9 radio">
                @foreach($sdata['detailmodel']->area_dispute as $key => $value)
                    @if($key==0||$key==1)
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
            <label class="col-sm-3 control-label no-padding-right" for="status"> 状态： </label>
            <div class="col-sm-9 radio">
                @foreach($sdata['detailmodel']->status as $key => $value)
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
                @foreach($sdata['detailmodel']->has_assets as $key => $value)
                    <label>
                        <input name="has_assets" type="radio" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="agree"> 征收意见： </label>
            <div class="col-sm-9 radio">
                @foreach($sdata['detailmodel']->agree as $key => $value)
                    <label>
                        <input name="agree" type="radio" class="ace" value="{{$key}}" @if($key==1) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="repay_way"> 补偿方式： </label>
            <div class="col-sm-9 radio">
                @foreach($sdata['detailmodel']->repay_way as $key => $value)
                    <label>
                        <input name="repay_way" type="radio" onclick="repayway(this)" class="ace" value="{{$key}}" @if($key==0) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="house_price"> 产权调换意向-房源单价： </label>
            <div class="col-sm-9">
                <input type="text" id="house_price" name="house_price" value="{{old('house_price')}}" class="col-xs-10 col-sm-5"  placeholder="请输入房源单价" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="house_area"> 产权调换意向-房源面积： </label>
            <div class="col-sm-9">
                <input type="text" id="house_area" name="house_area" value="{{old('house_area')}}" class="col-xs-10 col-sm-5"  placeholder="请输入房源面积" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="house_num"> 产权调换意向-房源数量： </label>
            <div class="col-sm-9">
                <input type="text" id="house_num" name="house_num" value="{{old('house_num')}}" class="col-xs-10 col-sm-5"  placeholder="请输入房源数量" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="house_addr"> 产权调换意向-房源地址： </label>
            <div class="col-sm-9">
                <input type="text" id="house_addr" name="house_addr" value="{{old('house_addr')}}" class="col-xs-10 col-sm-5"  placeholder="请输入房源地址" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="more_price"> 产权调换意向-增加面积单价： </label>
            <div class="col-sm-9">
                <input type="text" id="more_price" name="more_price" value="{{old('more_price')}}" class="col-xs-10 col-sm-5"  placeholder="请输入增加面积单价" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group repayways" style="display: none;">
            <label class="col-sm-3 control-label no-padding-right" for="layout_id"> 产权调换意向-房源户型： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="layout_id" id="layout_id">
                    <option value="0">--请选择--</option>
                    @foreach($sdata['layout'] as $layout)
                        <option value="{{$layout->id}}">{{$layout->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="opinion">其他意见：</label>
            <div class="col-sm-9">
                <textarea id="opinion" name="opinion" class="col-xs-10 col-sm-5" placeholder="请输入其他意见" >{{old('opinion')}}</textarea>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="receive_man"> 收件人： </label>
            <div class="col-sm-9">
                <input type="text" id="receive_man" name="receive_man" value="{{old('receive_man')}}" class="col-xs-10 col-sm-5"  placeholder="请输入收件人" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="receive_tel"> 收件电话： </label>
            <div class="col-sm-9">
                <input type="text" id="receive_tel" name="receive_tel" value="{{old('receive_tel')}}" class="col-xs-10 col-sm-5"  placeholder="请输入收件电话" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="receive_addr"> 收件地址： </label>
            <div class="col-sm-9">
                <input type="text" id="receive_addr" name="receive_addr" value="{{old('receive_addr')}}" class="col-xs-10 col-sm-5"  placeholder="请输入收件地址" required>
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
                                        <input type="file" accept="image/*" class="hidden" data-name="picture[{{$filecate->filename}}][]" multiple onchange="uplfile(this)">
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
        function repayway(obj) {
            var _repayway = $(obj).val();
            if(_repayway==1){
                $('.repayways').css('display','block');
            }else{
                $('.repayways').css('display','none');
                $('#house_price').val('');
                $('#house_area').val('');
                $('#house_num').val('');
                $('#house_addr').val('');
                $('#more_price').val('');
                var layout = ' <option value="0">--请选择--</option>';
                @foreach($sdata['layout'] as $layout)
                    layout+='<option value="{{$layout->id}}">{{$layout->name}}</option>';
                 @endforeach
                $('#layout_id').html(layout);

            }
        }
    </script>

@endsection