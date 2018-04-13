<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户-资产
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Householddetail;
use App\Http\Model\Itemuser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdassetsController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $infos['item'] = $item;
        $household_id = $request->input('household_id');
        if(blank($household_id)){
            $result=['code'=>'error','message'=>'请先选择被征收户','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        $where[] = ['household_id',$household_id];
        $infos['household_id'] = $household_id;
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
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 查询 ********** */
        $model=new Householdassets();
        DB::beginTransaction();
        try{
            $householdassetss=$model
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                ->where($where)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->get();
            if(blank($householdassetss)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$householdassetss;
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
            return view('gov.householdassets.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $household_id=$request->input('household_id');
        if(blank($household_id)){
            $result=['code'=>'error','message'=>'请先选择被征收户','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        $model=new Householdassets();
        if($request->isMethod('get')){
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householdassets.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'household_id' => 'required',
                'land_id' => 'required',
                'building_id' => 'required',
                'name' => 'required',
                'num_unit' => 'required',
                'gov_num' => 'required',
                'gov_pic' => 'required'
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
                        ['process_id',27],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                $item->process_id=25;
                $item->code='1';
                $item->save();
                /* ++++++++++ 资产是否存在 ++++++++++ */
                $name = $request->input('name');
                $householdassets = Householdassets::where('item_id',$item_id)->where('land_id',$request->input('land_id'))->where('name',$name)->lockForUpdate()->first();
                if(blank($householdassets)){
                    /* ++++++++++ 新增数据 ++++++++++ */
                    $householdassets = $model;
                    $householdassets->fill($request->input());
                    $householdassets->addOther($request);
                    $householdassets->item_id=$item_id;
                    $householdassets->save();
                }else{
                    /* ++++++++++ 修改数据 ++++++++++ */
                    $householdassets->gov_num=$request->input('gov_num');
                    $householdassets->gov_pic=$request->input('gov_pic');
                    $householdassets->save();
                }
                if (blank($householdassets)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $householdassets;
                $edata = null;
                $url = route('g_householddetail_info',['id'=>$household_id,'item'=>$item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = $householdassets;
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
        $householdassets=Householdassets::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },'itembuilding'=>function($query){
                $query->select(['id','building']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdassets)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=['item'=>$this->item];
            $url=null;

            $view='gov.error';
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdassets;
            $edata=['item'=>$this->item];
            $url=null;

            $view='gov.householdassets.info';
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
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        $item_id = $this->item_id;
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */

            DB::beginTransaction();
            $householdassets=Householdassets::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($householdassets)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=['item'=>$this->item];
                $url=null;

                $view='gov.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata= $householdassets;
                $edata=['item'=>$this->item];
                $url=null;

                $view='gov.householdassets.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $household_id = $request->input('household_id');
            $model=new Householdassets();
            /* ********** 表单验证 ********** */
            $rules=[
                'name'=> ['required',Rule::unique('item_household_assets')->where(function ($query) use($item_id,$id,$household_id){
                    $query->where('item_id', $item_id)->where('household_id',$household_id)->where('id','<>',$id);
                })],
                'num_unit' => 'required',
                'gov_num' => 'required',
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
                        ['process_id',27],
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
                $householdassets=Householdassets::lockForUpdate()->find($id);
                if(blank($householdassets)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $householdassets->fill($request->all());
                $householdassets->editOther($request);
                $householdassets->save();
                if(blank($householdassets)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$householdassets;
                $edata=null;
                $url = route('g_householddetail_info',['id'=>$household_id,'item'=>$this->item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
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
                    ['process_id',27],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                throw new \Exception('您没有执行此操作的权限',404404);
            }
            /*---------是否正在被使用----------*/
            $householdassets = Householdassets::where('id',$ids)->first();
            if($householdassets->number){
                throw new \Exception('该条资产数据正在被使用,暂时不能被删除！',404404);
            }
            /*---------删除资产----------*/
            $householdassets = Householdassets::where('id',$ids)->forceDelete();
            if(!$householdassets){
                throw new \Exception('删除失败',404404);
            }

            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$householdassets;
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

    /* ========== 被征收户列表 ========== */
    public function report(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $infos['item'] = $item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;

        $where[] = ['has_assets',1];
        $infos['has_assets'] = 1;

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
        $ordername=$ordername?$ordername:'dispute';
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
            $total=Householddetail::sharedLock()
                ->where($where)
                ->count();
            $households=Householddetail::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->where($where)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('g_householdassets_report',['item'=>$item_id]));


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
            return view('gov.householdassets.report')->with($result);
        }
    }

    /* ========== 资产确认列表 ========== */
    public function reportlist(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $infos['item'] = $item;

        $household_id = $request->input('household_id');
        $where[] = ['household_id',$household_id];
        $infos['household_id'] = $household_id;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Householdassets();
        DB::beginTransaction();
        try{
            $total=Householdassets::sharedLock()
                ->where($where)
                ->count();
            $householdassetss=$model
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                ->where($where)
                ->orderBy('household_id','asc')
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $householdassetss=new LengthAwarePaginator($householdassetss,$total,$per_page,$page);
            $householdassetss->withPath(route('g_householdassets_reportlist',['item'=>$item_id]));
            if(blank($householdassetss)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$householdassetss;
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
            return view('gov.householdassets.reportlist')->with($result);
        }
    }

    /* ========== 确认资产 ========== */
    public function reportadd(Request $request){
        $id=$request->input('id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        $item_id = $this->item_id;
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */

            DB::beginTransaction();
            $householdassets=Householdassets::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($householdassets)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=['item'=>$this->item];
                $url=null;

                $view='gov.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata= $householdassets;
                $edata=['item'=>$this->item];
                $url=null;

                $view='gov.householdassets.reportadd';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
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

            $household_id = $request->input('household_id');
            $model=new Householdassets();
            /* ********** 表单验证 ********** */
            $rules=[
                'number' => 'required'
            ];
            $messages=[
                'required'=>':attribute必须填写'
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
                $householdassets=Householdassets::lockForUpdate()->find($id);
                if(blank($householdassets)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $householdassets->fill($request->all());
                $householdassets->editOther($request);
                $householdassets->save();
                if(blank($householdassets)){
                    throw new \Exception('修改失败',404404);
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
                    $household->code = 63;
                    $household->save();
                    if(blank($household)){
                        throw new \Exception('修改失败',404404);
                    }
                    /* ++++++++++ 锁定数据 ++++++++++ */
                    $estate =  Estate::lockForUpdate()->where('item_id',$item_id)->where('household_id',$household_id)->first();
                    if(blank($estate)){
                        throw new \Exception('暂无相关数据',404404);
                    }
                    $estate->code = 130;
                    $estate->save();
                    if(blank($estate)){
                        throw new \Exception('修改失败',404404);
                    }
                }


                $code='success';
                $msg='修改成功';
                $sdata=$householdassets;
                $edata=null;
                $url = route('g_householdassets_reportlist',['household_id'=>$household_id,'item'=>$this->item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 确认资产详情 ========== */
    public function reportinfo(Request $request){
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
        $householdassets=Householdassets::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdassets)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=['item'=>$this->item];
            $url=null;

            $view='gov.error';
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdassets;
            $edata=['item'=>$this->item];
            $url=null;

            $view='gov.householdassets.reportinfo';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }



}