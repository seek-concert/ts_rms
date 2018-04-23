<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户详情
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Buildinguse;
use App\Http\Model\Estate;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdobject;
use App\Http\Model\Itemuser;
use App\Http\Model\Layout;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HouseholddetailController extends BaseitemController
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
        $infos['item'] = $item;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $select=['id','item_id','land_id','building_id','household_id','has_assets','status','dispute','area_dispute'];
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
        /* ********** 资产评估 ********** */
        $has_assets=$request->input('has_assets');
        if(is_numeric($has_assets)){
            $where[] = ['has_assets',$has_assets];
            $infos['has_assets'] = $has_assets;
        }
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
        DB::beginTransaction();
        try{
            $total=Householddetail::sharedLock()
                ->where($where)
                ->count();
            $households=Householddetail::with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    }, 'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }, 'household'=>function($query){
                    $query->select(['id','unit','floor','number','type','username']);
                }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            if(blank($households)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('g_householddetail',['item'=>$item_id]));

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
            return view('gov.householddetail.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $model=new Householddetail();
        $household_id =$request->input('household_id');
        if($request->isMethod('get')){
            $sdata['household'] = Household::select(['id','land_id','building_id'])
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                ->where('id',$household_id)
                ->first();
            $sdata['defuse'] = Buildinguse::select(['id','name'])->get()?:[];
            $sdata['layout'] = Layout::select(['id','name'])->get()?:[];
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['detailmodel'] = $model;
            $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
            $sdata['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();

            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householddetail.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'household_id'=>'required',
                'land_id'=>'required',
                'building_id'=>'required',
                'status'=>'required',
                'dispute'=>'required',
                'area_dispute'=>'required',
                'real_use'=>'required',
                'has_assets'=>'required',
                'agree'=>'required',
                'repay_way'=>'required'
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
            /* ++++++++++ 新增 ++++++++++ */
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
                $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
                $rules=[];
                $messages=[];
                foreach ($file_cates as $file_cate){
                    $name='picture.'.$file_cate->filename;
                    $rules[$name]='required';
                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
                }
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $householddetail = $model;
                $householddetail->fill($request->all());
                $householddetail->addOther($request);
                $householddetail->item_id=$this->item_id;
                $householddetail->save();
                if (blank($householddetail)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $householddetail;
                $edata = null;
                $url = route('g_householddetail_info',['id'=>$household_id,'item'=>$this->item_id]);
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
        $item_id=$this->item_id;
        $item=$this->item;
        $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
        $data['detail_filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
        $file_table_id1=Filetable::where('name','item_household_member')->sharedLock()->value('id');
        $data['member_filecates']=Filecate::where('file_table_id',$file_table_id1)->sharedLock()->pluck('name','filename');
        /* ********** 当前数据 ********** */
        $data['item_id'] = $item_id;
        $data['item'] = $item;
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
            ->where('household_id',$id)
            ->first();
        DB::beginTransaction();
        $household=Household::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            }])
            ->sharedLock()
            ->find($id);
            $data['householdmember']=Householdmember::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'nation'=>function($query){
                    $query->select(['id','name']);
                },
                'householdmembercrowds'=>function($query){
                    $query->with([
                        'crowd'=>function($querys){
                            $querys->select(['id','name']);
                        }])
                        ->select(['id','member_id','crowd_id','picture']);
                }])
                ->where('item_id',$item_id)
                ->where('household_id',$id)
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
                ->where('item_id',$item_id)
                ->where('household_id',$id)
                ->sharedLock()
                ->get();
            $data['householdbuilding']=Householdbuilding::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'buildingstruct'=>function($query){
                    $query->select(['id','name']);
                }])
                ->where('item_id',$item_id)
                ->where('household_id',$id)
                ->sharedLock()
                ->get();
        $data['householdassets']=Householdassets::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            },
            ])
            ->where('item_id',$item_id)
            ->where('household_id',$id)
            ->sharedLock()
            ->get();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($household)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=$data;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$household;
            $edata=$data;
            $url=null;

            $view='gov.householddetail.info';
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
        $item_id=$this->item_id;
        $item=$this->item;
        $model=new Householddetail();
        $id =$request->input('id');
        if($request->isMethod('get')){
            $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
            $sdata['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
            $sdata['household'] = Householddetail::with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    },
                    'defbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    },
                    'realbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    },
                    'layout'=>function($query){
                        $query->select(['id','name']);
                    }])
                ->where('id',$id)
                ->first();
            $sdata['defuse'] = Buildinguse::select(['id','name'])->get()?:[];
            $sdata['layout'] = Layout::select(['id','name'])->get()?:[];
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $sdata['detailmodel'] = $model;
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householddetail.edit')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'status'=>'required',
                'dispute'=>'required',
                'area_dispute'=>'required',
                'real_use'=>'required',
                'has_assets'=>'required',
                'agree'=>'required',
                'repay_way'=>'required'
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
            /* ++++++++++ 修改 ++++++++++ */
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
                $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
                $rules=[];
                $messages=[];
                foreach ($file_cates as $file_cate){
                    $name='picture.'.$file_cate->filename;
                    $rules[$name]='required';
                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
                }
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $householddetail=Householddetail::lockForUpdate()->find($id);
                if(blank($householddetail)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $householddetail->fill($request->all());
                $householddetail->editOther($request);
                $householddetail->save();
                if (blank($householddetail)) {
                    throw new \Exception('修改失败', 404404);
                }

                $code = 'success';
                $msg = '修改成功';
                $sdata = $householddetail;
                $edata = null;
                $url = route('g_householddetail_info',['id'=>$request->input('household_id'),'item'=>$this->item_id]);
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
    /* ========================================== 【房产确认】============================================== */
    /* ========== 被征收户详情列表 ========== */
    public function buildingconfirm(Request $request){
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
        /* ********** 资产评估 ********** */
        $has_assets=$request->input('has_assets');
        if(is_numeric($has_assets)){
            $where[] = ['has_assets',$has_assets];
            $infos['has_assets'] = $has_assets;
        }
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
        $model=new Householddetail();
        DB::beginTransaction();
        try{
                $total=$model->sharedLock()
                    ->where('item_id',$item_id)
                    ->where($where)
                    ->count();
                $households=Householddetail::with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'itembuilding'=>function($query){
                            $query->select(['id','building']);
                        },
                        'household'=>function($query){
                            $query->select(['id','unit','floor','number','type','code']);
                        }])
                    ->withCount([
                        'householdbuildings','estatebuildings',
                        'estatebuildings as estatebuildings_where'=>function($query){
                            $query->where('household_building_id',0);
                        }])
                    ->where($where)
                    ->orderBy($ordername,$orderby)
                    ->sharedLock()
                    ->offset($per_page*($page-1))
                    ->limit($per_page)
                    ->get();
                $households=new LengthAwarePaginator($households,$total,$per_page,$page);
                $households->withPath(route('g_buildingconfirm',['item'=>$item_id]));

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
            return view('gov.buildingconfirm.index')->with($result);
        }
    }

    /* ========== 被征收户房产【征收-房屋建筑列表】 ========== */
    public function buildingrelated(Request $request){
        $item_id = $this->item_id;
        $item = $this->item;
        $household_id = $request->input('household_id');
        DB::beginTransaction();
        $householdbuilding =  Householdbuilding::with([
            'estatebuilding'=>function($query){
                $query->select(['id','household_building_id']);
            }])
            ->where('item_id',$item_id)
            ->where('household_id',$household_id)
            ->get();
        DB::commit();
        if(blank($householdbuilding)){
            $code = 'warning';
            $msg = '数据不存在';
            $sdata = null;
            $edata = ['item'=>$item,'item_id'=>$item_id];
            $url = null;
        }else{
            $code = 'success';
            $msg = '查询成功';
            $sdata = $householdbuilding;
            $edata = ['item'=>$item,'item_id'=>$item_id];
            $url = null;
        }

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.buildingconfirm.related')->with($result);
        }

    }

    /* ========== 被征收户房产【征收建筑关联评估建筑】 ========== */
    public function buildingrelated_com(Request $request)
    {
        $item_id = $this->item_id;
        $item = $this->item;
        $id = $request->input('id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('gov.error')->with($result);
            }
        }
        $household_id = $request->input('household_id');
        if($request->isMethod('get')){
            DB::beginTransaction();
            $estatebuilding =  Estatebuilding::where('item_id',$item_id)->where('household_id',$household_id)->where('household_building_id',0)->get();
            DB::commit();
            if(blank($estatebuilding)){
                $code = 'warning';
                $msg = '暂无相关数据';
                $sdata = null;
                $edata = ['item'=>$item,'item_id'=>$item_id,'household_building_id'=>$id,'household_id'=>$household_id];
                $url = null;
            }else{
                $code = 'success';
                $msg = '查询成功';
                $sdata = $estatebuilding;
                $edata = ['item'=>$item,'item_id'=>$item_id,'household_building_id'=>$id,'household_id'=>$household_id];
                $url = null;
            }

            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('gov.buildingconfirm.relatedcom')->with($result);
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
            DB::beginTransaction();
            try{
                $estatebuilding = new Estatebuilding();
                $estatebuilding->where('id',$id)->update(['household_building_id'=>$request->input('household_building_id'),'updated_at'=>date('Y-m-d H:i:s')]);
                if(blank($estatebuilding)){
                    throw new \Exception('关联失败',404404);
                }
                $code = 'success';
                $msg = '关联成功';
                $sdata = $estatebuilding;
                $edata = null;
                $url = route('g_buildingrelated',['item'=>$item_id,'household_id'=>$household_id]);
                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'关联失败';
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

    /* ========== 被征收户房产【征收建筑关联评估建筑详情】 ========== */
    public function relatedcom_info(Request $request){
        $item_id = $this->item_id;
        $item = $this->item;
        $household_id = $request->input('household_id');
        $id = $request->input('id');

        DB::beginTransaction();
        $estatebuilding =  Estatebuilding::sharedLock()->where('household_building_id',$id)->first();
        DB::commit();

        if(blank($estatebuilding)){
            $code = 'error';
            $msg = '数据不存在';
            $sdata = null;

        }else{
            $code = 'success';
            $msg = '查询成功';
            $sdata = $estatebuilding;
        }
        $edata = ['item'=>$item,'item_id'=>$item_id,'household_id'=>$household_id,'id'=>$id];
        $url = null;
        if($code == 'error'){
            $view = 'gov.error';
        }else{
            $view = 'gov.buildingconfirm.relatedinfo';
        }

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }



    /* ========== 被征收户房产详情 ========== */
    public function buildingconfirm_info(Request $request){

        $id=$request->input('id');
        $household_id=$request->input('household_id');
        if(blank($id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        $item_id=$this->item_id;
        $item=$this->item;
        /* ********** 当前数据 ********** */
        $data['detail_id'] = $id;
        $data['item_id'] = $item_id;
        $data['item'] = $item;
        $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
        $data['detail_filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
        $file_table_id1=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
        $data['com_filecates']=Filecate::where('file_table_id',$file_table_id1)->sharedLock()->pluck('name','filename');
        DB::beginTransaction();
        /*------------ 被征收户信息 ----------------*/
        $data['household_detail'] = Householddetail::with([
            'defbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'realbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'layout'=>function($query){
                $query->select(['id','name']);
            },
            'household'=>function($query){
                $query->select(['id','code']);
            }])
            ->where('household_id',$household_id)
            ->first();
        $data['estate']=Estate::with([
            'defbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'realbuildinguse'=>function($query){
                $query->select(['id','name']);
            }])
            ->where('item_id',$item_id)
            ->where('household_id',$request->input('household_id'))
            ->sharedLock()
            ->first();
        /*------------ 房屋建筑信息 ----------------*/
        $data['householdbuildings']=Householdbuilding::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            },
            'buildingstruct'=>function($query){
                $query->select(['id','name']);
            },
            'defbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'realbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'landlayout'=>function($query){
                $query->select(['id','name','area','gov_img']);
            }])
            ->where('item_id',$item_id)
            ->where('household_id',$household_id)
            ->sharedLock()
            ->get();
        $data['estatebuildings'] = Estatebuilding::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            },
            'buildingstruct'=>function($query){
                $query->select(['id','name']);
            },
            'defbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'realbuildinguse'=>function($query){
                $query->select(['id','name']);
            },
            'landlayout'=>function($query){
                $query->select(['id','name','area','gov_img']);
            }])
            ->where('item_id',$item_id)
            ->where('household_id',$household_id)
            ->sharedLock()
            ->get();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($data)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=null;
            $edata=$data;
            $url=null;

            $view='gov.buildingconfirm.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }


    /* ========== 被征收户房产确权状态修改 ========== */
    public function edit_status(Request $request){
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
        $item->process_id=25;
        $item->code='1';
        $item->save();

        $item_id =$this->item_id;
        $id = $request->input('gov_id');
        DB::beginTransaction();
        try{
            /* ********** 锁定数据 ********** */
            $household = Household::LockforUpdate()->find($id);
            if(blank($household)){
                throw new \Exception('没有符合条件的数据',404404);
            }

            /* ********** 修改 ********** */
            $household->code = 62;
            $household->save();
            if(blank($household)){
                throw new \Exception('数据异常,确权失败!',404404);
            }
            /*------------ 检测是否所有的都已经确权 ------------*/
            $household_code = $this->household_status($id);
            if($household_code){
                /*----------- 修改状态 ------------*/
                /* ++++++++++ 锁定数据 ++++++++++ */
                $household =  Household::lockForUpdate()->find($id);
                if(blank($household)){
                    throw new \Exception('暂无相关数据',404404);
                }
                $household->code = 63;
                $household->save();
                if(blank($household)){
                    throw new \Exception('修改失败',404404);
                }
                /* ++++++++++ 锁定数据 ++++++++++ */
                $estate =  Estate::lockForUpdate()->where('item_id',$item_id)->where('household_id',$id)->first();
                if(blank($estate)){
                    throw new \Exception('暂无相关数据',404404);
                }
                $estate->code = 130;
                $estate->save();
                if(blank($estate)){
                    throw new \Exception('修改失败',404404);
                }
            }


            $code = 'success';
            $msg = '确权成功';
            $sdata = $household;
            $edata = null;
            $url = route('g_buildingconfirm_info',['id'=>$request->input('detail_id'),'item'=>$item_id,'household_id'=>$id]);
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'确权失败';
            $sdata=null;
            $edata=null;
            $url=null;
            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }


}