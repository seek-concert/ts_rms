<?php
/*
|--------------------------------------------------------------------------
| 项目-违建解决
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Household;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdbuildingdeal;
use App\Http\Model\Itemuser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdbuildingdealController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }


    /* ++++++++++ 违建被征户首页 ++++++++++ */
    public function index(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $infos['item'] = $item;
        /* ********** 查询条件 ********** */
        $where=[];
        $total_where=[];
        $where[] = ['item_id',$item_id];
        $total_where[] = ['item_household_detail.item_id',$item_id];
        $infos['item_id'] = $item_id;
        /* ********** 地块 ********** */
        $land_id=$request->input('land_id');
        if(is_numeric($land_id)){
            $where[] = ['land_id',$land_id];
            $total_where[] = ['item_household_detail.land_id',$land_id];
            $infos['land_id'] = $land_id;
        }
        /* ********** 楼栋 ********** */
        $building_id=$request->input('building_id');
        if(is_numeric($building_id)){
            $where[] = ['building_id',$building_id];
            $total_where[] = ['item_household_detail.building_id',$building_id];
            $infos['building_id'] = $building_id;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?'item_household_detail.'.$ordername:'item_household_detail.'.'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'desc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=20;
        $page=$request->input('page',1);

        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $total=Householddetail::sharedLock()
                ->select(DB::raw('count(distinct(item_household_detail.id)) as household_num'))
                ->leftJoin('item_household_building as hb', 'hb.household_id', '=', 'item_household_detail.household_id')
                ->where('hb.code','<>','90')
                ->where($total_where)
                ->first();
            $households=Householddetail::with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                ->withCount(['householdbuildings'=>function($query){
                    $query->whereIn('code',['91','93']);
                }])
                ->leftJoin('item_household_building as hb', 'hb.household_id', '=', 'item_household_detail.household_id')
                ->where('hb.code','<>','90')
                ->where($total_where)
                ->orderBy($ordername,$orderby)
                ->distinct()
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $households=new LengthAwarePaginator($households,$total->household_num,$per_page,$page);
            $households->withPath(route('g_householdbuildingdeal',['item'=>$item_id]));


            if(blank($households)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$households;
            $edata=$infos;
            $url=null;
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=$infos;
            $url=null;
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.householdbuildingdeal.index')->with($result);
        }
    }

    /* ========== 违建列表 ========== */
    public function infos(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $infos['item'] = $item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;

        $household_id = $request->input('household_id');
        if(blank($household_id)){
            $result=['code'=>'error','message'=>'请先选择被征收户','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('gov.error')->with($result);
            }
        }
        $where[] = ['household_id',$household_id];
        $infos['household_id'] = $household_id;
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $householdbuildings=Householdbuilding::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'buildingstruct'=>function($query){
                    $query->select(['id','name']);
                },
                'defbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'realbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'landlayout'=>function($query){
                    $query->select(['id','name','area']);
                },
                'state'=>function($query){
                    $query->select(['id','code','name']);
                }])
                ->where($where)
                ->where('code','<>',90)
                ->orderBy('code','desc')
                ->sharedLock()
                ->get();

            if(blank($householdbuildings)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$householdbuildings;
            $edata=$infos;
            $url=null;
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=$infos;
            $url=null;
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.householdbuildingdeal.infos')->with($result);
        }
    }

    /* ========== 合法性认定 ========== */
    public function status(Request $request){
        $id = $request->input('id');
        $item_id=$this->item_id;
        $item=$this->item;
        $household_id = $request->input('household_id');
        if($request->isMethod('get')){
            $sdata['id'] = $id;
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['household_id'] = $household_id;
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householdbuildingdeal.status')->with($result);
            }
        }else{
            $item=$this->item;
            if(blank($item)){
                $result=['code'=>'error','message'=>'项目不存在！','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查操作权限 ++++++++++ */
            $count=Itemuser::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['process_id',28],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                $result=['code'=>'error','message'=>'您没有执行此操作的权限','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            $code = $request->input('code');
            if(!in_array($code,[92,93])){
                $result=['code'=>'error','message'=>'数据异常,认定失败!','sdata'=>null,'edata'=>null,'url'=>null];
                if($request->ajax()){
                    return response()->json($result);
                }else {
                    return view('gov.error')->with($result);
                }
            }
            DB::beginTransaction();
            try{
                /* ++++++++++ 锁定数据 ++++++++++ */
                $householdbuilding = Householdbuilding::lockForUpdate()->find($id);
                if(blank($householdbuilding)){
                    throw new \Exception('数据不存在！',404404);
                }
                $householdbuilding->code = $code;
                $householdbuilding->save();
                if(blank($householdbuilding)){
                    throw new \Exception('数据异常,认定失败！',404404);
                }

                /*------------ 检测是否所有的都已经确权 ------------*/
                $household_code = $this->household_status($household_id);
                if($household_code){
                    /*----------- 修改状态 ------------*/
                    /* ++++++++++ 锁定数据 ++++++++++ */
                    $household =  Household::lockForUpdate()->find($household_id);
                    if(blank($household)){
                        throw new \Exception('暂无相关数据',404404);
                    }
                    $household->code = 62;
                    $household->save();
                    if(blank($household)){
                        throw new \Exception('处理失败',404404);
                    }
                }

                $code = 'success';
                $msg = '认定成功';
                $sdata = $householdbuilding;
                $edata = null;
                $url = route('g_householdbuildingdeal_infos',['item'=>$item_id,'household_id'=>$household_id]);
                DB::commit();
            }catch(\Exception $exception){
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '认定失败';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }

    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $household_building_id = $request->input('household_building_id');
        $item_id = $this->item_id;
        $item = $this->item;
        $household_id = $request->input('household_id');
        $model=new Householdbuildingdeal();
        if($request->isMethod('get')){
            $sdata['household_building_id'] = $household_building_id;
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['models'] = $model;
            $sdata['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householdbuildingdeal.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            $item=$this->item;
            if(blank($item)){
                $result=['code'=>'error','message'=>'项目不存在！','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查操作权限 ++++++++++ */
            $count=Itemuser::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['process_id',28],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                $result=['code'=>'error','message'=>'您没有执行此操作的权限','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            $code = $request->input('code');
              if($code==0){
                  $code = 94;
              }else{
                  $code = 95;
              }
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'household_id' => 'required',
                'land_id' => 'required',
                'building_id' => 'required',
                'household_building_id' => 'required',
                'way' => 'required',
                'price' => 'required'
            ];
            $messages = [
                'required' => ':attribute 为必须项'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 锁定建筑 ++++++++++ */
                $householdbuilding = Householdbuilding::lockforupdate()->find($household_building_id);
                if (blank($householdbuilding)) {
                    throw new \Exception('数据异常', 404404);
                }
                /* ++++++++++ 修改建筑建筑 ++++++++++ */
                $householdbuilding->code = $code;
                $householdbuilding->save();
                /* ++++++++++ 批量赋值 ++++++++++ */
                $householdbuildingdeal = $model;
                $householdbuildingdeal->fill($request->input());
                $householdbuildingdeal->addOther($request);
                $householdbuildingdeal->item_id = $item_id;
                $householdbuildingdeal->amount = $householdbuilding->real_outer*$request->input('price');
                $householdbuildingdeal->code = 0;
                $householdbuildingdeal->save();
                if (blank($householdbuildingdeal)) {
                    throw new \Exception('处理失败！', 404404);
                }
                /*------------ 检测是否所有的都已经确权 ------------*/
                $household_code = $this->household_status($household_id);
                if($household_code){
                    /*----------- 修改状态 ------------*/
                    /* ++++++++++ 锁定数据 ++++++++++ */
                    $household =  Household::lockForUpdate()->find($household_id);
                    if(blank($household)){
                        throw new \Exception('暂无相关数据',404404);
                    }
                    $household->code = 62;
                    $household->save();
                    if(blank($household)){
                        throw new \Exception('处理失败',404404);
                    }
                }

                $code = 'success';
                $msg = '处理成功';
                $sdata = $householdbuildingdeal;
                $edata = null;
                $url = route('g_householdbuildingdeal_infos',['item'=>$item_id,'household_id'=>$household_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '处理失败！';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        $household_id = $request->input('household_id');
        $item_id = $this->item_id;
        if(blank($household_id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdbuildingdeal=Householdbuildingdeal::sharedLock()->where('household_id',$household_id)->where('item_id',$item_id)->first();
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
            $view='gov.error';
        }else{
            $view='gov.householdbuildingdeal.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

}