<?php
/*
|--------------------------------------------------------------------------
| 项目-地块户型
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Household;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Itemland;
use App\Http\Model\Itemuser;
use App\Http\Model\Landlayout;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LandlayoutController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 查询地块下所有户型 ========== */
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
        /* ********** 查询条件 ********** */
        $where=[];
        $select=['id','item_id','land_id','name','area'];

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
        $model=new Landlayout();
        DB::beginTransaction();
        try{
            $total=$model->sharedLock()
                ->where('item_id',$item_id)
                ->where($where)
                ->count();
            $landlayouts=$model
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $landlayouts=new LengthAwarePaginator($landlayouts,$total,$per_page,$page);
            $landlayouts->withPath(route('g_landlayout',['item'=>$item_id]));

            if(blank($landlayouts)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$landlayouts;
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
            return view('gov.landlayout.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;

        $model=new landlayout();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $land_id=$request->input('land_id');
                $itemland=Itemland::sharedLock()->find($land_id);
                if(blank($itemland)){
                    throw new \Exception('数据错误', 404404);
                }
                $code='success';
                $msg='获取成功';
                $sdata=[
                    'item'=>$this->item,
                    'itemland'=>$itemland
                ];
                $edata=null;
                $url=null;

                $view='gov.landlayout.add';
            }catch (\Exception $exception){
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
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
                'land_id' => 'required',
                'name' => 'required',
                'gov_img' => 'required'
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
                /* ++++++++++ 地块户型是否存在 ++++++++++ */
                $name = $request->input('name');
                $landlayout = Landlayout::where('item_id',$item_id)->where('land_id',$request->input('land_id'))->where('name',$name)->lockForUpdate()->first();
                if(blank($landlayout)){
                    /* ++++++++++ 新增数据 ++++++++++ */
                    $landlayout = $model;
                    $landlayout->fill($request->input());
                    $landlayout->addOther($request);
                    $landlayout->item_id=$item_id;
                    $landlayout->save();
                }else{
                    /* ++++++++++ 修改数据 ++++++++++ */
                    $landlayout->area=$request->input('area');
                    $landlayout->gov_img=$request->input('gov_img');
                    $landlayout->save();
                }

                if (blank($landlayout)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $landlayout;
                $edata = null;
                $url = route('g_itemland_info',['id'=>$request->input('land_id'),'item'=>$item_id]);
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
        $landlayout=Landlayout::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($landlayout)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;

            $view='gov.error';
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=['item'=>$this->item,'landlayout'=>$landlayout];
            $edata=null;
            $url=null;

            $view='gov.landlayout.info';
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

        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */

            DB::beginTransaction();
            $landlayout=Landlayout::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($landlayout)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=['item'=>$this->item,'landlayout'=>$landlayout];
                $edata=null;
                $url=null;

                $view='gov.landlayout.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Landlayout();
            /* ********** 表单验证 ********** */
            $rules=[
                'name' => 'required',
                'gov_img' => 'required'
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
                $landlayout=Landlayout::lockForUpdate()->find($id);
                if(blank($landlayout)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $landlayout->fill($request->all());
                $landlayout->editOther($request);
                $landlayout->save();
                if(blank($landlayout)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$landlayout;
                $edata=null;
                 $url = route('g_itemland_info',['id'=>$landlayout->land_id,'item'=>$this->item_id]);


                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$landlayout;
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
            /*---------是否在使用----------*/
            $householdbuilding_state = Householdbuilding::where('layout_id',$ids)->count();
            if($householdbuilding_state!=0){
                throw new \Exception('该地块户型正在被使用,暂时不能被删除！',404404);
            }

            $estatebuilding = Estatebuilding::where('layout_id',$ids)->count();
            if($estatebuilding!=0){
                throw new \Exception('该地块户型正在被使用,暂时不能被删除！',404404);
            }
            /*---------是否在已出测绘报告----------*/
            $landlayout = Landlayout::where('id',$ids)->first();
            if($landlayout->picture){
                throw new \Exception('该地块户型正在被使用,暂时不能被删除！',404404);
            }
            /*---------地块户型----------*/
            $landlayout = Landlayout::where('id',$ids)->forceDelete();
            if(!$landlayout){
                throw new \Exception('删除失败',404404);
            }
            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$landlayout;
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

    /* ========== 测绘报告列表 ========== */
    public function reportlist(Request $request){
        $infos['item'] = $this->item;
        /* ********** 查询条件 ********** */
        $where=[];

        $item_id=$this->item_id;
        $where[]=['item_id',$item_id];
        $infos['item_id']=$item_id;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Landlayout();
        DB::beginTransaction();
        try{
            $total=$model->sharedLock()
                ->where('item_id',$item_id)
                ->where($where)
                ->count();
            $landlayouts=$model
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    }])
                ->where($where)
                ->orderBy('land_id','desc')
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $landlayouts=new LengthAwarePaginator($landlayouts,$total,$per_page,$page);
            $landlayouts->withPath(route('g_landlayout_reportlist',['item'=>$item_id]));

            if(blank($landlayouts)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$landlayouts;
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
            return view('gov.landlayout.reportlist')->with($result);
        }
    }

    /* ========== 添加测绘报告 ========== */
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

        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */

            DB::beginTransaction();
            $landlayout=Landlayout::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($landlayout)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=['item'=>$this->item,'landlayout'=>$landlayout];
                $edata=null;
                $url=null;

                $view='gov.landlayout.reportadd';
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

            $model=new Landlayout();
            /* ********** 表单验证 ********** */
            $rules=[
                'area' => 'required',
                'picture' => 'required'
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
                $landlayout=Landlayout::lockForUpdate()->find($id);
                if(blank($landlayout)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $landlayout->fill($request->all());
                $landlayout->editOther($request);
                $landlayout->save();
                if(blank($landlayout)){
                    throw new \Exception('提交失败',404404);
                }
                $code='success';
                $msg='提交成功';
                $sdata=$landlayout;
                $edata=null;
                $url = route('g_landlayout_reportlist',['item'=>$this->item_id]);


                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$landlayout;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 详情 ========== */
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
        $landlayout=Landlayout::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($landlayout)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;

            $view='gov.error';
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=['item'=>$this->item,'landlayout'=>$landlayout];
            $edata=null;
            $url=null;

            $view='gov.landlayout.reportinfo';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }


}