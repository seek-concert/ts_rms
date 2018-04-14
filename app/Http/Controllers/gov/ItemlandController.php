<?php
/*
|--------------------------------------------------------------------------
| 项目-地块
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Adminunit;
use App\Http\Model\Household;
use App\Http\Model\Itembuilding;
use App\Http\Model\Itemland;
use App\Http\Model\Itempublic;
use App\Http\Model\Itemuser;
use App\Http\Model\Landlayout;
use App\Http\Model\Landprop;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemlandController extends BaseitemController
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
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $select=['id','item_id','address','land_prop_id','land_source_id','land_state_id','admin_unit_id','area','infos'];
        /* ++++++++++ 地址 ++++++++++ */
        $address=trim($request->input('address'));
        if($address){
            $where[]=['address','like','%'.$address.'%'];
            $infos['address']=$address;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=20;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Itemland();
        DB::beginTransaction();
        try{
            $total=$model->sharedLock()
                ->where($where)
                ->where('item_id',$item_id)
                ->count();
            $itemlands=$model
                ->with([
                    'landprop'=>function($query){
                        $query->select(['id','name']);
                    },
                    'landsource'=>function($query){
                        $query->select(['id','name']);
                    },
                    'landstate'=>function($query){
                        $query->select(['id','name']);
                    },
                    'adminunit'=>function($query){
                        $query->select(['id','name']);
                }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $itemlands=new LengthAwarePaginator($itemlands,$total,$per_page,$page);
            $itemlands->withPath(route('g_itemland',['item'=>$item_id]));
            if(blank($itemlands)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$itemlands;
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
            return view('gov.itemland.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemland();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',26],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                $item->schedule_id=3;
                $item->process_id=25;
                $item->code='1';
                $item->save();

                $landprops=Landprop::with([
                    'landsources'=>function($query){
                        $query->with(['landstates']);
                    }])
                    ->select(['id','name'])
                    ->sharedLock()
                    ->get();
                $adminunits=Adminunit::sharedLock()->select(['id','name'])->get();

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item'=>$this->item,
                    'landprops'=>$landprops,
                    'adminunits'=>$adminunits,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemland.add';
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = null;
                $url = null;

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
                'address' => 'required',
                'land_prop_id' => 'required',
                'land_source_id' => 'required',
                'land_state_id' => 'required',
                'admin_unit_id' => 'required',
                'area' => 'required',
                'gov_pic' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写',
                'unique' => ':attribute已存在'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 赋值 ++++++++++ */
            DB::beginTransaction();
            try {
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',26],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                $item->process_id=25;
                $item->code='1';
                $item->save();
                /* ++++++++++ 地块是否存在 ++++++++++ */
                $address = $request->input('address');
                $itemland = Itemland::where('item_id',$item_id)->where('address',$address)->lockForUpdate()->first();
               if(blank($itemland)){
                   /* ++++++++++ 新增数据 ++++++++++ */
                   $itemland = $model;
                   $itemland->fill($request->all());
                   $itemland->addOther($request);
                   $itemland->item_id=$item_id;
                   $itemland->save();
               }else{
                   /* ++++++++++ 修改数据 ++++++++++ */
                   $itemland->gov_pic=$request->input('gov_pic');
                   $itemland->save();
               }
                if(blank($itemland)) {
                    throw new \Exception('添加失败', 404404);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemland;
                $edata = null;
                $url = route('g_itemland',['item'=>$item_id]);
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

    /* ========== 详情 ========== */
    public function info(Request $request){
        $item_id=$this->item_id;
        $id=$request->input('id');
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
        /* ++++++++++ 地块信息 ++++++++++ */
        $itemland=Itemland::sharedLock()->find($id);
        /* ++++++++++ 楼栋信息 ++++++++++ */
        $itembuildings=Itembuilding::with(
                ['buildingstruct'=>function($query){
                    $query->select(['id','name']);
                }])
            ->where('item_id',$item_id)
            ->where('land_id',$id)
            ->sharedLock()
            ->get();
        /* ++++++++++ 地块公共附属物 ++++++++++ */
        $itempublics=Itempublic::sharedLock()
            ->where('item_id',$item_id)
            ->where('land_id',$id)
            ->where('building_id',0)
            ->get();
        /* ++++++++++ 地块户型 ++++++++++ */
        $landlayouts=Landlayout::sharedLock()
            ->where('item_id',$item_id)
            ->where('land_id',$id)
            ->get();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($itemland)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=[
                'item'=>$this->item,
                'itemland'=>$itemland,
                'itembuildings'=>$itembuildings,
                'itempublics'=>$itempublics,
                'landlayouts'=>$landlayouts
                ];
            $edata=null;
            $url=null;

            $view='gov.itemland.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /* ========== 修改 ========== */
    public function edit(Request $request){
        $id=$request->input('id');
        $item_id = $this->item_id;
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',26],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }

                $itemland=Itemland::sharedLock()->find($id);
                if(blank($itemland)){
                    throw new \Exception('数据不存在',404404);
                }
                $landprops=Landprop::with([
                    'landsources'=>function($query){
                        $query->with(['landstates']);
                    }])
                    ->select(['id','name'])
                    ->sharedLock()
                    ->get();
                $adminunits=Adminunit::sharedLock()->select(['id','name'])->get();

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item'=>$this->item,
                    'itemland'=>$itemland,
                    'landprops'=>$landprops,
                    'adminunits'=>$adminunits,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemland.edit';
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = null;
                $url = null;

                $view='gov.error';
            }
            DB::commit();

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Itemland();
            /* ********** 表单验证 ********** */
            $rules=[
                'address'=> ['required',Rule::unique('item_land')->where(function ($query) use($item_id,$id){
                    $query->where('item_id', $item_id)->where('id','<>',$id);
                })],
                'land_prop_id' => 'required',
                'land_source_id' => 'required',
                'land_state_id' => 'required',
                'admin_unit_id' => 'required',
                'area' => 'required',
                'gov_pic' => 'required'
            ];
            $messages=[
                'required'=>':attribute必须填写',
                'unique'=>':attribute已存在'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',26],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                $item->process_id=25;
                $item->code='1';
                $item->save();
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemland=Itemland::lockForUpdate()->find($id);
                if(blank($itemland)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $itemland->fill($request->all());
                $itemland->editOther($request);
                $itemland->save();
                if(blank($itemland)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$itemland;
                $edata=null;
                $url=route('g_itemland_info',['id'=>$id,'item'=>$this->item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$itemland;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 删除 ========== */
    public function del(Request $request){
        $ids = $request->input('id');
        if(blank($ids)){
            $result=['code'=>'error','message'=>'请选择要删除的数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        /* ********** 删除数据 ********** */
        DB::beginTransaction();
        try{
            $item=$this->item;
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if(!in_array($item->process_id,[24,25]) || ($item->process_id==24 && $item->code!='22') || ($item->process_id==25 && $item->code!='1')){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查操作权限 ++++++++++ */
            $count=Itemuser::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['process_id',26],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                throw new \Exception('您没有执行此操作的权限',404404);
            }

            /*---------是否正在被使用----------*/
            $itempublic = Itempublic::where('land_id',$ids)->count();
            if($itempublic!=0){
                throw new \Exception('该地块下含有公共附属物,暂时不能被删除！',404404);
            }
             $landlayout = Landlayout::where('land_id',$ids)->count();
            if($landlayout!=0){
                throw new \Exception('该地块下含有地块户型,暂时不能被删除！',404404);
            }
            $itembuilding = Itembuilding::where('land_id',$ids)->count();
            if($itembuilding!=0){
                throw new \Exception('该地块下含有楼栋,暂时不能被删除！',404404);
            }
            $household = Household::where('land_id',$ids)->count();
            if($household!=0){
                throw new \Exception('该地块正在被使用,暂时不能被删除！',404404);
            }
            /*---------地快----------*/
            $itemland = Itemland::where('id',$ids)->forceDelete();
            if(!$itemland){
                throw new \Exception('删除失败',404404);
            }
            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$itemland;
            $url=null;
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常,请刷新后重试！';
            $sdata=$ids;
            $edata=null;
            $url=null;
            DB::rollBack();
        }
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

}