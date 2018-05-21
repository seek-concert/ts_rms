<?php
/*
|--------------------------------------------------------------------------
| 被征收户 --家庭成员  特殊人群
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Crowd;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdmembercrowd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdmembercrowdController extends BaseController
{


    /* ========== 详情 ========== */
    public function info(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('household.error')->with($result);
            }
        }
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdmembercrowd=Householdmembercrowd::with([
            'crowd'=>function($query){
                $query->select(['id','name']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdmembercrowd)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdmembercrowd;
            $edata=new Householdmembercrowd();
            $url=null;

            $view='household.householdmembercrowd.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

}