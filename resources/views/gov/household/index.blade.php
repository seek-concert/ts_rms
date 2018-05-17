{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="{{route('g_household_add',['item'=>$edata['item_id']])}}" class="btn">添加被征收户账号</a>
        <form action="{{route('g_household_export',['item'=>$edata['item_id']])}}" method="post" class="btn" onclick="export_household('all')">
            {{csrf_field()}}
            <input type="hidden" name="ids" id="ids" value="">
            <span>导出被征户信息</span>
        </form>
        <form action="{{route('g_household_import',['item'=>$edata['item_id']])}}" method="post" class="btn" data-toggle="modal" data-target="#import_household">
            {{csrf_field()}}
            <input type="hidden" name="ids" id="ids" value="">
            <span>导入被征户信息</span>
        </form>
    </div>

    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th><input type="checkbox"></th>
            <th>序号</th>
            <th>地块</th>
            <th>楼栋</th>
            <th>位置</th>
            <th>房产类型</th>
            <th>用户名</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
            @if($code=='success')
                @foreach($sdata as $infos)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{$infos->id}}"></td>
                        <td>{{$loop->iteration}}</td>
                        <td>{{$infos->itemland->address}}</td>
                        <td>{{$infos->itembuilding->building}}</td>
                        <td>{{$infos->unit?$infos->unit.'单元':''}}{{$infos->floor?$infos->floor.'楼':''}}{{$infos->number?$infos->number.'号':''}}</td>
                        <td>{{$infos->type}}</td>
                        <td>{{$infos->username}}</td>
                        <td>{{$infos->state->name}}</td>
                        <td>
                            <a href="{{route('g_householddetail_info',['id'=>$infos->id,'item'=>$infos->item_id])}}" class="btn btn-sm">查看详情</a>
                            <a class="btn btn-sm" data-toggle="modal" onclick="del_data('{{$infos->id}}')" data-target="#myModal">删除</a>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    <div class="row">
        <div class="col-xs-6">
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">共 @if($code=='success') {{ $sdata->total() }} @else 0 @endif 条数据</div>
        </div>
        <div class="col-xs-6">
            <div class="dataTables_paginate paging_simple_numbers" id="dynamic-table_paginate">
                @if($code=='success') {{ $sdata->links() }} @endif
            </div>
        </div>
    </div>

    {{--删除确认弹窗--}}
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">删除确认</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="del_id" value="">
                    你确定要删除本条数据吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary del_ok">确定</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                </div>
            </div>
        </div>
    </div>

    {{--导出数据弹窗--}}
    <div class="modal fade" id="import_household" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">被征户导入</h4>
                </div>
                <div class="modal-body">
                    <span style="color: red;">温馨提示：请先下载【被征户信息导入的模板】,模板文件已设置好数据格式，最好在模板上直接修改后上传，然后导入！</span>
                    <form action="{{route('g_household_import_demo',['item'=>$edata['item_id']])}}" method="post" class="btn btn-xs household_import_demo">
                        {{csrf_field()}}
                        <span id="household_import_demo">下载被征户信息导入模板</span>
                    </form>
                    <br/>
                    <br/>
                    <div class="form-group" >
                        <span class="col-sm-3">被征户导入：</span>
                        <form action="{{route('g_household_import',['item'=>$edata['item_id']])}}" enctype="multipart/form-data" method="post" class="col-sm-6 control-label no-padding-right">
                            {{csrf_field()}}
                            <input type="file"  name="myfile" class="myfile" id="household_import">
                        </form>
                    </div>
                    <br/>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary import_ok">确定导入</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- 样式 --}}
@section('css')

@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script>
        /*---------弹出删除确认----------*/
        function del_data(id) {
            $('#del_id').val(id);
        }
        /*---------确认删除----------*/
        $('.del_ok').on('click',function(){
            $('#myModal').modal('hide');
            var del_id = $('#del_id').val();
            if(!del_id){
                toastr.error('请选择要删除的数据！');
                return false;
            }
            ajaxAct('{{route('g_household_del')}}',{ id:del_id,item:'{{$edata['item_id']}}'},'post');
            if(ajaxResp.code=='success'){
                toastr.success(ajaxResp.message);
                if(ajaxResp.url){
                    setTimeout(function () {
                        location.href=ajaxResp.url;
                    },1000);
                }else{
                    setTimeout(function () {
                        location.reload();
                    },1000);
                }
            }else{
                toastr.error(ajaxResp.message);
            }
            return false;
        });

        /*--------- 被征户信息导出 ----------*/
        function export_household(rs) {
            var ids = "";
            if (rs == "all") {
                var checkedlist = $("input[name=\"ids[]\"]:checked");
                for (var i = 0; i < checkedlist.length; i++) {
                    ids += $(checkedlist[i]).val();
                    if (i < checkedlist.length - 1) ids += ",";
                }
            }else {
                ids = rs;
            }
            if (ids == "") {
                toastr.error('请勾选要导出的被征户!', { icon: 1, time: 1000 });
                return false;
            }
            $("#ids").val(ids);
            $("#ids").parent('form').submit();
        }

        /*--------- 下载[被征户导入模板] ----------*/
        $('.household_import_demo').on('click',function () {
            $('#household_import_demo').parent('form').submit();
        });

        /*--------- 被征户信息导入 ----------*/
        $('.import_ok').on('click',function () {
            var _file = $('#household_import').val();
            if(!_file){
                toastr.error('请选择文件!', { icon: 1, time: 1000 });
                return false;
            }
            var reg = /^.*\.(?:xls|xlsx)$/i;//文件名可以带空格
            if (!reg.test(_file)) {//校验不通过
                toastr.error("请上传excel格式的文件!", { icon: 1, time: 1000 });
                return false;
            }
            $('#household_import').parent('form').submit();
        });
    </script>

@endsection