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


    <form class="form-horizontal" role="form" action="{{route('g_houselayoutimg_add')}}" method="post">
        {{csrf_field()}}
        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="community_id"> 房源社区： </label>
            <div class="col-sm-9">
                <select class="col-xs-5 col-sm-5" name="community_id" id="community_id">
                    <option value="0">--请选择--</option>
                    @foreach($sdata['housecommunity'] as $community)
                        <option value="{{$community->id}}">{{$community->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="layout_id"> 房源户型： </label>
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
            <label class="col-sm-3 control-label no-padding-right" for="name"> 名称： </label>
            <div class="col-sm-9">
                <input type="text" id="name" name="name" value="{{old('name')}}" class="col-xs-10 col-sm-5"  placeholder="请输入名称" required>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="widget-body">
            <div class="widget-main padding-8">

                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        户型图：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="picture[]" onchange="uplfile(this)">
                        </span>
                    </label>

                    <div class="col-sm-9">
                         <span class="btn btn-xs">
                              <span onclick="open_gpy(this,1)" id="saomiao_id">扫描图片</span>
                          </span>
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
    <script src="{{asset('layer/layer.js')}}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        /*===== 打开高拍仪=====*/
        function open_gpy(obj,type) {
            var name = $('input[type="file"]').data('name')?$('input[type="file"]').data('name'):'picture';
            var gpyid = $(obj).attr('id');
            var url = '{{route('g_gaopaiyi')}}?smid='+gpyid+'&type='+type+'&name='+name;
            var width=arguments[2]?arguments[2]:800,
                height=arguments[3]?arguments[3]:450,
                is_full=arguments[4]?arguments[4]:false;
            if(window.screen.width<1080 && width>=800){
                width=750;
            }
            if(window.screen.height<1080 && height>450){
                height=450;
            }
            var index=layer.open({
                type: 2,
                skin: 'new-layer',
                title: '高拍仪管理',
                shadeClose: false,
                shade: 0.5,
                maxmin: true, //开启最大化最小化按钮
                area: [width+'px', height+'px'],
                content: [url,'yes'], //iframe的url，no代表不显示滚动条
                yes: function(index,layero){
                    layer.close(index);
                }
            });
        }
        /*===== 高拍仪返回图片地址=====*/
        function saomiao_img(smid,img_url,name,type,img_type) {
            if(type==1){
                /*===== 单图 =====*/
                $('.img-content').html(
                    '<li>'+
                        '<div>'+
                            '<img width="120" height="120" src="'+img_url+'" alt="加载失败">'+
                            '<input type="hidden" name="'+name+'" value="'+img_url+'">'+
                            '<div class="text">'+
                                '<div class="inner">'+
                                    '<a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>'+
                                    '<a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</li>');
            }else{
                /*===== 多图 =====*/
                if(img_type==2){
                    /*---- 多图 扫描缩略图 --*/
                    if (img_url.length > 0) img_url = img_url.substr(0, img_url.length - 2);
                    var img_arr = img_url.split('##');
                    $.each(img_arr,function (index,info) {
                        var img = info.replace('fieldname=','');
                        $('.img-content').append(
                            '<li>'+
                                '<div>'+
                                    '<img width="120" height="120" src="'+img_url+'" alt="加载失败">'+
                                    '<input type="hidden" name="'+name+'" value="'+img_url+'">'+
                                    '<div class="text">'+
                                        '<div class="inner">'+
                                            '<a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>'+
                                            '<a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>'+
                                        '</div>'+
                                    '</div>'+
                                '</div>'+
                            '</li>');
                    })
                }else{
                    /*---- 多图 扫描上传 --*/
                    $('.img-content').append(
                        '<li>'+
                            '<div>'+
                                '<img width="120" height="120" src="'+img_url+'" alt="加载失败">'+
                                '<input type="hidden" name="'+name+'" value="'+img_url+'">'+
                                '<div class="text">'+
                                    '<div class="inner">'+
                                        '<a onclick="preview(this)"><i class="fa fa-search-plus"></i></a>'+
                                        '<a onclick="removeimg(this)"><i class="fa fa-trash"></i></a>'+
                                    '</div>'+
                                '</div>'+
                            '</div>'+
                        '</li>');
                }
            }
            $('.img-content').viewer('update');
            layer.closeAll();
        }
    </script>

@endsection