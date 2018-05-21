<?php
/*
|--------------------------------------------------------------------------
| 被征户-家庭成员
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Household;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdmembercrowd;
use App\Http\Model\Nation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class  HouseholdmemberController extends BaseController
{


    public function info(Request $request)
    {
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdmembercrowd = Householdmembercrowd::select(['id','item_id','crowd_id'])
            ->with(['crowd'=>function($query){
                $query->select(['id','name']);
            }])
            ->where('item_id',session('household_user.item_id'))->where('member_id',$id)->get();
        $householdmember=Householdmember::with([
            'nation'=>function($query){
                $query->select(['id','name']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdmember)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=$householdmembercrowd;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdmember;
            $edata=$householdmembercrowd;
            $url=null;

            $view='household.householdmember.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }
}