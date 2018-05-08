<?php
/*
|--------------------------------------------------------------------------
| 项目-评估报告
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\Assess;
use App\Http\Model\Assets;
use App\Http\Model\Household;
use App\Http\Model\Estate;
use App\Http\Model\Itemriskreport;
use App\Http\Model\Itemuser;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssessController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $assess=null;
        DB::beginTransaction();
        try{
            $total=Assess::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();

            $per_page=15;
            $page=$request->input('page',1);
            $assesses=Assess::with(['itemland'=>function($query){
                $query->select(['id','address']);
            },'itembuilding'=>function($query){
                $query->select(['id','building']);
            },'household'=>function($query){
                $query->select(['id','unit','floor','number','type']);
            },'state'])
                ->sharedLock()
                ->where('item_id',$this->item_id)
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            
            $assesses=new LengthAwarePaginator($assesses,$total,$per_page,$page);
            $assesses->withPath(route('g_assess',['item'=>$this->item_id]));

            $code='success';
            $msg='获取成功';
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
        }
        $sdata=[
            'item'=>$this->item,
            'assesses'=>$assesses,
        ];
        $edata=null;
        $url=null;
        DB::commit();
        $result=['code'=>$code, 'message'=>$msg, 'sdata'=>$sdata, 'edata'=>$edata, 'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.assess.index')->with($result);
        }
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        $id=$request->input('id');
        DB::beginTransaction();
        try{
            if(!$id){
                throw new \Exception('错误操作',404404);
            }
            /* ********** 评估汇总 ********** */
            $assess=Assess::with(['itemland'=>function($query){
                $query->select(['id','address']);
            },'itembuilding'=>function($query){
                $query->select(['id','building']);
            },'household'=>function($query){
                $query->with(['householddetail'=>function($query){
                    $query->select(['id','household_id','status','register','has_assets']);
                }])
                    ->select(['id','unit','floor','number','type']);
            },'state'])
                ->sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['id',$id],
                ])
                ->first();
            if(blank($assess)){
                throw new \Exception('数据不存在',404404);
            }
            /* ********** 房产评估 ********** */
            $estate=Estate::with(['estatebuildings'=>function($query){
                $query->with(['realbuildinguse','buildingstruct','state']);
            },'company'=>function($query){
                $query->select(['id','name']);
            },'state'])
                ->sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['assess_id',$assess->id],
                ])
                ->first();
            $assets=null;
            if($assess->household->householddetail->getOriginal('has_assets')==1){
                $assets=Assets::with(['company'=>function($query){
                    $query->select(['id','name']);
                },'state'])->sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['assess_id',$assess->id],
                    ])
                    ->first();
            }

            $household=Household::sharedLock()
                ->find($assess->household_id);
            if(blank($household)){
                throw new \Exception('暂无该被征户信息!',404404);
            }

            $code='success';
            $msg='获取成功';
            $sdata=[
                'item'=>$this->item,
                'assess'=>$assess,
                'estate'=>$estate,
                'assets'=>$assets,
                'household'=>$household
            ];
            $edata=null;
            $url=null;

            $view='gov.assess.info';
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
            $sdata=null;
            $edata=null;
            $url=null;

            $view='gov.error';
        }

        DB::commit();
        $result=['code'=>$code, 'message'=>$msg, 'sdata'=>$sdata, 'edata'=>$edata, 'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }


    /* ========== 评估报告审查========== */
    public function check(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'错误请求','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        if($request->isMethod('get')){

            DB::beginTransaction();
            try{

                /*评估汇总信息检查*/
                $assess=Assess::sharedLock()
                    ->find($id);
                if (blank($assess)){
                    throw new \Exception('该被征户暂无有效评估报告',404404);
                }
                if($assess->code!=132){
                    throw new \Exception('评估现处于【'.$assess->state->name.'】状态，不能进行审查', 404404);
                }

                /*被征户信息检查*/
                $household=Household::sharedLock()
                    ->with('householddetail')
                    ->find($assess->household_id);
                if(blank($household)){
                    throw new \Exception('被征户信息不存在', 404404);
                }
                if($household->code!=64){
                    throw new \Exception('被征户现处于【'.$household->state->name.'】状态，不能进行评估审查', 404404);
                }

                $code='success';
                $msg='请求成功';
                $sdata=['item'=>$this->item,'assess'=>$assess,'item_id'=>$this->item_id,'household'=>$household];
                $edata=null;
                $url=null;
                $view='gov.assess.check';
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;
                $view='gov.error';
                DB::rollBack();
            }


            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }

        }
        else{
            DB::beginTransaction();
            try{
                $household=Household::sharedLock()
                    ->with('householddetail')
                    ->find($request->input('household_id'));
                if(blank($household)){
                    throw new \Exception('被征户信息不存在', 404404);
                }
                if($household->code!=64){
                    throw new \Exception('被征户现处于【'.$household->state->name.'】状态，不能进行评估审查', 404404);
                }

                $household->code=65;
                $household->save();
                if(blank($household)){
                    throw new \Exception('修改失败', 404404);
                }

                $assess=Assess::sharedLock()
                    ->find($id);
                if (blank($assess)){
                    throw new \Exception('错误操作',404404);
                }
                if($assess->code!=132){
                    throw new \Exception('评估现处于【'.$assess->state->name.'】状态，不能进行审查', 404404);
                }
                $assess->code=$request->input('code');
                $assess->save();
                if (blank($assess)) {
                    throw new \Exception('修改失败', 404404);
                }

                /*房产评估状态修改*/
                $estate=Estate::sharedLock()
                    ->where('assess_id',$id)
                    ->update(['code'=>$request->input('code')]);
                if(blank($estate)){
                    throw new \Exception('修改失败', 404404);
                }

                /*资产评估状态修改*/
                if($household->householddetail->getOriginal('has_assets')==1){
                    $assets=Assets::sharedLock()
                        ->where('assess_id',$id)
                        ->update(['code'=>$request->input('code')]);
                    if(blank($assets)){
                        throw new \Exception('修改失败', 404404);
                    }
                }

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_assess_info',['id'=>$id,'item'=>$this->item_id]);
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $sdata=null;
                $edata=null;
                $url=null;
                $view='gov.error';
                DB::rollBack();
            }

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];

            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
    }
}