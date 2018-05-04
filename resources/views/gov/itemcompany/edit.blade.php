{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')

    <p>
        <a class="btn" href="javascript:history.back()">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
        <span class="btn" data-toggle="modal"  data-target="#myModal">
            <i class="ace-icon fa fa-search bigger-110"></i>
            查询被征收户
        </span>
        <button class="btn btn-info" type="button" id="btn-choose-household">
            <i class="ace-icon fa fa-check bigger-110"></i>
            保存
        </button>
    </p>

    <div class="widget-box widget-color-green2">
        <div class="widget-header">
            <h4 class="widget-title lighter smaller">当前选择：</h4>
            <div class="widget-toolbar">
                <a href="#" data-action="collapse">
                    <i class="ace-icon fa fa-chevron-up"></i>
                    展开/关闭
                </a>
            </div>
        </div>
        <div class="widget-body">
            <div class="widget-main padding-8">
                <form class="form-horizontal" role="form" action="{{route('g_itemcompany_edit',['item'=>$sdata['item_id']])}}" method="post" id="form-choose-household">
                    {{csrf_field()}}
                    <input type="hidden" name="id" id="id" value="{{$sdata['itemcompany']->id}}">

                    <div class="form-group">
                        <label class="col-sm-3 control-label no-padding-right" for="company_id"> 评估机构： </label>
                        <div class="col-sm-9">
                            <input type="text"  class="col-xs-5 col-sm-5" id="company_id" value="{{$sdata['itemcompany']->company->name}}" readonly>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label no-padding-right" for="company_id"> 当前选择的被征收户： </label>
                        <div class="col-sm-9"></div>
                    </div>
                    <div class="space-4"></div>
                    <table class="table table-hover table-bordered">
                        <thead>
                        <tr>
                            <th>地址</th>
                            <th>房号</th>
                            <th>用户名</th>
                            <th>资产</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="choose-household">
                        @php $household_ids=[]; @endphp
                        @foreach($sdata['itemcompany']->households as $info)
                            @php $household_ids[]=$info->household_id; @endphp
                            <tr id="household-{{$info->household_id}}">
                                <td><input type="hidden" name="household_ids[]" value="{{$info->household_id}}">
                                    {{$info->household->itemland->address}}</td>
                                <td>{{$info->household->itembuilding->building?$info->household->itembuilding->building.'栋':''}}{{$info->household->unit?$info->household->unit.'单元':''}}{{$info->household->floor?$info->household->floor.'楼':''}}{{$info->household->number?$info->household->number.'号':''}}</td>
                                <td>{{$info->household->username}}</td>
                                <td>{{$info->household->householddetail->has_assets}}</td>
                                <td><a class="btn btn-sm" onclick="removeHousehold({{$info->household_id}})">删除</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    {{--查询被征收户--}}
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel"><i class="ace-icon fa fa-search bigger-110"></i> 查询被征收户</h4>
                </div>
                <div class="modal-body">
                    <div class="well">
                        <form action="{{route('g_householddetail',['item'=>$sdata['item_id']])}}" role="form" method="get" class="form-inline" id="form-search-household">
                            {{csrf_field()}}
                            <input type="hidden" name="page" value="1" id="current-page">
                            <input type="hidden" name="company_household" value="1">
                            <input type="hidden" name="types" value="{{$sdata['itemcompany']->getOriginal('type')}}">
                            @if($sdata['itemcompany']->getOriginal('type'))
                            <input type="hidden" name="has_assets" value="{{$sdata['itemcompany']->getOriginal('type')}}">
                            @endif
                            <div class="form-group">
                                <label for="land_id">地址：</label>
                                <select class="form-control" name="land_id" id="land_id">
                                    <option value="">--全部--</option>
                                    @if(filled($sdata['itemlands']))
                                    @foreach($sdata['itemlands'] as $itemland)
                                        <option value="{{$itemland->id}}">{{$itemland->address}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" id="btn-search-household">查询</button>
                        </form>
                    </div>
                    <table class="table table-hover table-bordered" id="table-search-household">
                        <thead>
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>地址</th>
                            <th>房号</th>
                            <th>用户名</th>
                            <th>资产</th>
                        </tr>
                        </thead>
                        <tbody id="search-household">

                        </tbody>
                    </table>
                    <div id="pagebar">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">确定</button>
                </div>
            </div>
        </div>
    </div>
    {{--删除确认--}}
    <div class="modal fade" id="delModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
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
                    当前被征收户存在评估机构摸底数据，你确定要清除评估机构数据吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary del_ok">确定</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
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
    <script src="{{asset('pagination/jquery.pagination.min.js')}}"></script>
    <script>
        // 数据保存
        $('#btn-choose-household').on('click',function () {
            var btn=$(this);
            var form=$('#form-choose-household');
            if(btn.data('loading')||btn.hasClass('disabled')){
                return false;
            }
            btn.data('loading',true).addClass('disabled');
            toastr.info('请稍等！处理中……');
            ajaxAct(form.attr('action'),form.serialize(),'post');
            if(ajaxResp.code=='success'){
                toastr.success(ajaxResp.message);
                setTimeout(function () {
                    location.href=ajaxResp.url;
                },1000);
            }else{
                toastr.error(ajaxResp.message);
                btn.data('loading',false).removeClass('disabled');
            }
            return false;
        });
        // 查询被征收户
        $('#btn-search-household').on('click',function () {
            var btn=$(this);
            var form=$('#form-search-household');
            if(btn.data('loading')||btn.hasClass('disabled')){
                return false;
            }
            btn.data('loading',true).addClass('disabled');
            toastr.info('请稍等！查询中……');
            $('#current-page').val(1);
            getHousehold(form);
            btn.data('loading',false).removeClass('disabled');
        });
        // 选择被征收户
        var choose_household_ids=@json($household_ids);
        var choose_households=[];
        $('#table-search-household').on('change',function () {
            var checkboxes=$(this).find('tbody').find('input[type="checkbox"]');
            if(checkboxes && checkboxes.length){
                var tr='';
                $.each(checkboxes,function (index,obj) {
                    var checkbox=$(obj);
                    var household_id=parseInt(checkbox.val());
                    var pos=$.inArray(household_id,choose_household_ids);
                    if(checkbox.prop('checked')){
                        if(pos == -1){
                            var info=ajaxResp.sdata.data[index];
                            choose_household_ids.push(household_id);
                            choose_households.push(info);
                            var building = info.itembuilding.building?info.itembuilding.building +'栋':'';
                            var unit = info.household.unit?info.household.unit+'单元':'';
                            var floor = info.household.floor?info.household.floor+'楼':'';
                            var number = info.household.number?info.household.number +'号':'';

                            tr += ' <tr id="household-'+info.household_id+'">' +
                                '<td><input type="hidden" name="household_ids[]" value="'+info.household_id+'">' + info.itemland.address + '</td>' +
                                '<td>' + building +unit+floor+number+ '</td>' +
                                '<td>' + info.household.username + '</td>' +
                                '<td>' + info.has_assets + '</td>' +
                                '<td><a class="btn btn-sm" onclick="removeHousehold('+household_id+')">删除</a></td>' +
                                '</tr>';
                        }
                    }else{
                        if(pos>-1){
                            removeHousehold(household_id);
                        }
                    }

                });
                $('#choose-household').append(tr);
            }
        });
        // 删除选择
        function removeHousehold(household_id) {
            var pos=$.inArray(household_id,choose_household_ids);
            var type = '{{$sdata['itemcompany']->getOriginal('type')}}';
            if(pos>-1){
                ajaxAct('{{route('g_search_com_data')}}',{item:'{{$sdata['item_id']}}',household_id:household_id,type:type},'get');
                if(ajaxResp.code=='success') {
                    choose_household_ids.splice(pos, 1);
                    choose_households.splice(pos, 1);
                    $('#household-' + household_id).remove();
                    toastr.success(ajaxResp.message);
                }else{
                    $('#delModal').modal('show');
                    $("#del_id").val(household_id);
                    toastr.error(ajaxResp.message);
                }
            }
        }
        //删除确认
        $('.del_ok').on('click',function() {
            var del_id = $('#del_id').val();
            var type = '{{$sdata['itemcompany']->getOriginal('type')}}';
            var company_id = '{{$sdata['itemcompany']->company_id}}';
            ajaxAct('{{route('g_del_com_data')}}',{item:'{{$sdata['item_id']}}',household_id:del_id,type:type,company_id:company_id},'get');
            if(ajaxResp.code=='success'){
                var pos=$.inArray(parseInt(del_id),choose_household_ids);
                if(pos>-1) {
                    choose_household_ids.splice(pos, 1);
                    choose_households.splice(pos, 1);
                    $('#household-' + del_id).remove();
                }
                toastr.success(ajaxResp.message);
                $('#delModal').modal('hide');
            }else{
                toastr.error(ajaxResp.message);
            }
        });
        // 获取被征收户
        function getHousehold(form) {
            ajaxAct(form.attr('action'),form.serialize(),'get');
            var tr='';
            if(ajaxResp.code=='success'){
                toastr.success('获取到 '+ajaxResp.sdata.data.length+' 条数据');
                $.each(ajaxResp.sdata.data,function (index,info) {

                    var checked='';
                    if($.inArray(info.household_id,choose_household_ids)>-1){
                        checked='checked';
                    }
                    var building = info.itembuilding.building?info.itembuilding.building +'栋':'';
                    var unit = info.household.unit?info.household.unit+'单元':'';
                    var floor = info.household.floor?info.household.floor+'楼':'';
                    var number = info.household.number?info.household.number +'号':'';
                    tr += ' <tr>' +
                        '<td><input type="checkbox" value="' + info.household_id + '" '+checked+'></td>' +
                        '<td>' + info.itemland.address + '</td>' +
                        '<td>' + building+unit+floor+number+ '</td>' +
                        '<td>' + info.household.username + '</td>' +
                        '<td>' + info.has_assets + '</td>' +
                        '</tr>';
                });
                $('#pagebar').pagination({
                    totalData:ajaxResp.sdata.total,
                    showData:ajaxResp.sdata.per_page,
                    current:ajaxResp.sdata.current_page,
                    jump:false,
                    coping:true,
                    homePage:'首页',
                    endPage:'末页',
                    prevContent:'上一页',
                    nextContent:'下一页',
                    callback:function(api){
                        var cur=api.getCurrent();
                        $('#current-page').val(cur);
                        getHousehold(form);
                    }
                },function(api){
                    var cur=api.getCurrent();
                    $('#current-page').val(cur);
                });
            }else{
                toastr.error(ajaxResp.message);
                $('#pagebar').html('');
                $('#current-page').val(1);
            }
            $('#search-household').html(tr);
        }

    </script>

@endsection