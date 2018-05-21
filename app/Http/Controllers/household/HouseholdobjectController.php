<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户-其他补偿事项
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Household;
use App\Http\Model\Householdobject;
use App\Http\Model\Itemobject;
use App\Http\Model\Itemuser;
use App\Http\Model\Objects;
use App\Http\Model\Payobject;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdobjectController extends BaseController
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
        $item_id=$this->item_id;

        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $data['item_id'] = $item_id;
        $householdobject=Householdobject::with([
            'object'=>function($query){
                $query->select(['id','name']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdobject)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=$data;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdobject;
            $edata=$data;
            $url=null;

            $view='household.householdobject.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }



}