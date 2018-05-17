<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdobject;
use App\Http\Model\Itembuilding;
use App\Http\Model\Itemland;
use App\Http\Model\Itemuser;
/*use Illuminate\Filesystem\Cache;*/
use Illuminate\Http\Request;
use App\libs\phpexcels\PHPExcel;
use App\libs\phpexcels\PHPExcel\IOFactory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class HouseholdController extends BaseitemController
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
        $select=['id','item_id','land_id','building_id','unit','floor','number','type','username','infos','code'];
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
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Household();
        DB::beginTransaction();
        try{
            $total=Household::sharedLock()
                ->where('item_id',$item_id)
                ->where($where)
                ->count();
            $households=Household::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'householddetail'=>function($query){
                    $query->select(['id','household_id','dispute','area_dispute','status','has_assets']);
                },
                'state'=>function($query){
                    $query->select(['code','name']);
                }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('g_household',['item'=>$item_id]));

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
            return view('gov.household.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $item=$this->item;
        $model=new Household();
        if($request->isMethod('get')){
            $sdata['itemland'] = Itemland::select(['id','address'])->where('item_id',$item_id)->get()?:[];
            $sdata['item_id'] = $item_id;
            $sdata['item'] = $item;
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.household.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'land_id' => 'required',
                'type' => 'required',
                'username' => ['required',Rule::unique('item_household')->where(function ($query) use($item_id){
                    $query->where('item_id',$item_id);
                })],
                'password' => 'required'
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
                /* ++++++++++ 批量赋值 ++++++++++ */
                $household = $model;
                $household->fill($request->all());
                $household->addOther($request);
                $household->item_id=$item_id;
                $household->save();
                if (blank($household)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $household;
                $edata = null;
                $url = route('g_household',['item'=>$item_id]);
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
        $item_id=$this->item_id;
        $item=$this->item;
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            $data['itemland'] = Itemland::select(['id','address'])->where('item_id',$item_id)->get()?:[];
            $data['item_id'] = $item_id;
            $data['item'] = $item;
            $data['household'] = new Household();
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

                $view='gov.household.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new Household();
            /* ********** 表单验证 ********** */
            $rules = [
                'type' => 'required',
                'username' => ['required',Rule::unique('item_household')->where(function ($query) use($item_id,$id){
                    $query->where('item_id',$item_id)->where('id','<>',$id);
                })],
                'password' => 'required'
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
                $household=Household::lockForUpdate()->find($id);
                if(blank($household)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $household->fill($request->all());
                $household->editOther($request);
                $household->save();
                if(blank($household)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$household;
                $edata=null;
                $url = route('g_household',['item'=>$item_id]);

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
            /*=== 房屋建筑 ===*/
            $householdbuilding = Householdbuilding::where('household_id',$ids)->count();
            if($householdbuilding!=0){
               throw new \Exception('该账号存在房屋建筑,暂时不能被删除！',404404);
            }
            /*=== 资产信息 ===*/
            $householdassets = Householdassets::where('household_id',$ids)->count();
            if($householdassets){
                throw new \Exception('该账号存在资产信息,暂时不能被删除！',404404);
            }
            /*=== 家庭成员 ===*/
            $householdmember = Householdmember::where('household_id',$ids)->count();
            if($householdmember){
                throw new \Exception('该账号存在家庭成员信息,暂时不能被删除！',404404);
            }
            /*=== 其他补偿事项 ===*/
            $householdobject = Householdobject::where('household_id',$ids)->count();
            if($householdobject){
                throw new \Exception('该账号存在其他补偿事项信息,暂时不能被删除！',404404);
            }
            /*=== 被征户当前状态 ===*/
            $household = Household::find($ids);
            if($household->code>60){
                throw new \Exception('该账号正在被使用,暂时不能被删除！',404404);
            }
            /*---------删除被征户账号----------*/
            $household = Household::where('id',$ids)->forceDelete();
            if(!$household){
                throw new \Exception('删除失败',404404);
            }
            /*---------删除被征户详情----------*/
            $householddetail = Householddetail::where('household_id',$ids)->first();
            if($householddetail){
                $householddetails = Householddetail::where('household_id',$ids)->forceDelete();
                if(!$householddetails){
                    throw new \Exception('删除失败',404404);
                }
            }


            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=['household'=>$household,'householddetail'=>$householddetail];
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

    /*导出被征户信息*/
    public function export(Request $request){
        $ids = $request->input('ids');
//        $ids='1,2,3,4,5,6,7,8,9,10';
        if(!$ids){
            $result=['code'=>'error','message'=>'请选择要导出的房源！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        $ids = explode(',',$ids);
        $households=Household::with([
            'itemland'=>function($query){
                $query->select(['id','address']);
            },
            'itembuilding'=>function($query){
                $query->select(['id','building']);
            },
            'householddetail'=>function($query){
                $query->with(['defbuildinguse','realbuildinguse','layout']);
            },
            'state'=>function($query){
                $query->select(['code','name']);
            }])
            ->whereIn('id',$ids)
            ->get();

        /*---------- 头部 -------------*/
        $data[]=['序号', '地块', '楼栋', '位置', '房产类型', '用户名', '状态', '产权争议', '面积争议', '房屋产权证号', '建筑面积', '阳台面积', '批准用途', '实际用途', '资产评估', '征收意见', '补偿方式','产权调换意向-房源单价','产权调换意向-房源面积','产权调换意向-房源数量','产权调换意向-房源地址','产权调换意向-增加面积单价','产权调换意向-房源户型', '其他意见', '收件人', '收件电话', '收件地址'];

        foreach ($households as $k=>$v){
            $building = $v->building?$v->building.'栋':'';
            $unit = $v->unit?$v->unit.'单元':'';
            $floor =  $v->floor?$v->floor.'楼':'';
            $number = $v->number?$v->number.'号':'';
            $data[]=[
                $k+1,
                $v->itemland->address,
                $v->building?$v->building.'栋':'',
                $building.$unit.$floor.$number,
                $v->type,
                $v->username,
                $v->state->name,
                $v->householddetail->dispute,
                $v->householddetail->area_dispute,
                $v->householddetail->register,
                $v->householddetail->reg_outer,
                $v->householddetail->balcony,
                $v->householddetail->defbuildinguse->name,
                $v->householddetail->realbuildinguse->name,
                $v->householddetail->has_assets,
                $v->householddetail->agree,
                $v->householddetail->repay_way,
                $v->householddetail->house_price,
                $v->householddetail->house_area,
                $v->householddetail->house_num,
                $v->householddetail->house_addr,
                $v->householddetail->more_price,
                $v->householddetail->layout->name,
                $v->householddetail->opinion,
                $v->householddetail->receive_man,
                $v->householddetail->receive_tel,
                $v->householddetail->receive_addr
            ];
        }

        if(count($data)>1){
            export_house_xls($data,'被征户'.date('Ymd'));
        }else{
            $result=['code'=>'error1','message'=>'暂无对应数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }


    }

    /*导入excel表格示例*/
    public function import_demo(Request $request){
        /*---------- 头部 -------------*/
        $data[]=['地块','楼栋号','单元号', '楼层', '房号', '房产类型', '用户名', '密码', '描述'];

        $data[]=[
            '示例：永庆路北侧片区',
            '示例：1',
            '示例：1',
            '示例：1',
            '示例：1',
            '私产 或 公产，示例：私产',
            '示例：王亮',
            '示例：123456',
            '示例：描述内容',
        ];
        export_household_xls($data,'被征户信息导出示例excel');
    }

    /*导入*/
    public function import(Request $request){

        $files=$request->file();
        $key=array_keys($files);
        $file = $files[$key[0]];
        if($file->isValid()){
            $file_name = date('YmdHis').rand(100,999);
            $info = $file->move( './storage/'.date('ymd'),$file_name.'.xls');
            if($info){
                $file_url = './storage/'.date('ymd').'/'.$file_name.'.xls';
                $result=self::get_excel_data($file_url);

                $view =$result['code']=='error'?'gov.error':'gov.household.import_result';

                /* ++++++++++ 结果 ++++++++++ */
                return view($view)->with($result);

            }else{
                $result=['code'=>'error','message'=>'文件导入失败！','sdata'=>null,'edata'=>null,'url'=>null];
                return view('gov.error')->with($result);
            }

        }else{
            $result=['code'=>'error','message'=>'文件获取失败！','sdata'=>null,'edata'=>null,'url'=>null];
            return view('gov.error')->with($result);
        }
    }

    public function export_error(Request $request){
        $file_url = $request->input('file_url');
        $title=[
            'land'=>'地块',
            'building'=>'楼栋号',
            'unit'=>'单元号',
            'floor'=>'楼层',
            'number'=>'房号',
            'type'=>'房产类型',
            'username'=>'用户名',
            'password'=>'密码',
            'infos'=>'描述'
        ];

        $household_error_data = Cache::remember('household_error_data', 120, function () use ($file_url) {
            return self::get_excel_data($file_url)['sdata']['error_data'];
        });

        if(!empty($household_error_data)){
            array_unshift($household_error_data,$title);
            export_household_xls($household_error_data,'被征户错误数据导出');
        }else{
            $result=['code'=>'error','message'=>'数据已过期！','sdata'=>null,'edata'=>null,'url'=>null];
            return view('gov.error')->with($result);
        }

    }

    protected  function get_excel_data($file_name){
        $datas = $file_name;
        $title = [
            'land'=>'地块',
            'building'=>'楼栋号',
            'unit'=>'单元号',
            'floor'=>'楼层',
            'number'=>'房号',
            'type'=>'房产类型',
            'username'=>'用户名',
            'password'=>'密码',
            'infos'=>'描述'
        ];
        $add_data_array = import_household($datas,$title);
        $count_all=count($add_data_array);

        /*去掉已存在的用户数据--用户名*/
        foreach ($add_data_array as $k=>$v){
            $household_check = Household::where('username',trim($v['username']))
                ->count();
            if($household_check){
                unset($add_data_array[$k]);
            }
        }
        $count_exit=$count_all-count($add_data_array);


        foreach ($add_data_array as $k=>$v){

            if(isset($v['land'])){
                $land_id=Itemland::where('address',trim($v['land']))->value('id');
                if(isset($land_id)){
                    $success_data[$k]['land_id']=$land_id;
                }else{
                    $error_data[$k]=$v;
                    continue;
                }
            }else{
                $error_data[$k]=$v;
                continue;
            }

            if(isset($v['building'])){
                $building_id=Itembuilding::where('building',trim($v['building']))->value('id');
                if(isset($building_id)){
                    $success_data[$k]['building_id']=$building_id;
                }else{
                    $error_data[$k]=$v;
                    continue;
                }
            }else{
                $error_data[$k]=$v;
                continue;
            }

            $success_data[$k]['unit']=!empty($v['unit'])? $v['unit']:'';
            $success_data[$k]['floor']=!empty($v['floor'])? $v['floor']:'';
            $success_data[$k]['number']=!empty($v['number'])? $v['number']:'';

            if(trim($v['type'])=='公产'){
                $success_data[$k]['type']=0;
            }elseif (trim($v['type'])=='私产'){
                $success_data[$k]['type']=1;
            }else{
                $error_data[$k]=$v;
                continue;
            }

            if(!empty($v['username'])){
                $success_data[$k]['username']=trim($v['username']);
            }else{
                $error_data[$k]=$v;
                continue;
            }

            if(!empty($v['password'])){
                $success_data[$k]['password']=encrypt(trim($v['password']));
            }else{
                $error_data[]=$v;
                continue;
            }

            $success_data[$k]['infos']=!empty($v['infos'])?$v['infos']:'';

            $household=new Household();
            $success_data[$k]['secret']=$household->get_secret();

            $success_data[$k]['created_at']=date('Y-m-d H:i:s');
            $success_data[$k]['updated_at']=date('Y-m-d H:i:s');

            $success_data[$k]['item_id']=$this->item_id;
            $success_data[$k]['code']=60;
        }

        /*去掉错误数据*/
        if(!empty($error_data)){
            foreach ($error_data as $k=>$v){
                unset($success_data[$k]);
            }
        }

        /*符合格式的条数*/
        $success_count=count($success_data);

        if(empty($success_data)){
            $result=['code'=>'error','message'=>'没有正确的被征户数据','sdata'=>null,'edata'=>null,'url'=>null];
            return view('gov.error')->with($result);
        }


        /*用户名去重*/
        $unique_username=[];
        foreach ($success_data as $k=>$v){
            $unique_username[$k]=$v['username'];
        }

        /*用户名去重后合格数据键名*/
        $username_unique_keys = array_keys(array_unique($unique_username));
        foreach ($username_unique_keys as $k => $v) {
            $after_user_unique[] = $success_data[$v];
        };


        /*数组去重--住址*/
        $unique_addr=[];
        foreach ($after_user_unique as $k=>$v){
            $addr_check=Household::where('item_id',$v['item_id'])
                ->where('land_id',$v['land_id'])
                ->where('building_id',$v['building_id'])
                ->where('unit',$v['unit'])
                ->where('floor',$v['floor'])
                ->where('number',$v['number'])
                ->first();
            if(blank($addr_check)){
                $unique_addr[$k]=$v['item_id'].$v['land_id'].$v['building_id'].$v['unit'].$v['floor'].$v['number'];
            }
        }
        /*住址去重后合格数据键名*/
        $addr_unique_keys=array_keys(array_unique($unique_addr));

        $add_data=[];
        foreach ($addr_unique_keys as $k => $v) {
            $add_data[] = $after_user_unique[$v];
        };

        DB::beginTransaction();
        $result=Household::insert($add_data);
        if(!$result){
            $code = 'error';
            $msg='添加失败!';
            $sdata = null;
            $edata = null;
            DB::rollBack();
        }else{
            $code = 'success';
            $msg='添加成功!';
            $sdata = [
                'total_count'=>$count_all,
                'format_count'=>$count_all-count($error_data),
                'error_count'=>count($error_data),
                'repeat_count'=>$count_exit+$success_count-count($add_data),//重组前过滤+用户名过滤+地址过滤
                'add_count'=>count($add_data),
                'file_url'=>$datas,
                'error_data'=>$error_data,
                'item_id'=>$this->item_id
            ];
            $edata = null;
            DB::commit();
        }

        /* ++++++++++ 结果 ++++++++++ */
        return $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>null];

    }

}