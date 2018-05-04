<?php
/*
|--------------------------------------------------------------------------
| 被征户--被征户详情
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;

use App\Http\Model\Householddetail;
use App\Http\Model\Household;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdobject;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householdassets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Model\Itemrisk;

class  HouseholddetailController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request){
        /* ++++++++++ 是否调取接口(分页) ++++++++++ */
        $app = $request->input('app');
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$this->item_id];
        $infos['item_id'] = $this->item_id;
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

        /* ********** 查询 ********** */
        $model=new Household();
        DB::beginTransaction();
        try{
            if($app){
                /* ********** 资产评估 ********** */
                $has_assets=$request->input('has_assets');
                if(is_numeric($has_assets)){
                    $where[] = ['has_assets',$has_assets];
                    $infos['has_assets'] = $has_assets;
                }
                $households=Householddetail::with([
                    'item'=>function($query){
                        $query->select(['id','name']);
                    },
                    'household'=>function($query){
                        $query->select(['id','unit','floor','number','type']);
                    },
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                    ->select(['id','item_id','household_id','land_id','building_id','has_assets'])
                    ->where($where)
                    ->sharedLock()
                    ->get();
            }else{
                $households=$model
                    ->with(['item'=>function($query){
                        $query->select(['id','name']);
                    },
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'itembuilding'=>function($query){
                            $query->select(['id','building']);
                        }])
                    ->where($where)
                    ->sharedLock()
                    ->first();
            }

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
            return view('household.householddetail.index')->with($result);
        }
    }

    public function info(Request $request){

        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        try{
            $data['item_id'] = $this->item_id;
            $data['household'] = new Household();
            $data['household_detail'] = Householddetail::with([
                'defbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'realbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'layout'=>function($query){
                    $query->select(['id','name']);
                }])
                ->where('household_id',$this->household_id)
                ->first();

            $household=Household::with([
                'itemland'=>function($query){
                    $query->select(['id','address'])
                        ->with(['adminunit'=>function($querys){
                            $querys->select(['name','address','phone','contact_man','contact_tel','infos']);
                        }]);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->sharedLock()
                ->find($this->household_id);
            $data['householdmember']=Householdmember::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'nation'=>function($query){
                    $query->select(['id','name']);
                }])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->sharedLock()
                ->get();
            $data['householdobject']=Householdobject::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'object'=>function($query){
                    $query->select(['id','name']);
                }])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->sharedLock()
                ->get();
            $data['householdbuilding']=Householdbuilding::with(['itemland'=>function($query){
                $query->select(['id','address']);
            },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'buildingstruct'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                },])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->sharedLock()
                ->get();
            $data['householdassets']=Householdassets::where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->where('number','<>',null)
                ->sharedLock()
                ->get();
            $result=['code'=>'success','message'=>'获取成功','sdata'=>$household,'edata'=>$data,'url'=>null];
            $view='household.householddetail.info';
            DB::commit();
        }catch (\Exception $exception){
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
            $result=['code'=>'warning','message'=>$msg,'sdata'=>null,'edata'=>null,'url'=>null];
            $view='household.error';
            DB::rollback();
        }
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /*社会稳定风险评估-添加页面*/
    public function add(Request $request)
    {
        $land_id = session('household_user.land_id');
        $building_id = session('household_user.building_id');


        if ($request->isMethod('get')) {

            DB::beginTransaction();
            $itemrisk = Itemrisk::with(
                ['item' => function ($query) {
                    $query->select(['id', 'name']);
                }, 'land' => function ($query) {
                    $query->select(['id', 'address']);
                }, 'building' => function ($query) {
                    $query->select(['id', 'building']);
                }])
                ->where('household_id', $this->household_id)
                ->where('item_id', $this->item_id)
                ->sharedLock()
                ->first();
            DB::commit();

            if (filled($itemrisk)) {
                return response()->json(['code' => 'error', 'message' => '社会稳定风险评估不允许重复添加!', 'sdata' => null, 'edata' => null, 'url' => null]);
            }

            $model = Household::with(
                ['item' => function ($query) {
                    $query->select(['id', 'name']);
                }, 'itemland' => function ($query) {
                    $query->select(['id', 'address']);
                }, 'itembuilding' => function ($query) {
                    $query->select(['id', 'building']);
                }])
                ->where('item_id', $this->item_id)
                ->sharedLock()
                ->first();
            $model->layout = Layout::pluck('name', 'id');
            $result = ['code' => 'success', 'message' => '请求成功', 'sdata' => $model, 'edata' => new Itemrisk(), 'url' => null];

            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.itemrisk.add')->with($result);
            }
        } /*数据保存*/
        else {
            $model = new Itemrisk();
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'agree' => 'required',
                'repay_way' => 'required',
                'layout_id' => 'required',
                'transit_way' => 'required',
                'move_way' => 'required',
                'move_fee' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }
            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 批量赋值 ++++++++++ */
                $itemrisk = $model;
                $itemrisk->fill($request->all());
                $itemrisk->addOther($request);
                $itemrisk->item_id = $this->item_id;
                $itemrisk->land_id = $land_id;
                $itemrisk->household_id = $this->household_id;
                $itemrisk->building_id = $building_id;

                $itemrisk->save();
                if (blank($itemrisk)) {
                    throw new \Exception('添加失败', 404404);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemrisk;
                $edata = null;
                $url = route('h_itemrisk_info', ['item' => $this->item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);
        }
    }

    /*社会稳定风险评估-修改页面*/
    public function edit(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            $result = ['code' => 'error', 'message' => '请先选择数据', 'sdata' => null, 'edata' => null, 'url' => null];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $data = Itemrisk::with(['item' => function ($query) {
                $query->select(['id', 'name']);
            }, 'building' => function ($query) {
                $query->select(['id', 'building']);
            }, 'land' => function ($query) {
                $query->select(['id', 'address']);
            }])
                ->sharedLock()
                ->find($id);
            $data->layout = Layout::pluck('name', 'id');
            DB::commit();

            /* ++++++++++ 数据不存在 ++++++++++ */
            if (blank($data)) {
                $code = 'warning';
                $msg = '数据不存在';
                $sdata = null;
                $edata = null;
                $url = null;
            } else {
                $code = 'success';
                $msg = '获取成功';
                $sdata = $data;
                $edata = null;
                $url = null;
                $view = 'household.itemrisk.edit';
            }
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => new Itemrisk(), 'url' => $url];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view($view)->with($result);
            }
        } else {
            $model = new Itemrisk();
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'agree' => 'required',
                'repay_way' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try {
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemrisk = Itemrisk::lockForUpdate()->find($id);
                if (blank($itemrisk)) {
                    throw new \Exception('指定数据项不存在', 404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $itemrisk->fill($request->all());
                $itemrisk->editOther($request);
                $itemrisk->save();
                if (blank($itemrisk)) {
                    throw new \Exception('修改失败', 404404);
                }
                $code = 'success';
                $msg = '修改成功';
                $sdata = $itemrisk;
                $edata = null;
                $url = route('h_itemrisk_info');

                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);
        }

    }

    /*确认面积争议*/
    public function area(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $infos['item'] = $item;

        if($request->isMethod('get')){
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
                ->where('area_dispute','1')
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
                $households->withPath(route('h_householddetail_area',['item'=>$item_id]));


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
                return view('household.householddetail.area')->with($result);
            }
        }
        /*被征户提交面积争议确认结果*/
        else{
            /* ++++++++++ 表单验证 ++++++++++ */
            $model=new Householddetail();
            $rules = [
                'area_dispute' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须选择'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try {
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $householddetail = Householddetail::lockForUpdate()
                    ->where('household_id',$this->household_id)
                    ->where('item_id',$this->item_id)
                    ->first();
                if (blank($householddetail)) {
                    throw new \Exception('指定数据项不存在', 404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $householddetail->area_dispute=$request->input('area_dispute');
                $householddetail->save();
                if (blank($householddetail)) {
                    throw new \Exception('修改失败', 404404);
                }
                $code = 'success';
                $msg = '修改成功';
                $sdata = $householddetail;
                $edata = null;
                $url = route('h_householddetail_info');

                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);

        }


    }
}