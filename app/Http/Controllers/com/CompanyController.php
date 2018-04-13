<?php
/*
|--------------------------------------------------------------------------
| 评估机构
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompanyController extends BaseauthController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ++++++++++ 评估机构简介 ++++++++++ */
    public function info(Request $request){
        $id = session('com_user.company_id');
        DB::beginTransaction();
           $company = Company::where('id',$id)->sharedLock()->first();
        DB::commit();
        if(blank($company)){
            $code='error';
            $msg='暂无数据';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='查询成功';
            $sdata=$company;
            $edata=null;
            $url=null;
        }
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('com.company.info')->with($result);
        }
    }

    /* ++++++++++ 修改评估机构简介 ++++++++++ */
    public function edit(Request $request){
        $id = session('com_user.company_id');
        if($request->isMethod('get')){
            DB::beginTransaction();
            $company = Company::where('id',$id)->sharedLock()->first();
            DB::commit();
            if(blank($company)){
                $code='error';
                $msg='暂无数据';
                $sdata=null;
                $edata=null;
                $url=null;
            }else{
                $code='success';
                $msg='查询成功';
                $sdata=$company;
                $edata=null;
                $url=null;
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('com.company.edit')->with($result);
            }
        }else{
            $model=new Company();
            /* ********** 表单验证 ********** */
            $rules=[
                'type'=>'required',
                'name'=>'required|unique:company,name,'.$id.',id',
                'address'=>'required',
                'phone'=>'required',
                'content'=>'required'
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
                $company=Company::withTrashed()
                    ->lockForUpdate()
                    ->find($id);
                if(blank($company)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $company->fill($request->input());
                $company->editOther($request);
                $company->save();
                if(blank($company)){
                    throw  new \Exception('修改失败',404404);
                }

                $code='success';
                $msg='修改成功';
                $sdata=$company;
                $edata=null;
                $url=route('c_company_info');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$company;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }
}