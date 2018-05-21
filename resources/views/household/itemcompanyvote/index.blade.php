{{-- 继承aceAdmin后台布局 --}}
@extends('household.layout')


{{-- 页面内容 --}}
@section('content')

    <div class="row">
        <div class="col-xs-12">
            @if($code=='success')
                @if(filled($sdata['companyvote']))
                <div class="col-xs-6 col-sm-3 pricing-box">
                    <div class="widget-box widget-color-blue">
                        <div class="widget-header">
                            <h5 class="widget-title bigger lighter">我的投票</h5>
                        </div>

                        <div class="widget-body">
                            <div class="widget-main">

                                <div class="profile-user-info profile-user-info-striped">

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 评估机构： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['companyvote']->company->name}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 基本信息： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['companyvote']->company->infos}}</span>
                                        </div>
                                    </div>

                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> 目前得票： </div>
                                        <div class="profile-info-value">
                                            <span class="editable editable-click">{{$sdata['companyvote']->company->companyvotes_count}}</span>
                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-name"> LOGO： </div>

                                        <div class="profile-info-value img-content">
                                            @if($sdata['companyvote']->company->logo)
                                                <img width="120" height="120" src="{{$sdata['companyvote']->company->logo}}" alt="加载失败">
                                            @else
                                                暂无
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div >
                                <a href="{{route('h_company_info',['id'=>$sdata['companyvote']->company->id])}}" class="btn btn-block btn-primary" style="font-size: 15px">查看详情 <i class="ace-icon fa fa-chevron-circle-right bigger-110"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @foreach($sdata['companys'] as $company)
                    <div class="col-xs-6 col-sm-3 pricing-box">
                        <div class="widget-box widget-color-green">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">{{$company->name}}</h5>
                            </div>

                            <div class="widget-body">
                                <div class="widget-main">

                                    <div class="profile-user-info profile-user-info-striped">

                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> 基本信息： </div>
                                            <div class="profile-info-value">
                                                <span class="editable editable-click">{{$company->infos}}</span>
                                            </div>
                                        </div>

                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> 目前得票： </div>
                                            <div class="profile-info-value">
                                                <span class="editable editable-click">{{$company->companyvotes_count}}</span>
                                            </div>
                                        </div>

                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> LOGO： </div>

                                            <div class="profile-info-value img-content">
                                                @if($company->logo)
                                                    <img width="120" height="120" src="{{$company->logo}}" alt="加载失败">
                                                    @else
                                                    暂无
                                                    @endif
                                             </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="price">
                                        <a href="{{route('h_company_info',['id'=>$company->id])}}" style="font-size: 15px">查看详情 <i class="ace-icon fa fa-chevron-circle-right bigger-110"></i>
                                        </a>
                                    </div>

                                </div>
                                <div >
                                    @if(filled($sdata['companyvote']) && $sdata['companyvote']->company_id == $company->id)
                                        <a href="javascript:;" class="btn btn-block btn-success" >
                                            <span>已投</span>
                                        </a>
                                    @else
                                        <a href="javascript:;" onclick="vote({{$company->id}})" class="btn btn-block btn-inverse" >
                                            <span>投票</span>
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else

                <div class="alert alert-warning">
                    <button type="button" class="close" data-dismiss="alert">
                        <i class="ace-icon fa fa-times"></i>
                    </button>
                    <strong>
                        <i class="ace-icon fa fa-exclamation-circle"></i>
                    </strong>
                    <strong class="resp-error">{{$message}}</strong>

                    <br>
                </div>

            @endif
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
       function vote(company_id) {
          var data={company_id:company_id};
           ajaxAct('{{route('h_itemcompanyvote_add')}}',data,'post');
           console.log( ajaxResp);
           if(ajaxResp.code=='success'){
               toastr.success(ajaxResp.message);
               setTimeout(function () {
                   location.href=ajaxResp.url;
               },1000);
           }else{
               toastr.error(ajaxResp.message);
           }
           return false;
       }
       $('.img-content').viewer();
    </script>
@endsection