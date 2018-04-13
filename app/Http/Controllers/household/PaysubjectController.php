<?php
/*
|--------------------------------------------------------------------------
| 被征户--兑付 补偿科目
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
use App\Http\Model\Paysubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class  PaysubjectController extends BaseController{
        public function info(Request $request){
            $id=$request->input('id');

            DB::beginTransaction();
            try{
                if(!$id){
                    throw new \Exception('请先选择数据',404404);
                }
                $paysubject=Paysubject::sharedLock()
                    ->with(['subject'=>function($query){
                        $query->select(['id','name','main','infos']);
                    },'state'=>function($query){
                        $query->select(['code','name']);
                    }])
                    ->find($id);
                if(blank($paysubject)){
                    throw new \Exception('补偿科目数据不存在',404404);
                }
                $code='success';
                $msg='请求成功';
                $sdata=$paysubject;
                $edata=null;
                $url=null;
                $view='household.paysubject.info';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='household.error';
            }
            DB::commit();
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }

        }
}