<?php
/*
|--------------------------------------------------------------------------
| 项目-选定评估机构
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Assets;
use App\Http\Model\Comassessvaluer;
use App\Http\Model\Company;
use App\Http\Model\Companyhousehold;
use App\Http\Model\Estate;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Itembuilding;
use App\Http\Model\Itemcompany;
use App\Http\Model\Itemland;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemcompanyController extends BaseitemController
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

        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $itemcompanys=Itemcompany::with(['company'=>function($query){
                    $query->select(['id','name']);
                }])
                ->withCount('households')
                ->where($where)
                ->orderBy('type','asc')
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->get();

            if(blank($itemcompanys)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$itemcompanys;
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
            return view('gov.itemcompany.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemcompany();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $type=$request->input('type');
                if(!is_numeric($type) || !in_array($type,[0,1])){
                    throw new \Exception('错误操作',404404);
                }
                $companys=Company::sharedLock()->where('type',$type)->get();
                if(blank($companys)){
                    throw new \Exception('没有可选的评估机构',404404);
                }
                $itemlands=Itemland::sharedLock()->select(['id','address'])->where('item_id',$item_id)->get();

                if(blank($itemlands)){
                    throw new \Exception('没有被征收户数据',404404);
                }

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item'=>$this->item,
                    'item_id'=>$item_id,
                    'type'=>$type,
                    'companys'=>$companys,
                    'itemlands'=>$itemlands,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemcompany.add';
            } catch (\Exception $exception) {
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
                'type'=>'required|boolean',
                'company_id'=>['required',Rule::unique('item_company')->where(function($query){
                    $query->where([
                        ['item_id',$this->item_id],
                        ['type',request()->input('type')],
                    ]);
                })],
                'household_ids'=>'required',
            ];
            $messages = [
                'required' => ':attribute 必须填写',
                'unique' => ':attribute 已存在',
                'household_ids.required' => '请选择被征收户',
                'boolean' => '错误操作',
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 【选定评估机构】 ++++++++++ */
                $itemcompany =$model;
                $itemcompany->fill($request->all());
                $itemcompany->item_id = $item_id;
                $itemcompany->save();
                if (blank($itemcompany)) {
                    throw new \Exception('添加失败', 404404);
                }
                /* ++++++++++ 【选定评估机构-评估范围】 ++++++++++ */
                $household_ids = $request->input('household_ids');
                $datas = [];
                foreach ($household_ids as $household_id){
                    $datas[]=[
                        'item_id'=>$item_id,
                        'company_id'=>$request->input('company_id'),
                        'item_company_id'=>$itemcompany->id,
                        'household_id'=>$household_id,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $field=['item_id','company_id','item_company_id','household_id','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_company_household',$field,$datas,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach ($sqls as $sql){
                    DB::statement($sql);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = ['itemcompany'=>$itemcompany];
                $edata = null;
                $url = route('g_itemcompany',['item'=>$item_id]);
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
        try{
            if(!$id){
                throw new \Exception('请先选择数据',404404);
            }
            /* ********** 当前数据 ********** */
            $itemcompany=Itemcompany::with([
                'company'=>function($query){
                    $query->select(['id','name']);
                },'households'=>function($query){
                    $query->with(['household'=>function($query){
                        $query->select(['id','land_id','building_id','unit','floor','number','type','username'])
                            ->with(['itemland'=>function($querys){
                                $querys->select(['id','address']);
                            },
                                'itembuilding'=>function($querys){
                                    $querys->select(['id','building']);
                                },
                                'householddetail'=>function($querys){
                                    $querys->select(['id','household_id','has_assets']);
                                }]);
                    }]);
                }])
                ->sharedLock()
                ->find($id);
            if(blank($itemcompany)){
                throw new \Exception('数据不存在',404404);
            }

            $code = 'success';
            $msg = '请求成功';
            $sdata = [
                'item_id'=>$this->item_id,
                'itemcompany'=>$itemcompany,
            ];
            $edata = null;
            $url = null;

            $view='gov.itemcompany.info';
        } catch (\Exception $exception) {
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

    /* ========== 修改 ========== */
    public function edit(Request $request){
        $item_id=$this->item_id;
        $model=new Itemcompany();
        $id =$request->input('id');
        if($request->isMethod('get')){
            try{
                if(!$id){
                    throw new \Exception('请先选择数据',404404);
                }
                /* ********** 当前数据 ********** */
                $itemcompany=Itemcompany::with([
                    'company'=>function($query){
                        $query->select(['id','name']);
                    },'households'=>function($query){
                        $query->with(['household'=>function($query){
                            $query->select(['id','land_id','building_id','unit','floor','number','type','username'])
                                ->with(['itemland'=>function($querys){
                                    $querys->select(['id','address']);
                                },
                                    'itembuilding'=>function($querys){
                                        $querys->select(['id','building']);
                                    },
                                    'householddetail'=>function($querys){
                                        $querys->select(['id','household_id','has_assets']);
                                    }]);
                        }]);
                    }])
                    ->sharedLock()
                    ->find($id);
                if(blank($itemcompany)){
                    throw new \Exception('数据不存在',404404);
                }
                $itemlands=Itemland::sharedLock()->select(['id','address'])->where('item_id',$item_id)->get();

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item_id'=>$this->item_id,
                    'itemcompany'=>$itemcompany,
                    'itemlands'=>$itemlands,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemcompany.edit';
            } catch (\Exception $exception) {
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
            /* ++++++++++ 修改 ++++++++++ */
            DB::beginTransaction();
            try {
                if(!$id){
                    throw new \Exception('错误操作',404404);
                }
                $household_ids = $request->input('household_ids');
                if(blank($household_ids)){
                    throw new \Exception('请选择被征收户',404404);
                }
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemcompany=Itemcompany::sharedLock()->find($id);
                if(blank($itemcompany)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 【选定评估机构-评估范围-删除旧数据】 ++++++++++ */
                Companyhousehold::lockForUpdate()
                    ->where('item_company_id',$id)
                    ->where('item_id',$item_id)
                    ->delete();
                /* ++++++++++ 【选定评估机构-评估范围-添加新数据】 ++++++++++ */
                $datas = [];
                foreach ($household_ids as $household_id){
                    $datas[]=[
                        'item_id'=>$item_id,
                        'company_id'=>$itemcompany->company_id,
                        'item_company_id'=>$itemcompany->id,
                        'household_id'=>$household_id,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $field=['item_id','company_id','item_company_id','household_id','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_company_household',$field,$datas,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach ($sqls as $sql){
                    DB::statement($sql);
                }

                $code = 'success';
                $msg = '修改成功';
                $sdata = $itemcompany;
                $edata = null;
                $url = route('g_itemcompany',['id'=>$id,'item'=>$item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '修改失败';
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

    /* ========== 评估委托书 ========== */
    public function pic(Request $request){
        $id =$request->input('id');
        if($request->isMethod('get')){
            try{
                if(!$id){
                    throw new \Exception('请先选择数据',404404);
                }
                /* ********** 当前数据 ********** */
                $itemcompany=Itemcompany::with([
                    'company'=>function($query){
                        $query->select(['id','name']);
                    }])
                    ->sharedLock()
                    ->find($id);
                if(blank($itemcompany)){
                    throw new \Exception('数据不存在',404404);
                }

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item_id'=>$this->item_id,
                    'itemcompany'=>$itemcompany,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemcompany.pic';
            } catch (\Exception $exception) {
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
            DB::beginTransaction();
            try {
                if(!$id){
                    throw new \Exception('错误操作',404404);
                }
                if(blank($request->input('picture'))){
                    throw new \Exception('请上传评估委托书',404404);
                }
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemcompany=Itemcompany::lockForUpdate()->find($id);
                if(blank($itemcompany)){
                    throw new \Exception('数据不存在',404404);
                }
                $itemcompany->fill($request->input());
                $itemcompany->save();

                $code = 'success';
                $msg = '保存成功';
                $sdata = $itemcompany;
                $edata = null;
                $url = route('g_itemcompany_info',['id'=>$id,'item'=>$this->item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '保存失败';
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


    /* ========== 【点击删除时调用】评估机构征收范围删除被征收户【检测删除的被征收户有无摸底数据及评估数据】 ========== */
    public function search_com_data(Request $request){
        $household_id = $request->input('household_id');
        $item_id = $this->item_id;
        $type = $request->input('type');
        if($type){
            /*--- 资产 ---*/
            $data_count = Assets::sharedLock()->where('household_id',$household_id)->where('item_id',$item_id)->count();
        }else{
            /*--- 房产 ---*/
            $data_count = Estate::sharedLock()->where('household_id',$household_id)->where('item_id',$item_id)->count();
        }

        if($data_count){
            $result=['code'=>'error','message'=>'存在评估数据','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }else{
            $result=['code'=>'success','message'=>'删除成功！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
    }

    /* ========== 【点击删除确认时调用】评估机构征收范围删除被征收户【删除被征收户摸底数据及评估数据】 ========== */
    public function del_com_data(Request $request){
        $type = $request->input('type');
        $household_id = $request->input('household_id');
        $company_id = $request->input('company_id');
        $item_id = $this->item_id;
        if($type){
            /*==========【删除房产资产评估端数据】=======*/
            DB::beginTransaction();
            try{
                /*---------- 删除评估记录 ----------*/
                $comassessvaluer_count = Comassessvaluer::where('household_id',$household_id)->where('estate_id',0)->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                if(blank($comassessvaluer_count)){
                    throw new \Exception('删除失败',404404);
                }
                /*---------- 删除资产评估 ----------*/
                $del_assets = Assets::where('household_id',$household_id)->where('item_id',$item_id)->forceDelete();
                if(blank($del_assets)){
                    throw new \Exception('删除失败',404404);
                }

                $code = 'success';
                $msg = '删除成功';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::commit();
            }catch (\Exception $exception){
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }else{
            /*==============【删除房产评估端数据】==============*/
            DB::beginTransaction();
            try{
                /*---------- 删除房产评估 ----------*/
                $del_estate = Estate::where('household_id',$household_id)->where('item_id',$item_id)->forceDelete();
                if(blank($del_estate)){
                    throw new \Exception('删除失败',404404);
                }
                /*---------- 删除房产建筑评估 ----------*/
                $estate_building_count = Estatebuilding::where('household_id',$household_id)->where('item_id',$item_id)->count();
                if($estate_building_count){
                    $del_estate_building = Estatebuilding::where('household_id',$household_id)->where('item_id',$item_id)->forceDelete();
                    if(blank($del_estate_building)){
                        throw new \Exception('删除失败',404404);
                    }
                }
                /*---------- 删除评估记录 ----------*/
                $comassessvaluer_count = Comassessvaluer::where('household_id',$household_id)->where('assets_id',0)->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                if(blank($comassessvaluer_count)){
                    throw new \Exception('删除失败',404404);
                }

                $code = 'success';
                $msg = '删除成功';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::commit();
            }catch (\Exception $exception){
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }

    }
}