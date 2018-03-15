<?php
/*
|--------------------------------------------------------------------------
| 项目-冻结房源
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\House;
use App\Http\Model\Housecommunity;
use App\Http\Model\Initbudget;
use App\Http\Model\Itemhouse;
use App\Http\Model\Itemuser;
use App\Http\Model\Layout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemhouseController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $item_id=$this->item_id;
        /* ********** 查询条件 ********** */
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $select=['item_id','house_id','type','created_at'];
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'created_at';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            /* ++++++++++ 冻结房源 ++++++++++ */
            $total=Itemhouse::sharedLock()
                ->where('item_id',$item_id)
                ->count();
            $itemhouses=Itemhouse::with(['house'=>function($query){
                $query->with([
                    'housecommunity'=> function ($query) {
                        $query->withTrashed()->select(['id','name']);
                    },
                    'layout'=> function ($query) {
                        $query->withTrashed()->select(['id','name']);
                    },
                    'housecompany'=> function ($query) {
                        $query->withTrashed()->select(['id','name']);
                    },
                    'state'=> function ($query) {
                        $query->withTrashed()->select(['code','name']);
                    }
                ]);
            }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $itemhouses=new LengthAwarePaginator($itemhouses,$total,$per_page,$page);
            $itemhouses->withPath(route('g_itemhouse',['item'=>$item_id]));
            /* ++++++++++ 初步预算 ++++++++++ */
            $init_budget=Initbudget::sharedLock()->where('item_id',$this->item_id)->first();

            $code='success';
            $msg='查询成功';
            $sdata=[
                'item'=>$this->item,
                'itemhouses'=>$itemhouses,
                'init_budget'=>$init_budget,
            ];
            $edata=$infos;
            $url=null;

            $view='gov.itemhouse.index';
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=$infos;
            $url=null;

            $view='gov.error';
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemhouse();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || $item->process_id!=18 ||  $item->code!='1'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',$item->process_id],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }

                $communitys= Housecommunity::select(['id','name'])->get();
                $layouts = Layout::select(['id','name'])->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item_id'=>$item_id,'communitys'=>$communitys,'layouts'=>$layouts];
                $edata=null;
                $url=null;

                $view='gov.itemhouse.add';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'house_ids'=>'required',
            ];
            $messages = [
                'required' => '请选择 :attribute'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                $item=$this->item;
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || $item->process_id!=18 ||  $item->code!='1'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',$item->process_id],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                /* ++++++++++ 获取房源 ++++++++++ */
                $house_ids = $request->input('house_ids');
                $house_ids=House::lockForUpdate()->whereIn('id',$house_ids)->where('code',150)->pluck('id');
                if(blank($house_ids)){
                    throw new \Exception('当前选择中没有空闲的房源，请重新选择',404404);
                }
                /* ++++++++++ 修改房源状态 ++++++++++ */
                House::whereIn('id',$house_ids)->update(['code'=>'151','updated_at'=>date('Y-m-d H:i:s')]);
                /* ++++++++++ 冻结房源 ++++++++++ */
                $values = [];
                foreach ($house_ids as $house_id){
                    $values[]=[
                        'item_id'=>$item_id,
                        'house_id'=>$house_id,
                        'type'=>0,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $field=['item_id','house_id','type','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_house',$field,$values,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach ($sqls as $sql){
                    DB::statement($sql);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = null;
                $edata = null;
                $url = route('g_itemhouse',['item'=>$item_id]);
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
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }



}