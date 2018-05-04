<?php
/*
|--------------------------------------------------------------------------
| 项目-违建解决
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
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
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        $id = $request->input('id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdbuildingdeal=Householdbuildingdeal::sharedLock()->find($id);
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
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

}