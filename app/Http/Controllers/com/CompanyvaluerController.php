<?php
/*
|--------------------------------------------------------------------------
| 评估机构-评估师
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Comassessvaluer;
use App\Http\Model\Company;
use App\Http\Model\Companyvaluer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompanyvaluerController extends BaseauthController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request,$next){
            if(session('com_user.isAdmin')==0){
                $result=['code'=>'error','message'=>'您没有操作权限','sdata'=>null,'edata'=>null,'url'=>null];
                if(request()->ajax()){
                    return response()->json($result);
                }else{
                    return redirect()->route('c_error')->with($result);
                }
            }
            return $next($request);
        });
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $select=['id','company_id','name','phone','register','valid_at','deleted_at'];

        /* ********** 查询条件 ********** */
        $where=[];
        /* ++++++++++ 评估机构 ++++++++++ */
        $company_id = session('com_user.company_id');
        $where[] = ['company_id',$company_id];
        $infos['company_id'] = $company_id;
        /* ++++++++++ 名称 ++++++++++ */
        $name=trim($request->input('name'));
        if($name){
            $where[]=['name','like','%'.$name.'%'];
            $infos['name']=$name;
        }
        /* ++++++++++ 电话 ++++++++++ */
        $phone=trim($request->input('phone'));
        if($phone){
            $where[]=['phone',$phone];
            $infos['phone']=$phone;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 是否删除 ********** */
        $deleted=$request->input('deleted');

        $model=new Companyvaluer();
        if(is_numeric($deleted) && in_array($deleted,[0,1])){
            $infos['deleted']=$deleted;
            if($deleted){
                $model=$model->onlyTrashed();
            }
        }
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $companyvaluers=$model
                ->with(['company'=>function($query){
                    $query->withTrashed()->select(['id','name','type']);
                }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->get();
            if(blank($companyvaluers)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$companyvaluers;;
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
            return view('com.companyvaluer.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $model=new Companyvaluer();
        if($request->isMethod('get')){
            $sdata['company'] = Company::withTrashed()->select(['id','type','name'])->get();
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('com.companyvaluer.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'company_id' => 'required',
                'name' => 'required',
                'register' => 'required|unique:company_valuer',
                'valid_at' => 'required'
            ];
            $messages = [
                'required' => ':attribute 为必须项',
                'unique' => ':attribute 已存在'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 批量赋值 ++++++++++ */
                /*--- 评估机构 ---*/
                $companyvaluer = $model;
                $companyvaluer->fill($request->input());
                $companyvaluer->company_id=session('com_user.company_id');
                $companyvaluer->save();
                if (blank($companyvaluer)) {
                    throw  new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $companyvaluer;
                $edata = null;
                $url = route('c_companyvaluer');
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('com.error')->with($result);
            }
        }
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $companyvaluer=Companyvaluer::withTrashed()
            ->with(['company'=>function($query){
                $query->withTrashed()->select(['id','name','type']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($companyvaluer)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$companyvaluer;
            $edata=new Companyvaluer();
            $url=null;

            $view='com.companyvaluer.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
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
                return view('com.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $companyvaluer=Companyvaluer::withTrashed()
                ->with(['company'=>function($query){
                    $query->withTrashed()->select(['id','name','type']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($companyvaluer)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=$companyvaluer;
                $edata=new Companyvaluer();
                $url=null;

                $view='com.companyvaluer.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Companyvaluer();
            /* ********** 表单验证 ********** */
            $rules=[
                'name'=>'required|unique:company_valuer,name,'.$id.',id',
                'register'=>'required',
                'valid_at'=>'required'
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'unique'=>':attribute 已存在'
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
                $companyvaluer=Companyvaluer::withTrashed()
                    ->lockForUpdate()
                    ->find($id);
                if(blank($companyvaluer)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $companyvaluer->fill($request->input());
                $companyvaluer->editOther($request);
                $companyvaluer->save();
                if(blank($companyvaluer)){
                    throw  new \Exception('修改失败',404404);
                }
    
                $code='success';
                $msg='修改成功';
                $sdata=$companyvaluer;
                $edata=null;
                $url=route('c_companyvaluer');
    
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$companyvaluer;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 删除 ========== */
    public function del(Request $request){
        $ids = $request->input('id');
        if(blank($ids)){
            $result=['code'=>'error','message'=>'请选择要删除的数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        /* ********** 删除数据 ********** */
        DB::beginTransaction();
        try{
            /*---------是否正在被使用----------*/
            if($ids==session('com_user.user_id')){
                throw new \Exception('当前账号正在使用，暂不能被删除！',404404);
            }

            $comassessvaluer = Comassessvaluer::where('valuer_id',$ids)->count();
            if($comassessvaluer!=0){
                throw new \Exception('当前账号存在相关数据，暂不能被删除！',404404);
            }
            /*---------删除评估师----------*/
            $companyvaluer = Companyvaluer::where('id',$ids)->forceDelete();
            if(!$companyvaluer){
                throw new \Exception('删除失败',404404);
            }
            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$companyvaluer;
            $url=null;
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常,请刷新后重试！';
            $sdata=$ids;
            $edata=null;
            $url=null;
            DB::rollBack();
        }
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

}