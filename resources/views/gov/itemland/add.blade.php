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


    <form class="form-horizontal" role="form" action="{{route('g_itemland_add')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="item" value="{{$sdata['item']->id}}">
        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="address"> 地址： </label>
            <div class="col-sm-9">
                <input type="text" id="address" name="address" value="{{old('address')}}" class="col-xs-10 col-sm-5"  placeholder="请输入地址" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="land_prop_id"> 土地性质： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="land_prop_id" id="land_prop_id">
                    @if($sdata['landprops'])
                        @foreach($sdata['landprops'] as $landprop)
                            <option value="{{$landprop->id}}" data-index="{{$loop->index}}">{{$landprop->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="land_source_id"> 土地来源： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="land_source_id" id="land_source_id">
                    @if($sdata['landprops'])
                        @foreach($sdata['landprops'][0]->landsources as $landsource)
                            <option value="{{$landsource->id}}" data-index="{{$loop->index}}">{{$landsource->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="land_state_id"> 土地权益状况：</label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="land_state_id" id="land_state_id">
                    @if($sdata['landprops'])
                        @foreach($sdata['landprops'][0]->landsources[0]->landstates as $landstate)
                            <option value="{{$landstate->id}}" data-index="{{$loop->index}}">{{$landstate->name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="admin_unit_id"> 公房单位： </label>
            <div class="col-sm-9">
                <select class="col-xs-10 col-sm-5" name="admin_unit_id" id="admin_unit_id">
                    <option value="0">--请选择公房单位--</option>
                    @if(filled($sdata['adminunits']))
                        @foreach($sdata['adminunits'] as $adminunit)
                            <option value="{{$adminunit->id}}">--{{$adminunit->name}}--</option>
                        @endforeach
                    @endif
                </select>
                <span class="help-inline col-xs-12 col-sm-7">
                    <span class="middle red">注：不选择则为私产</span>
                </span>
            </div>

        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="area"> 占地面积： </label>
            <div class="col-sm-9">
                <input type="text" id="area" name="area" value="{{old('area')}}" class="col-xs-10 col-sm-5"  placeholder="请输入占地面积,填写数字或小数,小数精确到第二位" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="infos">备注：</label>
            <div class="col-sm-9">
                <textarea id="infos" name="infos" class="col-xs-10 col-sm-5" placeholder="请输入备注" >{{old('infos')}}</textarea>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="widget-body">
            <div class="widget-main padding-8">

                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        地块图片：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="gov_pic[]" multiple  onchange="uplfile(this)">
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
        var landprops=@json($sdata['landprops']);
        $('#land_prop_id').on('change',function () {
            var landprop_index=$(this).find('option:selected').data('index');
            var landsources=landprops[landprop_index].landsources;
            var landsources_opt='';
            $.each(landsources,function (index,infos) {
                landsources_opt +='<option value="'+infos.id+'" data-index="'+index+'" '+(index==0?'selected':'')+'>'+infos.name+'</option>';
            });
            $('#land_source_id').html(landsources_opt);
            var landstates_opt='';
            $.each(landsources[0].landstates,function (index,infos) {
                landstates_opt +='<option value="'+infos.id+'" data-index="'+index+'" '+(index==0?'selected':'')+'>'+infos.name+'</option>';
            });
            $('#land_state_id').html(landstates_opt);
        });
        $('#land_source_id').on('change',function () {
            var landprop_index=$('#land_prop_id').find('option:selected').data('index');
            var landsources=landprops[landprop_index].landsources;
            var landsource_index=$(this).find('option:selected').data('index');
            var landstates=landsources[landsource_index].landstates;
            var landstates_opt='';
            $.each(landstates,function (index,infos) {
                landstates_opt +='<option value="'+infos.id+'" data-index="'+index+'" '+(index==0?'selected':'')+'>'+infos.name+'</option>';
            });
            $('#land_state_id').html(landstates_opt);
        });
    </script>

@endsection