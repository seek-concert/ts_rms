<?php
/*
|--------------------------------------------------------------------------
| 项目-面积争议解决
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Estate;
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

class HouseholdbuildingareaController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }


    /* ++++++++++ 首页 ++++++++++ */
    public function index(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $infos['item'] = $item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        /* ********** 地块 ********** */
        $land_id=$request->input('land_id');
        if(is_numeric($land_id)){
            $where[] = ['land_id',$land_id];
            $infos['land_id'] = $land_id;
        }
        /* ********** 楼栋 ********** */
        $building_id=$request->input('building_id');
        if(is_numeric($building_id)){
            $where[] = ['building_id',$building_id];
            $infos['building_id'] = $building_id;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'area_dispute';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        $infos['wait_num']=Householddetail::sharedLock()
            ->where($where)
            ->whereNotin('area_dispute',['0','3','5'])
            ->count();
        /*============== 检测测绘状态【更改面积争议的测绘状态】 ================*/
        $householddetail =  Householddetail::with([
            'householdbuildings'=>function($query){
                $query->with(['landlayout'=>function($querys){
                    $querys->whereNotnull('picture');
                }]);
             }])
            ->withCount(['householdbuildings',
                'householdbuildings as householdbuildings_layout'=>function($query){
                    $query->whereNotnull('layout_id');
                }
            ])
            ->where('item_id',$item_id)
            ->whereIn('area_dispute',[1,2])
            ->get();

        if(!blank($householddetail)){
            $household_ids = [];
            foreach($householddetail as $k=>$v){
                if($v['householdbuildings_count']==$v['householdbuildings_layout']){
                    $num = 0;
                    foreach($v['householdbuildings'] as $key=>$val){
                        if(!is_null($val->landlayout->id)){
                            if(!in_array($v['household_id'],$household_ids)){
                                $num+=1;
                            }
                        }
                    }
                    /* 已测绘的建筑户型数量与建筑户型数量比较*/
                    if($num==$v['householdbuildings_layout']){
                        $household_ids[] = $v['household_id'];
                    }
                }
            }
           if($household_ids!=[]){
              Householddetail::whereIn('household_id',$household_ids)->update(['area_dispute'=>2,'updated_at'=>date('Y-m-d H:i:s')]);
              Estate::whereIn('household_id',$household_ids)->update(['area_dispute'=>2,'updated_at'=>date('Y-m-d H:i:s')]);
           }
       }

        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{

            $total=Householddetail::sharedLock()
                ->where($where)
                ->where('area_dispute','<>','0')
                ->count();
            $households=Householddetail::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->withCount(['householdbuildings'=>function($query){
                    $query->whereNull('layout_id');
                }])
                ->where($where)
                ->where('area_dispute','<>','0')
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('g_householdbuildingarea',['item'=>$item_id]));


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
            return view('gov.householdbuildingarea.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $id = $request->input('id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('gov.error')->with($result);
            }
        }
        $item_id = $this->item_id;
        $item = $this->item;
        $household_id = $request->input('household_id');
        $model=new Householdbuildingarea();
        if($request->isMethod('get')){
            $sdata['id'] = $id;
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['models'] = $model;
            $sdata['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householdbuildingarea.add')->with($result);
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
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'household_id' => 'required',
                'land_id' => 'required',
                'building_id' => 'required',
                'picture' => 'required'
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
                /* ++++++++++ 修改面积争议状态 ++++++++++ */
                $householddetail = Householddetail::lockforupdate()->find($id);
                if (blank($householddetail)) {
                    throw new \Exception('数据异常', 404404);
                }
                $householddetail->area_dispute = 5;
                $householddetail->save();
                if (blank($householddetail)) {
                    throw new \Exception('处理失败！', 404404);
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $householdbuildingarea = $model;
                $householdbuildingarea->fill($request->input());
                $householdbuildingarea->addOther($request);
                $householdbuildingarea->item_id = $item_id;
                $householdbuildingarea->code = 0;
                $householdbuildingarea->save();
                if (blank($householdbuildingarea)) {
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
                $sdata = $householdbuildingarea;
                $edata = null;
                $url = route('g_householdbuildingarea',['item'=>$item_id]);
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
        $householdbuildingarea=Householdbuildingarea::sharedLock()->where('household_id',$household_id)->where('item_id',$item_id)->first();
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
            $view='gov.error';
        }else{
            $view='gov.householdbuildingarea.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

}