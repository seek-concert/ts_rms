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


    <form class="form-horizontal" role="form" action="{{route('g_householdright_edit')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="id" value="{{$sdata['id']}}">
        <input type="hidden" name="item" value="{{$sdata['item_id']}}">
        <input type="hidden" name="household_id" value="{{$sdata['household']->id}}">
        <input type="hidden" name="right_id" value="{{$sdata['right_info']->id}}">

        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th>序号</th>
                <th>姓名</th>
                <th>身份证</th>
                <th>权属类型</th>
                <th>权属分配比例</th>
            </tr>
            </thead>
            <tbody>
            @if($code=='success')
                @foreach($sdata['member'] as $infos)
                    <tr>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$infos->name}}</td>
                        <td>{{$infos->card_num}}</td>
                        <td>
                            @foreach($sdata['membermodel']->holder as $key=>$val)
                                <label>
                                    <input name="holder[{{$infos->id}}]" type="radio" class="ace" value="{{$key}}"  @if($key==$infos->getOriginal('holder')) checked @endif>
                                    <span class="lbl">{{$val}}</span>
                                </label>
                            @endforeach
                        </td>
                        <td><input type="number" name="portion[{{$infos->id}}]" value="{{$infos->portion}}" >%</td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>

        <div class="form-group">
            <label class="col-sm-3 control-label no-padding-right" for="way">解决方式：</label>
            <div class="col-sm-9">
                <textarea id="way" name="way" class="col-xs-10 col-sm-5" placeholder="请输入解决方式" >{{$sdata['right_info']->way}}</textarea>
            </div>
        </div>
        <div class="space-4"></div>

        <div class="widget-body">
            <div class="widget-main padding-8">
                <div class="form-group img-box">
                    <label class="col-sm-3 control-label no-padding-right">
                        证明：<br>
                        <span class="btn btn-xs">
                            <span>上传图片</span>
                            <input type="file" accept="image/*" class="hidden" data-name="picture[]" multiple  onchange="uplfile(this)">
                        </span>
                    </label>
                    <div class="col-sm-9">
                        <ul class="ace-thumbnails clearfix img-content">
                            @if($sdata['right_info']->picture)
                                @foreach($sdata['right_info']->picture as $pic)
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
    </script>

@endsection