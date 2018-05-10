<?php
/*
|--------------------------------------------------------------------------
| 项目-面积争议解决
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
use App\Http\Model\Household;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdbuildingarea;
use App\Http\Model\Itemuser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Model\Estate;

class HouseholdbuildingareaController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdbuildingarea=Householdbuildingarea::sharedLock()->where('household_id',$this->household_id)->where('item_id',$this->item_id)->first();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdbuildingarea)){
            $code='error';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdbuildingarea;
            $edata=null;
            $url=null;
        }
        if($code=='error'){
            $view='household.error';
        }else{
            $view='household.householdbuildingarea.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    public function confirm(Request $request){
        $area_dispute=$request->input('area_dispute');
        DB::beginTransaction();
        try{
            if(!$area_dispute){
                throw new \Exception('请选择处理结果！', 404404);
            }
            $householddetail=Householddetail::sharedLock()
                ->where('household_id',$this->household_id)
                ->where('item_id',$this->item_id)
                ->first();
            if($householddetail->code>2){
                throw new \Exception('面积争议已处理！', 404404);
            }
            $householddetail->area_dispute=$area_dispute;
            $householddetail->save();
            if(blank($householddetail)){
                throw new \Exception('保存失败！', 404404);
            }

            if($area_dispute==3){
                $estate=Estate::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('household_id',$this->household_id)
                    ->first();
                $estate->area_dispute=2;
                $estate->save();
                if(blank($estate)){
                    throw new \Exception('保存失败！', 404404);
                }
            }

            $code = 'success';
            $msg = '提交成功';
            $sdata = null;
            $edata = null;
            $url = route('h_householdright');
            DB::commit();
        }catch (\Exception $exception) {

            DB::rollBack();
            $code = 'error';
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '保存失败';
            $sdata = null;
            $edata = null;
            $url = null;
        }
        $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view('household.error')->with($result);
        }
    }

}