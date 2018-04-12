<?php
/*
|--------------------------------------------------------------------------
| 房源社区户型图
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\House;
use App\Http\Model\Housecommunity;
use App\Http\Model\Houselayoutimg;
use App\Http\Model\Layout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouselayoutimgController extends BaseauthController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $select=['id','community_id','layout_id','picture','name','deleted_at'];

        /* ********** 查询条件 ********** */
        $where=[];
        /* ++++++++++ 房源社区 ++++++++++ */
        $community_id=$request->input('community_id');
        if(is_numeric($community_id)){
            $where[]=['community_id',$community_id];
            $infos['community_id']=$community_id;
        }
        /* ++++++++++ 户型 ++++++++++ */
        $layout_id=$request->input('layout_id');
        if(is_numeric($layout_id)){
            $where[]=['layout_id',$layout_id];
            $infos['layout_id']=$layout_id;
        }
        /* ++++++++++ 名称 ++++++++++ */
        $name=trim($request->input('name'));
        if($name){
            $where[]=['name','like','%'.$name.'%'];
            $infos['name']=$name;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $displaynum=$request->input('displaynum');
        $displaynum=$displaynum?$displaynum:15;
        $infos['displaynum']=$displaynum;
        /* ********** 是否删除 ********** */
        $deleted=$request->input('deleted');

        $model=new Houselayoutimg();
        if(is_numeric($deleted) && in_array($deleted,[0,1])){
            $infos['deleted']=$deleted;
            if($deleted==0){
                $model=$model->onlyTrashed();
            }
        }
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $houselayoutimgs=$model->with(
                ['housecommunity'=> function ($query) {
                    $query->withTrashed()->select(['id', 'name']);
                  },
                  'layout'=> function ($query) {
                     $query->withTrashed()->select(['id', 'name']);
                   }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->paginate($displaynum);
            if(blank($houselayoutimgs)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$houselayoutimgs;
            $edata=null;
            $url=null;
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.houselayoutimg.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $model=new Houselayoutimg();
        if($request->isMethod('get')){
            $sdata['housecommunity'] = Housecommunity::select(['id','name'])->get();
            $sdata['layout'] = Layout::select(['id','name'])->get();
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.houselayoutimg.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $community_id = $request->input('community_id');
            $layout_id = $request->input('layout_id');
            $rules = [
                'name' => ['required',Rule::unique('house_layout_img')->where(function ($query) use($community_id,$layout_id){
                    $query->where('community_id', $community_id)->where('layout_id',$layout_id);
                })],
                'picture' => 'required',
            ];
            $messages = [
                'required' => ':attribute 为必须项',
                'unique' => ':attribute 已存在'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 批量赋值 ++++++++++ */
                $houselayoutimg = $model;
                $houselayoutimg->fill($request->input());
                $houselayoutimg->addOther($request);
                $houselayoutimg->save();
                if (blank($houselayoutimg)) {
                    throw new \Exception('添加失败', 404404);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = $houselayoutimg;
                $edata = null;
                $url = route('g_houselayoutimg');
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
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $houselayoutimg=Houselayoutimg::withTrashed()
            ->with(['housecommunity'=> function ($query) {
                    $query->withTrashed()->select(['id', 'name']);
                },
                    'layout'=> function ($query) {
                        $query->withTrashed()->select(['id', 'name']);
                }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($houselayoutimg)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$houselayoutimg;
            $edata=new Houselayoutimg();
            $url=null;

            $view='gov.houselayoutimg.info';
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
        if(!$id){
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
            $houselayoutimg=Houselayoutimg::withTrashed()
                ->with(['housecommunity'=> function ($query) {
                    $query->withTrashed()->select(['id', 'name']);
                },
                    'layout'=> function ($query) {
                        $query->withTrashed()->select(['id', 'name']);
                    }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($houselayoutimg)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=$houselayoutimg;
                $edata=new Houselayoutimg();
                $url=null;

                $view='gov.houselayoutimg.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $community_id = $request->input('community_id');
            $layout_id = $request->input('layout_id');
            $model=new Houselayoutimg();
            /* ********** 表单验证 ********** */
            $rules=[
                'name' => ['required',Rule::unique('house_layout_img')->where(function ($query) use($community_id,$layout_id,$id){
                $query->where('community_id', $community_id)->where('layout_id',$layout_id)->where('id','<>',$id);
            })],
                'picture' => 'required',
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'unique'=>':attribute 已存在'
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
                $houselayoutimg=Houselayoutimg::withTrashed()
                    ->lockForUpdate()
                    ->find($id);
                if(blank($houselayoutimg)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $houselayoutimg->fill($request->input());
                $houselayoutimg->editOther($request);
                $houselayoutimg->save();
                if(blank($houselayoutimg)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$houselayoutimg;
                $edata=null;
                $url=route('g_houselayoutimg');
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$houselayoutimg;
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
            $houselayoutimg_state = House::where('layout_img_id',$ids)->count();
            if($houselayoutimg_state){
                throw new \Exception('本条数据正在被房源使用,暂时不能被删除！',404404);
            }

            $houselayoutimg = Houselayoutimg::where('id',$ids)->forceDelete();
            if(!$houselayoutimg){
                throw new \Exception('删除失败',404404);
            }
            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$houselayoutimg;
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