<?php
/*
|--------------------------------------------------------------------------
| 被征户--资产
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');

use App\Http\Model\Assess;
use App\Http\Model\Assets;
use App\Http\Model\Estate;
use App\Http\Model\Householddetail;
use App\Http\Model\Item;
use App\Http\Model\Household;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class AssessController extends BaseController{


    public function info(Request $request){
        

        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id', $this->item_id];
        $where[] = ['household_id', $this->household_id];


        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            /*项目信息*/
            $item=Item::sharedLock()->find($this->item_id);
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }

            /* ++++++++++ 被征收户 ++++++++++ */
            $household=Household::sharedLock()
                ->select(['id','item_id','land_id','building_id','unit','floor','number','type'])
                ->find($this->household_id);
            if(blank($item)){
                throw new \Exception('被征户不存在',404404);
            }

            $assess=Assess::sharedLock()
                    ->where([
                        ['item_id', $this->item_id],
                        ['household_id', $this->household_id]
                    ])
                    ->whereIn('code',['135','133','136'])
                    ->first();

            if(blank($assess)){
                throw new \Exception('暂无有效评估汇总',404404);
            }

            $estate=Estate::with(['company'=>function($query){
                $query->select(['id','name']);
            },'state'=>function($query){
                $query->select(['code','name']);
            }])
                ->sharedLock()
                ->where([['assess_id',$assess->id]])
                ->first();
            if(blank($estate)){
                throw new \Exception('暂无有效评估数据',404404);
            }

            $assets=Assets::with(['company'=>function($query){
                $query->select(['id','name']);
            },'state'=>function($query){
                $query->select(['code','name']);
            }])
                ->sharedLock()
                ->where([['assess_id',$assess->id]])
                ->first();

            $code='success';
            $msg='查询成功';
            $sdata=[
                'item'=>$item,
                'household'=>$household,
                'assess'=>$assess,
                'assets'=>$assets,
                'estate'=>$estate,
            ];
            $edata=null;
            $url=null;
            $view='household.assess.info';
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;
            $view='household.error';
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') || $request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }

    public function confirm(Request $request){
        $id=$request->input('id');
        $code=$request->input('code');
        if(!$id || !$code){
            $result=['code'=>'error','message'=>'错误请求','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('household.error')->with($result);
            }
        }

        DB::beginTransaction();
        try{
            $household=Household::sharedLock()
                ->with('householddetail')
                ->find($this->household_id);
            if($household->code!=64){
                throw new \Exception('您现处于【'.$household->state->name.'】状态，不能进行评估确认', 404404);
            }

            $household->code=65;
            $household->save();
            if(blank($household)){
                throw new \Exception('修改失败', 404404);
            }

            $assess=Assess::sharedLock()
                ->find($id);
            if($assess->code!=133){
                throw new \Exception('评估现处于【'.$assess->state->name.'】状态，不能进行确认', 404404);
            }
            if (blank($assess)){
                throw new \Exception('错误操作',404404);
            }
            $assess->code=$code;
            $assess->save();

            if (blank($assess)) {
                throw new \Exception('修改失败', 404404);
            }

            /*房产评估状态修改*/
            $estate=Estate::sharedLock()
                ->where('assess_id',$id)
                ->update(['code'=>$code]);
            if(blank($estate)){
                throw new \Exception('修改失败', 404404);
            }

            /*资产评估状态修改*/
            if($household->householddetail->getOriginal('has_assets')==1){
                $assets=Assets::sharedLock()
                    ->where('assess_id',$id)
                    ->update(['code'=>$code]);
                if(blank($assets)){
                    throw new \Exception('修改失败', 404404);
                }
            }

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('h_assess');
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
            $sdata=$exception->getMessage();
            $edata=null;
            $url=null;
            $view='household.error';
            DB::rollBack();
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];

        if($request->is('api/*') || $request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }

    }



}
