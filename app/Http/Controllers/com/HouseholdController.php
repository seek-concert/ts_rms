<?php
/*
|--------------------------------------------------------------------------
| 入户摸底
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Assess;
use App\Http\Model\Assets;
use App\Http\Model\Buildingstruct;
use App\Http\Model\Buildinguse;
use App\Http\Model\Comassessvaluer;
use App\Http\Model\Companyvaluer;
use App\Http\Model\Estate;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Companyhousehold;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Landlayout;
use App\Http\Model\Layout;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ++++++++++ 获取入户摸底资料 ++++++++++ */
    public function index(Request $request)
    {
        $infos = [];
        $where = [];
       $item_id = $this->item_id;
        $infos['item_id'] = $item_id;
        $where[] = ['item_id',$item_id];
        $infos['type'] = session('com_user.type');
        $company_id = session('com_user.company_id');
        $infos['company_id'] = $company_id;
        $where[] = ['company_id',$company_id];
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $total=Companyhousehold::sharedLock()
                ->where($where)
                ->count();
            $households = Companyhousehold::with([
                'household'=>function($querys){
                    $querys->with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'itembuilding'=>function($query){
                            $query->select(['id','building']);
                        },
                        'householddetail'=>function($query){
                            $query->select(['id','household_id','dispute']);
                        }]);
                }])
                ->where($where)
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('c_household',['item'=>$item_id]));
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
            return view('com.household.index')->with($result);
        }
    }

    /* ++++++++++ 入户摸底详情 ++++++++++ */
    public function info(Request $request){
        $item_id = $this->item_id;
        $type = session('com_user.type');
        $company_id = session('com_user.company_id');
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
        try{
            /*------------- 基本信息 -------------*/
            $household=Household::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'householddetail'=>function($query){
                    $query->select(['id','household_id','register','reg_inner','reg_outer',
                        'balcony','dispute','picture','def_use','real_use',
                        'has_assets','agree','repay_way','layout_id']);
                }])
                ->sharedLock()
                ->find($id);
            if(blank($household)){
                throw new \Exception('数据异常',404404);
            }
            /*------------- [房产][资产]信息 -------------*/
            if($type==0){
                $data['estate']=Estate::with([
                    'defbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    },
                    'realbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    },
                    'state'=>function($query){
                        $query->select(['id','code','name']);
                    }])
                    ->where('item_id',$item_id)
                    ->where('household_id',$id)
                    ->where('company_id',$company_id)
                    ->sharedLock()
                    ->first();

                $data['buildings'] = Estatebuilding::where('item_id',$item_id)
                                        ->where('household_id',$id)
                                        ->where('company_id',$company_id)
                                        ->get();
                $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
                $data['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
            }else{
                $data['householdassets'] = Householdassets::where('item_id',$item_id)
                    ->where('household_id',$id)
                    ->get();
            }

            $data['type'] = $type;
            $code='success';
            $msg='获取成功';
            $sdata=$household;
            $edata=$data;
            $url=null;

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url='';
            DB::rollBack();
        }
        if($code=='error'){
            $view='com.error';
        }else{
            $view='com.household.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /* ++++++++++ 入户摸底-添加[房产][资产] ++++++++++ */
    public function add(Request $request){
        $type = session('com_user.type');
        $company_id = session('com_user.company_id');
        $item_id=$this->item_id;
        $item=$this->item;
        $household_id =$request->input('household_id');

        if($request->isMethod('get')){
            $sdata['type'] = $type;
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
            $sdata['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
            if($type==0){
                $model=new Estate();
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
                $sdata['models'] = $model;
            }else{
                $model=new Householdassets();
                $sdata['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            }

            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('com.household.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            if($type==0){
                $model=new Estate();
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
                    'sign'=>'required'
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
                $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
                $rules=[];
                $messages=[];
                foreach ($file_cates as $file_cate){
                    $name='house_pic.'.$file_cate->filename;
                    $rules[$name]='required';
                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
                }
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }

                /* ++++++++++ 新增 ++++++++++ */
                DB::beginTransaction();
                try {
                    /* ++++++++++ 批量赋值 ++++++++++ */
                    $household = $model;
                    $household->fill($request->all());
                    $household->addOther($request);
                    $household->item_id=$this->item_id;
                    $household->assess_id=0;
                    $household->company_id=$company_id;
                    $household->code=0;
                    $household->save();
                    if (blank($household)) {
                        throw new \Exception('添加失败', 404404);
                    }

                    $code = 'success';
                    $msg = '添加成功';
                    $sdata = $household;
                    $edata = null;
                    $url = route('c_household_info',['id'=>$household_id,'item'=>$item_id]);
                    DB::commit();
                } catch (\Exception $exception) {
                    $code = 'error';
                    $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                    $sdata = null;
                    $edata = null;
                    $url = null;
                    DB::rollBack();
                }
            }else{
                $model=new Householdassets();
                /* ++++++++++ 表单验证 ++++++++++ */
                $rules = [
                    'household_id' => 'required',
                    'land_id' => 'required',
                    'building_id' => 'required',
                    'name' => 'required',
                    'num_unit' => 'required',
                    'com_num' => 'required',
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
                        $householdassets->com_num=$request->input('com_num');
                        $householdassets->com_pic=$request->input('com_pic');
                        $householdassets->save();
                    }
                    if (blank($householdassets)) {
                        throw new \Exception('添加失败', 404404);
                    }

                    $code = 'success';
                    $msg = '添加成功';
                    $sdata = $householdassets;
                    $edata = null;
                    $url = route('c_household_info',['id'=>$household_id,'item'=>$item_id]);
                    DB::commit();
                } catch (\Exception $exception) {
                    $code = 'error';
                    $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                    $sdata = null;
                    $edata = null;
                    $url = null;
                    DB::rollBack();
                }
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ++++++++++ 修改摸底信息 ++++++++++ */
    public function edit(Request $request){
        $id = $request->input('id');
        $household_id = $request->input('household_id');
        $item_id = $this->item_id;
        $item = $this->item;
        $type = session('com_user.type');
        $company_id = session('com_user.company_id');

        if($request->isMethod('get')){
            $data['type'] = $type;
            $data['company_id'] = $company_id;
            $data['item_id'] = $item_id;
            $data['item'] = $item;
            if($type==0){
                $model = new Estate();
                $data['household'] = Household::select(['id','land_id','building_id'])
                    ->with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'itembuilding'=>function($query){
                            $query->select(['id','building']);
                        }])
                    ->where('id',$household_id)
                    ->first();
                $data['defuse'] = Buildinguse::select(['id','name'])->get()?:[];
                $data['models'] = $model;
                $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
                $data['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
                DB::beginTransaction();
                   $estate = Estate::with([
                       'itemland'=>function($query){
                           $query->select(['id','address']);
                       },
                       'itembuilding'=>function($query){
                           $query->select(['id','building']);
                       }])
                       ->sharedLock()
                       ->find($id);
                DB::commit();
                if(blank($estate)){
                    $code = 'error';
                    $msg = '数据异常';
                    $sdata = null;
                    $edata = $data;
                    $url = null;
                }else{
                    $code = 'success';
                    $msg = '获取成功';
                    $sdata = $estate;
                    $edata = $data;
                    $url = null;
                }

            }else{
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
                    $edata=$data;
                    $url=null;
                }else{
                    $code='success';
                    $msg='获取成功';
                    $sdata= $householdassets;
                    $edata=$data;
                    $url=null;
                }
            }

            if($code=='error'){
                $view='com.error';
            }else{
                $view='com.household.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            /* ********** 保存 ********** */
            if($type==0){
                $model = new Estate();
                /* ++++++++++ 表单验证 ++++++++++ */
                $rules = [
                    'household_id'=>'required',
                    'status'=>'required',
                    'dispute'=>'required',
                    'area_dispute'=>'required',
                    'real_use'=>'required',
                    'has_assets'=>'required',
                    'sign'=>'required'
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

                $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
                $rules=[];
                $messages=[];
                foreach ($file_cates as $file_cate){
                    $name='house_pic.'.$file_cate->filename;
                    $rules[$name]='required';
                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
                }
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
                /* ++++++++++ 修改 ++++++++++ */
                DB::beginTransaction();
                try {
                    /* ++++++++++ 锁定数据 ++++++++++ */
                    $estate = Estate::lockforupdate()->find($id);
                    /* ++++++++++ 批量赋值 ++++++++++ */
                    $estate->fill($request->all());
                    $estate->editOther($request);
                    $estate->save();
                    if (blank($estate)) {
                        throw new \Exception('修改失败', 404404);
                    }

                    $code = 'success';
                    $msg = '修改成功';
                    $sdata = $estate;
                    $edata = null;
                    $url = route('c_household_info',['id'=>$household_id,'item'=>$item_id]);
                    DB::commit();
                } catch (\Exception $exception) {
                    $code = 'error';
                    $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '修改失败';
                    $sdata = null;
                    $edata = null;
                    $url = null;
                    DB::rollBack();
                }
            }else{
                $model=new Householdassets();
                /* ********** 表单验证 ********** */
                $rules=[
                    'name'=> ['required',Rule::unique('item_household_assets')->where(function ($query) use($item_id,$id,$household_id){
                        $query->where('item_id', $item_id)->where('household_id',$household_id)->where('id','<>',$id);
                    })],
                    'num_unit' => 'required',
                    'com_num' => 'required',
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
                    $url = route('c_household_info',['id'=>$household_id,'item'=>$this->item_id]);

                    DB::commit();
                }catch (\Exception $exception){
                    $code='error';
                    $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                    $sdata=null;
                    $edata=null;
                    $url=null;
                    DB::rollBack();
                }
            }

            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }

    }

    /* =========================================== 【房产】房屋建筑 ===================================== */
    /* ++++++++++ 添加房屋建筑 ++++++++++ */
    public function buildingadd(Request $request){
        $item_id=$this->item_id;
        $company_id = session('com_user.company_id');
        $household_id=$request->input('household_id');
        $model=new Estatebuilding();
        if($request->isMethod('get')){
            $sdata['buildingstruct'] = Buildingstruct::select(['id','name'])->get()?:[];
            $sdata['defbuildinguse'] = Buildinguse::select(['id','name'])->get()?:[];
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $this->item;
            $sdata['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            $sdata['landlayouts'] = Landlayout::select(['id','item_id','land_id','name','area'])->where('item_id',$item_id)->where('land_id',$sdata['household']->land_id)->get()?:[];
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('com.householdbuilding.add')->with($result);
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
                'code' => 'required',
                'reg_outer' => 'required',
                'real_outer' => 'required',
                'def_use' => 'required',
                'real_use' => 'required',
                'struct_id' => 'required',
                'direct' => 'required',
                'floor' => 'required',
                'build_year' => 'required',
                'picture' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 被征户房屋建筑-批量赋值 ++++++++++ */
                $householdbuilding = $model;
                $householdbuilding->fill($request->all());
                $householdbuilding->addOther($request);
                $householdbuilding->item_id=$item_id;
                $householdbuilding->company_id=$company_id;
                $householdbuilding->assess_id=0;
                $householdbuilding->estate_id=0;
                $householdbuilding->household_building_id=0;
                $householdbuilding->save();
                if (blank($householdbuilding)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $householdbuilding;
                $edata = null;
                $url = route('c_household_info',['id'=>$household_id,'item'=>$item_id]);
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

    /* ========== 房屋建筑详情 ========== */
    public function buildinginfo(Request $request){
        $id=$request->input('id');
        if(!$id){
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
        $data['item_id'] = $item_id;
        $data['item'] = $item;

        DB::beginTransaction();
        $householdbuilding=Estatebuilding::with([
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
            },
            'state'=>function($query){
                $query->select(['id','code','name']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdbuilding)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=$data;
            $url=null;
            $view='com.error';
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdbuilding;
            $edata=$data;
            $url=null;

            $view='com.householdbuilding.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /* ========== 修改房屋建筑详情 ========== */
    public function buildingedit(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        $item_id=$this->item_id;
        $household_id = $request->input('household_id');
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            $data['buildingstruct'] = Buildingstruct::select(['id','name'])->get()?:[];
            $data['defbuildinguse'] = Buildinguse::select(['id','name'])->get()?:[];
            $data['item_id'] = $item_id;
            $data['household'] = Household::select(['id','land_id','building_id'])->find($household_id);
            $data['landlayouts'] = Landlayout::select(['id','item_id','land_id','name','area'])->where('item_id',$item_id)->where('land_id',$data['household']->land_id)->get()?:[];
            DB::beginTransaction();
            $householdbuilding=Estatebuilding::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'landlayout'=>function($query){
                    $query->select(['id','name','area','gov_img']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();

            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($householdbuilding)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=$data;
                $url=null;
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=$householdbuilding;
                $edata=$data;
                $url=null;

                $view='com.householdbuilding.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Household();
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'reg_outer' => 'required',
                'real_outer' => 'required',
                'def_use' => 'required',
                'real_use' => 'required',
                'struct_id' => 'required',
                'direct' => 'required',
                'floor' => 'required',
                'build_year' => 'required',
                'picture' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                /* ++++++++++ 被征户房屋建筑-锁定数据模型 ++++++++++ */
                $estatebuilding=Estatebuilding::lockForUpdate()->find($id);
                if(blank($estatebuilding)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 被征户房屋建筑-处理其他数据 ++++++++++ */
                $estatebuilding->fill($request->all());
                $estatebuilding->editOther($request);
                $estatebuilding->code=$request->code;
                $estatebuilding->save();
                if(blank($estatebuilding)){
                    throw new \Exception('修改失败',404404);
                }

                $code='success';
                $msg='修改成功';
                $sdata=$estatebuilding;
                $edata=null;
                $url = route('c_household_info',['id'=>$household_id,'item'=>$item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$estatebuilding;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 删除房屋建筑详情 ========== */
    public function buildingdel(Request $request){
        $ids = $request->input('id');
        if(blank($ids)){
            $result=['code'=>'error','message'=>'请选择要删除的数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        /* ********** 删除数据 ********** */
        DB::beginTransaction();
        try{
            /*---------是否正在被使用----------*/
            /*=== 房屋建筑 ===*/
            $estatebuilding = Estatebuilding::where('id',$ids)->value('household_building_id');
            if($estatebuilding!=0){
                throw new \Exception('该数据正在被使用,暂时不能被删除！',404404);
            }
            /*---------删除房屋建筑----------*/
            $estatebuilding = Estatebuilding::where('id',$ids)->forceDelete();
            if(!$estatebuilding){
                throw new \Exception('删除失败',404404);
            }

            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata='household';
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