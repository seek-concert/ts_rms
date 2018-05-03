{{-- 继承主体 --}}
@extends('gov.main')

{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a class="btn" href="{{route('g_buildingrelated',['item'=>$edata['item']->id,'household_id'=>$edata['household_id']])}}">
            <i class="ace-icon fa fa-arrow-left bigger-110"></i>
            返回
        </a>
    </div>
    <form class="form-horizontal" role="form" action="{{route('g_buildingrelated_com')}}" method="post">
        {{csrf_field()}}
        <input type="hidden" name="household_building_id" value="{{$edata['household_building_id']}}">
        <input type="hidden" name="household_id" value="{{$edata['household_id']}}">
        <input type="hidden" name="item" value="{{$edata['item_id']}}">
        <table class="table table-hover table-bordered">
            <thead>
            <tr>
                <th style="text-align: center">请勾选</th>
                <th>评估机构</th>
                <th>名称</th>
                <th>地块</th>
                <th>楼栋</th>
                <th>楼层</th>
                <th>朝向</th>
                <th>结构</th>
                <th>实际建筑面积</th>
                <th>实际用途</th>
                <th>状态</th>
            </tr>
            </thead>
            <tbody>
            @if($code=='success')
                @foreach($sdata as $infos)
                    <tr>
                        <td style="text-align: center"><input type="radio" name="id" value="{{$infos->id}}"></td>
                        <td>{{$infos->company->name}}</td>
                        <td>{{$infos->name}}</td>
                        <td>{{$infos->itemland->address}}</td>
                        <td>{{$infos->itembuilding->building}}</td>
                        <td>{{$infos->floor}}</td>
                        <td>{{$infos->direct}}</td>
                        <td>{{$infos->buildingstruct->name}}</td>
                        <td>{{$infos->real_outer}}</td>
                        <td>{{$infos->realbuildinguse->name}}</td>
                        <td>{{$infos->state->name}}</td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>

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

@endsection

{{-- 插件 --}}
@section('js')
    @parent
    <script src="{{asset('js/func.js')}}"></script>
    <script>
        function sub_ajax(obj) {
            var _this = $('input[name=id]:checked').val();
            if(!_this){
                toastr.error('请先勾选建筑信息');
                return false;
            }
           sub(obj);
        }
    </script>

@endsection