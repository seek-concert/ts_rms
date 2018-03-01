{{-- 继承aceAdmin登录布局 --}}
@extends('ace_login')


{{-- 标题 --}}
@section('title')
    评估机构端
@endsection


{{-- 客户端 --}}
@section('client')
    评估机构端
@endsection


{{-- 登录地址 --}}
@section('login_url')

    {{route('c_login')}}

@endsection


{{-- 更多表单 --}}
@section('more_field')

@endsection


{{-- 样式 --}}
@section('css')


@endsection


{{-- 插件 --}}
@section('js')
    @parent

    <script>
        function login(obj) {
            ajaxFormSub(obj);
            if(ajaxResp.code=='success'){
                toastr.success(ajaxResp.message);
                setTimeout(function () {
                    location.href=ajaxResp.url;
                },1000);
            }else{
                toastr.error(ajaxResp.message);
                $('#username').focus();
            }
            return false;
        }

        $(document).on('keydown',function (e) {
            if (!e) e = window.event;
            if ((e.keyCode || e.which) == 13) {
                $('#btn-login').click();
            }
        });
    </script>

@endsection