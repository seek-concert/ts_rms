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


    <form class="form-horizontal" role="form" action="{{route('g_house_edit')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="id" value="{{$sdata->id}}">

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="company_id"> 房源管理机构： </label>
            <div class="col-sm-9">
                <input type="text" class="col-xs-10 col-sm-5" id="company_id" value="{{$sdata->housecompany->name}}" readonly>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="community_id"> 房源社区： </label>
            <div class="col-sm-9">
                <input type="text" class="col-xs-10 col-sm-5" id="community_id" value="{{$sdata->housecommunity->name}}" readonly>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="layout_id"> 房源户型： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="layout_id" id="layout_id">
                    <option value="0">--请选择--</option>
                    @foreach($sdata['layout'] as $layout)
                        <option value="{{$layout->id}}" @if($layout->id == $sdata->layout_id) selected @endif>{{$layout->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right"> 房源户型图：<br/>
                <button type="button" class="btn layout_select" style="background-color: #ABBAC3!important;border-color: #ABBAC3;width: 100px;"
                        data-toggle="modal">
                    选择户型图
                </button>
            </label>
            <div class="col-sm-9">
                <ul class="ace-thumbnails clearfix img-content viewer imgs_layout">
                    <li>
                        <div>
                            <img width="120" height="120" src="{{$sdata->houselayoutimg->picture}}" alt="加载失败">
                            <input type="hidden" name="layout_img_id" value="{{ $sdata->layout_img_id }}">
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
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="building"> 楼栋： </label>
            <div class="col-sm-9">
                <input type="text" id="building" name="building" value="{{$sdata->building}}" class="col-xs-10 col-sm-5"  placeholder="请输入楼栋" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="unit"> 单元： </label>
            <div class="col-sm-9">
                <input type="number" id="unit" name="unit" value="{{$sdata->unit}}" class="col-xs-10 col-sm-5"  placeholder="请输入单元" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="floor"> 楼层： </label>
            <div class="col-sm-9">
                <input type="number" id="floor" name="floor" value="{{$sdata->floor}}" class="col-xs-10 col-sm-5"  placeholder="请输入楼层" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="number"> 房号： </label>
            <div class="col-sm-9">
                <input type="number" id="number" name="number" value="{{$sdata->number}}" class="col-xs-10 col-sm-5"  placeholder="请输入房号" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="area"> 面积： </label>
            <div class="col-sm-9">
                <input type="number" id="area" name="area" value="{{$sdata->area}}" class="col-xs-10 col-sm-5"  placeholder="请输入面积" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="total_floor"> 总楼层： </label>
            <div class="col-sm-9">
                <input type="number" id="total_floor" name="total_floor" value="{{$sdata->total_floor}}" class="col-xs-10 col-sm-5"  placeholder="请输入总楼层数" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="lift"> 是否电梯房： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->lift as $key => $value)
                    <label>
                        <input name="lift" type="radio" class="ace" value="{{$key}}" @if($key==$sdata->getOriginal('lift')) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="is_real"> 房源类型： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->is_real as $key => $value)
                    <label>
                        <input name="is_real" type="radio" class="ace" value="{{$key}}" @if($key==$sdata->getOriginal('is_real')) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="is_buy"> 购置状态： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->is_buy as $key => $value)
                    <label>
                        <input name="is_buy" type="radio" class="ace" value="{{$key}}" @if($key==$sdata->getOriginal('is_buy')) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="is_transit"> 可临时周转状况： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->is_transit as $key => $value)
                    <label>
                        <input name="is_transit" type="radio" class="ace" value="{{$key}}" @if($key==$sdata->getOriginal('is_transit')) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="is_public"> 可项目共享状况： </label>
            <div class="col-sm-9 radio">
                @foreach($edata->is_public as $key => $value)
                    <label>
                        <input name="is_public" type="radio" class="ace" value="{{$key}}" @if($key==$sdata->getOriginal('is_public')) checked @endif >
                        <span class="lbl">{{$value}}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="delive_at"> 交付时间： </label>
            <div class="col-sm-9">
                <input type="text" id="delive_at" name="delive_at" value="{{$sdata->delive_at}}" class="col-xs-10 col-sm-5 laydate"  placeholder="请输入交付时间" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="widget-body">
            <div class="widget-main padding-8">

                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        房源图片：<br>
                        <span class="btn btn-xs">
                                        <span>上传图片</span>
                                        <input type="file" accept="image/*" class="hidden" data-name="picture[]" multiple  onchange="uplfile(this)">
                                    </span>
                    </label>
                    <div class="col-sm-9">
                        <ul class="ace-thumbnails clearfix img-content viewer">
                            @if($sdata->picture)
                            @foreach($sdata->picture as $pic)
                                <li>
                                    <div>
                                        <img width="120" height="120" src="{!! $pic !!}" alt="加载失败">
                                        <input type="hidden" name="picture[]" value="{!! $pic !!}">
                                        <div class="text">
                                            <div class="inner">
                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>
                                                <a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                                @endif
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
    {{--户型图列表--}}
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">选择户型图</h4>
                </div>
                <div class="modal-body layout_img">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary layout_img_checked">确定</button>
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
    <script src="{{asset('laydate/laydate.js')}}"></script>
    <script src="{{asset('viewer/viewer.min.js')}}"></script>
    <script src="{{asset('js/func.js')}}"></script>
    <script>
        $('#name').focus();
        $('.img-content').viewer();
        /*---------清空户型图----------*/
        $('#layout_id').on('change',function () {
            $('.imgs_layout').html('');
        });

        /*---------获取户型图----------*/
        $(".layout_select").on('click',function(){
            var community_id = '{{$sdata->community_id}}';
            if(community_id==0||!community_id){
                $(this).attr('data-target','');
                toastr.error('请先选择房源社区');
                return false;
            }
            var layout_id = $('#layout_id').find('option:selected').val();
            if(layout_id==0||!layout_id){
                $(this).attr('data-target','');
                toastr.error('请先选择户型');
                return false;
            }

            var data = {
                'layout_id':layout_id,
                'community_id':community_id
            };
            ajaxAct('{{route('g_houselayoutimg')}}',data,'post');
            if(ajaxResp.code=='error'){
                $(".layout_img").html('');
                toastr.error(ajaxResp.message);
            }else{
                $(".layout_img").html('');
                var layoutimg = '';
                $.each(ajaxResp.sdata.data,function (index,info) {
                    layoutimg+=' <div class=" radio">\n' +
                        '                    <label>\n' +
                        '                        <input name="layoutimgs" type="radio" class="ace img_radio" value="'+info.id+'">\n' +
                        '                        <span class="lbl"><img width="400" height="300" src="'+info.picture+'"></span>\n' +
                        '                    </label>\n' +
                        '            </div>'
                });
                $(".layout_img").html(layoutimg);
            }
            $(this).attr('data-target','#myModal');
        });

        /*---------弹出户型图----------*/
        $(".layout_img_checked").on('click',function(){
            var imgs_id = $('input[name=layoutimgs]:checked').val();
            if(!imgs_id){
                toastr.error('请先选择户型图');
                return false;
            }
            var imgs_picture = $('input[name=layoutimgs]:checked').next('span').find('img').attr('src');
            $(".layout_img").html('');
            $('.imgs_layout').html(
                '   <li>\n' +
                '                                    <div>\n' +
                '                                        <img width="120" height="120" src="'+imgs_picture+'" alt="加载失败">\n' +
                '                                        <input type="hidden" name="layout_img_id" value="'+imgs_id+'">\n' +
                '                                        <div class="text">\n' +
                '                                            <div class="inner">\n' +
                '                                                <a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>\n' +
                '                                            </div>\n' +
                '                                        </div>\n' +
                '                                    </div>\n' +
                '                                </li>');
            $('.img-content').viewer('update');
            $('#myModal').modal('hide');
        })
    </script>

@endsection