<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户 家庭成员
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Household;
use App\Http\Model\Householdmember;
use App\Http\Model\Householdmembercrowd;
use App\Http\Model\Householdright;
use App\Http\Model\Itemuser;
use App\Http\Model\Nation;
use App\Http\Model\Paycrowd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdmemberController extends BaseitemController
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

        $where[] = ['household_id',$request->input('household_id')];
        $infos['household_id'] = $request->input('household_id');

        $select=['id','item_id','household_id','land_id','building_id','name','relation','card_num','phone','nation_id','sex','age','crowd','holder','portion','deleted_at'];
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 查询 ********** */
        $model=new Householdmember();
        DB::beginTransaction();
        try{
            $householdmembers=$model
                ->with(['item'=>function($query){
                        $query->select(['id','name']);
                    },
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    },
                    'nation'=>function($query){
                            $query->select(['id','name']);
                    }])
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->get();
            if(blank($householdmembers)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$householdmembers;
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
            return view('gov.householdmember.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $item_id = $this->item_id;
        $household_id = $request->input('household_id');
        $model=new Householdmember();
        if($request->isMethod('get')){
            $sdata['household'] = Household::select(['id','land_id','item_id','building_id','type'])->find($household_id);
            $sdata['nation'] = Nation::select(['id','name'])->get();
            $file_table_id=Filetable::where('name','item_household_member')->sharedLock()->value('id');
            $sdata['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
            $edata = $model;
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$edata,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.householdmember.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
           $item_ids = $request->input('item');
            /* ++++++++++ 同一被征户是否存在第二个承租人【公产】 ++++++++++ */
            $holder = $request->input('holder');
            if($holder==2){
               $holder2_count =  Householdmember::where('item_id',$item_ids)
                    ->where('household_id',$household_id)
                    ->where('holder',$holder)
                    ->count();
               if($holder2_count){
                   $result=['code'=>'error','message'=>'承租人已经存在','sdata'=>null,'edata'=>null,'url'=>null];
                   if($request->ajax()){
                       return response()->json($result);
                   }else{
                       return view('gov.householdmember.add')->with($result);
                   }
               }
            }
            /* ++++++++++ 份额是否超过 ++++++++++ */
            $portion = $request->input('portion');
            $portion_sum = Householdmember::where('item_id',$item_ids)
                ->where('household_id',$household_id)
                ->sum('portion');
            $sums = $portion+$portion_sum;
            if($sums>100){
                $result=['code'=>'error','message'=>'总份额超出限定范围(0-100)','sdata'=>null,'edata'=>null,'url'=>null];
                if($request->ajax()){
                    return response()->json($result);
                }else{
                    return view('gov.householdmember.add')->with($result);
                }
            }

            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'household_id'=>'required',
                'name' =>  ['required',Rule::unique('item_household_member')->where(function ($query) use($household_id){
                    $query->where('household_id', $household_id);
                })],
                'land_id'=>'required',
                'building_id'=>'required',
                'relation'=>'required',
                'card_num'=>'required',
                'phone'=>'required',
                'nation_id'=>'required',
                'sex'=>'required',
                'age'=>'required',
                'crowd'=>'required',
                'holder'=>'required',
                'portion'=>'required'
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

            $file_table_id=Filetable::where('name','item_household_member')->sharedLock()->value('id');
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
                $householdmember = $model;
                $householdmember->fill($request->input());
                $householdmember->addOther($request);
                $householdmember->item_id = $item_id;
                $householdmember->save();
                if (blank($householdmember)) {
                    throw new \Exception('添加失败', 404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $householdmember;
                $edata = null;
                $url = route('g_householddetail_info',['item'=>$item_id,'id'=>$household_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = $householdmember;
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
        $file_table_id=Filetable::where('name','item_household_member')->sharedLock()->value('id');
        $filecates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
        $householdmembercrowd = Householdmembercrowd::select(['id','item_id','crowd_id','picture'])
                        ->with(['crowd'=>function($query){
                            $query->select(['id','name']);
                        }])
                        ->where('item_id',$this->item_id)
                        ->where('member_id',$id)
                        ->get();
        $householdmember=Householdmember::with([
            'nation'=>function($query){
                $query->select(['id','name']);
            }])
            ->sharedLock()
            ->find($id);
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdmember)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=['crowd'=>$householdmembercrowd,'filecates'=>$filecates];
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdmember;
            $edata=['crowd'=>$householdmembercrowd,'filecates'=>$filecates];
            $url=null;

            $view='gov.householdmember.info';
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
        $item_id=$this->item_id;
        $household_id = $request->input('household_id');
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $data['household'] = Household::select(['id','land_id','item_id','building_id','type'])->find($household_id);
            $data['nation'] = Nation::select(['id','name'])->get();
            $data['householdmember'] = new Householdmember();
            $file_table_id=Filetable::where('name','item_household_member')->sharedLock()->value('id');
            $data['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
            $householdmember=Householdmember::with([
                'nation'=>function($query){
                    $query->select(['id','name']);
                }])
                ->sharedLock()
                ->find($id);
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($householdmember)){
                $code='warning';
                $msg='数据不存在';
                $sdata=null;
                $edata=$data;
                $url=null;
            }else{
                $code='success';
                $msg='获取成功';
                $sdata=$householdmember;
                $edata=$data;
                $url=null;
                $view='gov.householdmember.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $item_ids = $request->input('item');
            /* ++++++++++ 同一被征户是否存在第二个承租人【公产】 ++++++++++ */
            $holder = $request->input('holder');
            if($holder==2){
                $holder2_count =  Householdmember::where('item_id',$item_ids)
                    ->where('household_id',$household_id)
                    ->where('holder',$holder)
                    ->where('id','<>',$id)
                    ->count();
                if($holder2_count){
                    $result=['code'=>'error','message'=>'承租人已经存在','sdata'=>null,'edata'=>null,'url'=>null];
                    if($request->ajax()){
                        return response()->json($result);
                    }else{
                        return view('gov.householdmember.add')->with($result);
                    }
                }
            }
            /* ++++++++++ 份额是否超过 ++++++++++ */
            $portion = $request->input('portion');
            $portion_sum = Householdmember::where('item_id',$item_ids)
                ->where('household_id',$household_id)
                ->where('id','<>',$id)
                ->sum('portion');
            $sums = $portion+$portion_sum;
            if($sums>100){
                $result=['code'=>'error','message'=>'总份额超出限定范围(0-100)','sdata'=>null,'edata'=>null,'url'=>null];
                if($request->ajax()){
                    return response()->json($result);
                }else{
                    return view('gov.householdmember.add')->with($result);
                }
            }

            $model=new Household();
            /* ********** 表单验证 ********** */
            $rules = [
                'household_id'=>'required',
                'name' =>['required',Rule::unique('item_household_member')->where(function ($query) use($household_id,$id){
                    $query->where('household_id', $household_id)->where('id','<>',$id);
                })],
                'land_id'=>'required',
                'building_id'=>'required',
                'relation'=>'required',
                'card_num'=>'required',
                'phone'=>'required',
                'nation_id'=>'required',
                'sex'=>'required',
                'age'=>'required',
                'crowd'=>'required',
                'holder'=>'required',
                'portion'=>'required'
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
            $file_table_id=Filetable::where('name','item_household_member')->sharedLock()->value('id');
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
                $householdmember=Householdmember::lockForUpdate()->find($id);
                if(blank($householdmember)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $householdmember->fill($request->all());
                $householdmember->editOther($request);
                $householdmember->save();
                if(blank($householdmember)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$householdmember;
                $edata=null;
                $url = route('g_householddetail_info',['id'=>$household_id,'item'=>$item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$householdmember;
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
            $household_id = Householdmember::where('id',$ids)->value('household_id');
            if($household_id){
                $householdright = Householdright::where('household_id',$household_id)->count();
                if($householdright!=0){
                    throw new \Exception('该家庭成员正在被使用,暂时不能被删除！',404404);
                }
            }

            $householdmembercrowd = Householdmembercrowd::where('member_id',$ids)->value('id');
            if($householdmembercrowd){
                $paycrowd = Paycrowd::where('member_crowd_id',$householdmembercrowd)->count();
                if($paycrowd!=0){
                    throw new \Exception('该家庭成员正在被使用,暂时不能被删除！',404404);
                }
            }
            /*---------删除家庭成员----------*/
            $householdmember = Householdmember::where('id',$ids)->forceDelete();
            if(!$householdmember){
                throw new \Exception('删除失败',404404);
            }
            /*---------删除家庭成员下的特殊人群----------*/
            $householdmembercrowd = Householdmembercrowd::where('member_id',$ids)->count();
            if($householdmembercrowd){
                $householdmembercrowds = Householdmembercrowd::where('member_id',$ids)->forceDelete();
                if(!$householdmembercrowds){
                    throw new \Exception('删除失败',404404);
                }
            }


            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$householdmember;
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