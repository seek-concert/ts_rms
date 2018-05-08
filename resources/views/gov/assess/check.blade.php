{{-- 继承布局 --}}
@extends('gov.main')


{{-- 页面内容 --}}
@section('content')
    <div class="well well-sm">
        <a href="javascript:history.back();" class="btn">返回</a>
    </div>
    <div class="widget-box widget-color-red">
        <div class="widget-header">
            <h4 class="widget-title lighter smaller">评估报告审查意见</h4>
        </div>

        <div class="widget-body">
            <div class="widget-main padding-8">
                <form class="form-horizontal" role="form" action="{{route('g_assess_check')}}" method="post">
                    {{csrf_field()}}
                    <input type="hidden" name="id" value="{{$sdata['assess']->id}}"/>
                    <input type="hidden" name="item" value="{{$sdata['item']->id}}"/>
                    <input type="hidden" name="household_id" value="{{$sdata['household']->id}}"/>
                    <div class="form-group">
                        <label class="col-sm-3 control-label no-padding-right" for="code">审查结果：</label>
                        <div class="col-sm-9 radio">
                            <label>
                                <input name="code" type="radio" class="ace" value="133" checked >
                                <span class="lbl">评估通过</span>
                            </label>

                            <label>
                                <input name="code" type="radio" class="ace" value="134" >
                                <span class="lbl">评估驳回</span>
                            </label>
                        </div>
                    </div>
                    <div class="space-4"></div>

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

@endsection