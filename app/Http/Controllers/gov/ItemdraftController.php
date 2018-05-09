<?php
/*
|--------------------------------------------------------------------------
| 项目-征收意见稿
|--------------------------------------------------------------------------
*/
namespace  App\Http\Controllers\gov;
use App\Http\Model\Itemdraft;
use App\Http\Model\Itemuser;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemdraftController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 详情页 ========== */
    public function index(Request $request){
        $item_id=$this->item_id;
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id',$item_id];
        $infos['item_id'] = $item_id;
        $select=['id','name','code','content','item_id','created_at','updated_at','deleted_at'];

        /* ********** 查询 ********** */
        $model=new Itemdraft();
        DB::beginTransaction();
        try{
            $itemdraft=$model
                ->with(['state'=>function($query){
                    $query->select(['code','name']);
                }])
                ->where($where)
                ->select($select)
                ->sharedLock()
                ->first();
            if(blank($itemdraft)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$itemdraft;
            $edata=$infos;
            $url=null;
        }catch(\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=$infos;
            $url=null;
        }
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];

        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.itemdraft.index')->with($result);
        }
    }

    /* ========== 添加页 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemdraft();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=3 || $item->process_id!=30 || $item->code != '22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',31],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }

                $itemdraft=Itemdraft::sharedLock()->where('item_id',$this->item_id)->first();
                if(filled($itemdraft)){
                    throw new \Exception('征收意见稿已添加',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
               /* $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];*/

                $code = 'success';
                $msg = '请求成功';
                $sdata = ['item_id'=>$item_id];
                $edata = null;
                $url = null;

                $view='gov.itemdraft.add';
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
        else{
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'content'=>'required',
                'name'=>'required'
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
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=3 || $item->process_id!=30 || $item->code != '22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                $itemdraft=Itemdraft::sharedLock()->where('item_id',$this->item_id)->first();
                if(filled($itemdraft)){
                    throw new \Exception('征收意见稿已添加',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                $worknotice->code='2';
                $worknotice->save();

                /* ++++++++++ 批量赋值 ++++++++++ */
                $itemdraft = $model;
                $itemdraft->fill($request->all());
                $itemdraft->addOther($request);
                $itemdraft->item_id=$this->item_id;
                $itemdraft->code='20';
                $itemdraft->save();
                if (blank($itemdraft)) {
                    throw new \Exception('添加失败', 404404);
                }
                /* ++++++++++ 删除相同工作推送 ++++++++++ */
                Worknotice::lockForUpdate()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$process->schedule_id],
                        ['process_id',$process->id],
                        ['menu_id',$process->menu_id],
                        ['code','0'],
                    ])
                    ->delete();

                /* ++++++++++ 征收意见稿审查 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',32],
                    ])
                    ->get();
                $values=[];
                /* ++++++++++ 征收意见稿审查 工作提醒推送 ++++++++++ */
                foreach ($itemusers as $user){
                    $values[]=[
                        'item_id'=>$user->item_id,
                        'schedule_id'=>$user->schedule_id,
                        'process_id'=>$user->process_id,
                        'menu_id'=>$user->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->user_id,
                        'url'=>route('g_draft_check',['item'=>$this->item->id],false),
                        'code'=>'20',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }

                $field=['item_id','schedule_id','process_id','menu_id','dept_id','parent_id','role_id','user_id','url','code','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_work_notice',$field,$values,'updated_at');
                if(!$sqls){
                    throw new \Exception('操作失败',404404);
                }
                foreach ($sqls as $sql){
                    DB::statement($sql);
                }

                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code=$worknotice->code;
                $item->save();

                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemdraft;
                $edata = null;
                $url = route('g_itemdraft',['item'=>$this->item_id]);
                DB::commit();
            }catch (\Exception $exception){
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

    /* ========== 修改页 ========== */
    public function edit(Request $request){
        $item_id=$this->item_id;
        if ($request->isMethod('get')) {
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,['31','32']) || ($item->process_id =='31' && $item->code!='2') || ($item->process_id =='32' && $item->code!='23')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',31],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                $itemdraft=Itemdraft::sharedLock()->where('item_id',$this->item_id)->first();
                if(blank($itemdraft)){
                    throw new \Exception('征收意见稿还未添加',404404);
                }

                $code = 'success';
                $msg = '请求成功';
                $sdata =[
                    'item'=>$this->item,
                    'itemdraft'=>$itemdraft,
                ];
                $edata = null;
                $url = null;

                $view='gov.itemdraft.edit';
            }catch (\Exception $exception){
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
                $sdata = null;
                $edata = null;
                $url = null;

                $view='gov.error';
            }

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 保存 ********** */
        else{
            $model=new Itemdraft();
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'content'=>'required',
                'name'=>'required'
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
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,['31','32']) || ($item->process_id =='31' && $item->code!='2') || ($item->process_id =='32' && $item->code!='23')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',31],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                /* ++++++++++ 审查驳回处理 ++++++++++ */
                if($item->process_id==32 && $item->code=='23'){
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$item->schedule_id],
                            ['process_id',31],
                            ['code','0'],
                        ])
                        ->delete();
                    /* ++++++++++ 征收意见稿审查 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',32],
                        ])
                        ->get();
                    $values=[];
                    /* ++++++++++ 征收意见稿审查 工作提醒推送 ++++++++++ */
                    foreach ($itemusers as $user){
                        $values[]=[
                            'item_id'=>$user->item_id,
                            'schedule_id'=>$user->schedule_id,
                            'process_id'=>$user->process_id,
                            'menu_id'=>$user->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->user_id,
                            'url'=>route('g_draft_check',['item'=>$this->item->id],false),
                            'code'=>'20',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }

                    $field=['item_id','schedule_id','process_id','menu_id','dept_id','parent_id','role_id','user_id','url','code','created_at','updated_at'];
                    $sqls=batch_update_or_insert_sql('item_work_notice',$field,$values,'updated_at');
                    if(!$sqls){
                        throw new \Exception('操作失败',404404);
                    }
                    foreach ($sqls as $sql){
                        DB::statement($sql);
                    }

                    $item->schedule_id=4;
                    $item->process_id=31;
                    $item->code='2';
                    $item->save();
                }
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemdraft=Itemdraft::lockForUpdate()->where('item_id',$this->item_id)->first();
                if(blank($itemdraft)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $itemdraft->fill($request->all());
                $itemdraft->editOther($request);
                $itemdraft->code='20';
                $itemdraft->save();
                if(blank($itemdraft)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$itemdraft;
                $edata=null;
                $url=route('g_itemdraft',['item'=>$this->item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$exception->getMessage();
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }
}