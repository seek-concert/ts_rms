<?php
/*
|--------------------------------------------------------------------------
| 项目-违建解决
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Household;
use App\Http\Model\Householdbuildingarea;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdbuildingdeal;
use App\Http\Model\Itemuser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdbuildingdealController extends BaseController
{


    /* ========== 详情 ========== */
    public function info(Request $request){

        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdbuildingdeal=Householdbuildingdeal::sharedLock()
            ->where('household_id',$this->household_id)
            ->where('item_id',$this->item_id)
            ->first();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdbuildingdeal)){
            $code='error';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdbuildingdeal;
            $edata=null;
            $url=null;
        }
        if($code=='error'){
            $view='household.error';
        }else{
            $view='household.householdbuildingdeal.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if( $request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

}