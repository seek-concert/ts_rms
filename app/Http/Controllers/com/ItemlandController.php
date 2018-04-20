<?php
/*
|--------------------------------------------------------------------------
| 项目-地块
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Adminunit;
use App\Http\Model\Itembuilding;
use App\Http\Model\Itemland;
use App\Http\Model\Itempublic;
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
            $itemlands->withPath(route('c_itemland',['item'=>$item_id]));
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
            return view('com.itemland.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemland();
        if($request->isMethod('get')){
            DB::beginTransaction();
            $landprops=Landprop::with([
                'landsources'=>function($query){
                    $query->with(['landstates']);
                }])
                ->select(['id','name'])
                ->sharedLock()
                ->get();
            $adminunits=Adminunit::sharedLock()->select(['id','name'])->get();
            DB::commit();

            $result=[
                'code'=>'success',
                'message'=>'请求成功',
                'sdata'=>[
                    'item'=>$this->item,
                    'landprops'=>$landprops,
                    'adminunits'=>$adminunits,
                ],
                'edata'=>null,
                'url'=>null];

            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('com.itemland.add')->with($result);
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
                'com_pic' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 赋值 ++++++++++ */
            DB::beginTransaction();
            try {
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
                   $itemland->com_pic=$request->input('com_pic');
                   $itemland->save();
               }
                if(blank($itemland)) {
                    throw new \Exception('添加失败', 404404);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemland;
                $edata = null;
                $url = route('c_itemland',['item'=>$item_id]);
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
                return view('com.error')->with($result);
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
                'landlayouts'=>$landlayouts,
                ];
            $edata=null;
            $url=null;

            $view='com.itemland.info';
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
                return view('com.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $itemland=Itemland::sharedLock()->find($id);
            $landprops=Landprop::with([
                'landsources'=>function($query){
                    $query->with(['landstates']);
                }])
                ->select(['id','name'])
                ->sharedLock()
                ->get();
            $adminunits=Adminunit::sharedLock()->select(['id','name'])->get();

            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($itemland)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='com.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=['itemland'=>$itemland,'landprops'=>$landprops,'adminunits'=>$adminunits,'item'=>$this->item];
                $edata=null;
                $url=null;

                $view='com.itemland.edit';
            }
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
                'com_pic' => 'required'
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
                $url=route('c_itemland_info',['id'=>$id,'item'=>$this->item_id]);

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

}