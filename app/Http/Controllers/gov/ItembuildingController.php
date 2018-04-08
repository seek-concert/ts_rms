<?php
/*
|--------------------------------------------------------------------------
| 项目-地块楼栋
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Buildingstruct;
use App\Http\Model\Household;
use App\Http\Model\Householddetail;
use App\Http\Model\Itembuilding;
use App\Http\Model\Itemland;
use App\Http\Model\Itempublic;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItembuildingController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 查询地块下所有楼栋 ========== */
    public function index(Request $request){
        $item_id=$this->item_id;

        $land_id=$request->input('land_id');
        if(blank($land_id)){
            $result=['code'=>'error','message'=>'请先选择地块','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        $infos['item'] = $this->item;
        /* ********** 是否分页 ********** */
        $app = $request->input('app');
        /* ********** 查询条件 ********** */
        $where=[];
        $where[]=['item_id',$item_id];
        $infos['item_id']=$item_id;

        $where[]=['land_id',$land_id];
        $infos['land_id']=$land_id;
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Itembuilding();
        DB::beginTransaction();
        try{
            /* ********** 是否分页 ********** */
            if(blank($app)){
                $total=$model->sharedLock()
                    ->where('item_id',$item_id)
                    ->where($where)
                    ->count();
                $itembuildings=$model
                    ->with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'buildingstruct'=>function($query){
                            $query->select(['id','name']);
                        }])
                    ->where($where)
                    ->orderBy($ordername,$orderby)
                    ->sharedLock()
                    ->offset($per_page*($page-1))
                    ->limit($per_page)
                    ->get();
                $itembuildings=new LengthAwarePaginator($itembuildings,$total,$per_page,$page);
                $itembuildings->withPath(route('g_itembuilding',['item'=>$item_id]));
            }else{
                $itembuildings=$model
                    ->with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'buildingstruct'=>function($query){
                            $query->select(['id','name']);
                        }])
                    ->where($where)
                    ->orderBy($ordername,$orderby)
                    ->sharedLock()
                    ->get();
            }

            if(blank($itembuildings)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$itembuildings;
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
        return response()->json($result);
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $land_id=$request->input('land_id');
        if(blank($land_id)){
            $result=['code'=>'error','message'=>'请先选择地块','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        $model=new Itembuilding();
        if($request->isMethod('get')){
            $itemland = Itemland::sharedLock()->find($land_id);
            $buildingstructs=Buildingstruct::sharedLock()->select(['id','name'])->get();
            if(blank($itemland)){
                $result=['code'=>'error','message'=>'数据不存在','sdata'=>null,'edata'=>null,'url'=>null];
                if($request->ajax()){
                    return response()->json($result);
                }else{
                    return view('gov.error')->with($result);
                }
            }
            $result=[
                'code'=>'success',
                'message'=>'请求成功',
                'sdata'=>[
                    'item'=>$this->item,
                    'itemland'=>$itemland,
                    'buildingstructs'=>$buildingstructs,
                ],
                'edata'=>null,
                'url'=>null];

            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.itembuilding.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            $land_id = $request->input('land_id');
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'land_id' => 'required',
                'building' => 'required',
                'total_floor' => 'required',
                'area' => 'required',
                'build_year' => 'required',
                'struct_id' => 'required',
                'gov_pic' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写',
                'unique'=>':attribute已经存在'
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

                /* ++++++++++ 楼栋是否存在 ++++++++++ */
                $building = $request->input('building');
                $itembuilding = Itembuilding::where('item_id',$item_id)->where('land_id',$land_id)->where('building',$building)->lockForUpdate()->first();
                if(blank($itembuilding)){
                    /* ++++++++++ 新增数据 ++++++++++ */
                    $itembuilding = $model;
                    $itembuilding->fill($request->input());
                    $itembuilding->addOther($request);
                    $itembuilding->item_id=$item_id;
                    $itembuilding->save();
                }else{
                    /* ++++++++++ 修改数据 ++++++++++ */
                    $itembuilding->gov_pic=$request->input('gov_pic');
                    $itembuilding->save();
                }

                if (blank($itembuilding)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $itembuilding;
                $edata = null;
                $url = route('g_itemland_info',['id'=>$land_id,'item'=>$item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = $itembuilding;
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
        $id=$request->input('id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        DB::beginTransaction();
        $itembuilding=Itembuilding::with(['itemland'=>function($query){
            $query->select(['id','address']);
        },'buildingstruct'=>function($query){
            $query->select(['id','name']);
        }])
            ->sharedLock()
            ->find($id);

        if(blank($itembuilding)){
            $result=['code'=>'error','message'=>'数据不存在','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        $itempublics=Itempublic::sharedLock()
            ->where('item_id',$this->item_id)
            ->where('land_id',$itembuilding->land_id)
            ->where('building_id',$id)
            ->get();
        DB::commit();

        $result=[
            'code'=>'success',
            'message'=>'获取成功',
            'sdata'=>[
                'item'=>$this->item,
                'itembuilding'=>$itembuilding,
                'itempublics'=>$itempublics,
            ],
            'edata'=>null,
            'url'=>null];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view('gov.itembuilding.info')->with($result);
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
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $itembuilding=Itembuilding::with(['itemland'=>function($query){
                $query->select(['id','address']);
            }])
                ->sharedLock()
                ->find($id);

            $buildingstructs=Buildingstruct::sharedLock()->select(['id','name'])->get();
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($itembuilding)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=['item'=>$this->item,'itembuilding'=>$itembuilding,'buildingstructs'=>$buildingstructs];
                $edata=null;
                $url=null;

                $view='gov.itembuilding.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Itembuilding();
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
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itembuilding=Itembuilding::lockForUpdate()->find($id);
                if(blank($itembuilding)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                $land_id=$itembuilding->land_id;
                /* ********** 表单验证 ********** */
                $rules=[
                    'building' =>['required',Rule::unique('item_building')->where(function ($query) use($item_id,$land_id,$id){
                        $query->where('land_id', $land_id)->where('item_id', $item_id)->where('id','<>',$id);
                    })],
                    'total_floor' => 'required',
                    'area' => 'required',
                    'build_year' => 'required',
                    'struct_id' => 'required',
                    'gov_pic' => 'required'
                ];
                $messages=[
                    'required'=>':attribute必须填写',
                    'unique'=>':attribute已存在'
                ];
                $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
                if ($validator->fails()) {
                    throw new \Exception($validator->errors()->first(),404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $itembuilding->fill($request->input());
                $itembuilding->editOther($request);
                $itembuilding->save();
                if(blank($itembuilding)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$itembuilding;
                $edata=null;
                $url = route('g_itembuilding_info',['id'=>$id,'item'=>$item_id]);
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$itembuilding;
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
            $itempublic = Itempublic::where('building_id',$ids)->count();
            if($itempublic!=0){
                throw new \Exception('该楼栋下含有楼栋公共附属物,暂时不能被删除！',404404);
            }
            $household = Household::where('building_id',$ids)->count();
            if($household!=0){
                throw new \Exception('该楼栋正在被使用,暂时不能被删除！',404404);
            }
            /*---------公共附属物----------*/
            $itempublic = Itempublic::where('id',$ids)->delete();
            if(!$itempublic){
                throw new \Exception('删除失败',404404);
            }
            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$itempublic;
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