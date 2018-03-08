<?php
/*
|--------------------------------------------------------------------------
| 必备附件分类-数据表
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\system;
use App\Http\Model\Filetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FiletableController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $filetables=Filetable::sharedLock()->get();
            if(blank($filetables)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$filetables;
            $edata=null;
            $url=null;
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('system.filetable.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        if($request->isMethod('get')){
            $result=['code'=>'success','message'=>'请求成功','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('system.filetable.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'name' => 'required|unique:a_file_table',
            ];
            $messages = [
                'required' => ':attribute 为必须项',
                'unique' => ':attribute 已存在',
            ];

            $model=new Filetable();
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 批量赋值 ++++++++++ */
                $filetable = $model;
                $filetable->fill($request->input());
                $filetable->addOther($request);
                $filetable->save();
                if (blank($filetable)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $filetable;
                $edata = null;
                $url = route('sys_filetable');
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = $filetable;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 修改 ========== */
    public function edit(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('system.error')->with($result);
            }
        }

        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $filetable=Filetable::withTrashed()->sharedLock()->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($filetable)){
                $code='error';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='system.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=$filetable;
                $edata=new Filetable();
                $url=null;

                $view='system.filetable.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Filetable();
            /* ********** 表单验证 ********** */
            $rules=[
                'name'=>'required|unique:a_file_table,name,'.$id.',id',
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'unique'=>':attribute 已存在',
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $filetable=Filetable::withTrashed()->lockForUpdate()->find($id);
                if(blank($filetable)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $filetable->fill($request->input());
                $filetable->editOther($request);
                $filetable->save();
                if(blank($filetable)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='保存成功';
                $sdata=$filetable;
                $edata=null;
                $url=route('sys_filetable');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$filetable;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }
}