<?php
/*
|--------------------------------------------------------------------------
| 项目流程
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Funds;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householddetail;
use App\Http\Model\Initbudget;
use App\Http\Model\Item;
use App\Http\Model\Itemadmin;
use App\Http\Model\Itemcrowd;
use App\Http\Model\Itemdraft;
use App\Http\Model\Itemhouse;
use App\Http\Model\Itemhouserate;
use App\Http\Model\Itemland;
use App\Http\Model\Itemnotice;
use App\Http\Model\Itemobject;
use App\Http\Model\Itemprogram;
use App\Http\Model\Itempublic;
use App\Http\Model\Itemreward;
use App\Http\Model\Itemriskreport;
use App\Http\Model\Itemsubject;
use App\Http\Model\Itemtime;
use App\Http\Model\Itemuser;
use App\Http\Model\News;
use App\Http\Model\Process;
use App\Http\Model\Schedule;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemprocessController extends BaseitemController
{
    /* ========== 初始化 ========== */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 项目进度 ========== */
    public function index(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $schedules=Schedule::with(['worknotices'=>function($query){
                $query->with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc');
            },'processes'=>function($query){
                $query->select(['id','schedule_id','name','sort'])
                    ->orderBy('sort','asc');
            }])
                ->orderBy('sort','asc')
                ->sharedLock()
                ->get();

            /* ++++++++++ 工作提醒 ++++++++++ */
            $worknotices=Worknotice::with(['process'=>function($query){
                $query->select(['id','name']);
            }])
                ->where('item_id',$this->item_id)
                ->where('user_id',session('gov_user.user_id'))
                ->whereIn('code',['0','20'])
                ->sharedLock()
                ->get();
            if(blank($schedules)){
                throw new \Exception('没有符合条件的数据',404404);
            }

            $code='success';
            $msg='查询成功';
            $sdata=['schedules'=>$schedules,'worknotices'=>$worknotices];
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
            return view('gov.itemprocess.index')->with($result);
        }
    }

    /* ========== 项目审查 - 提交部门审查 ========== */
    public function check_to_dept_check(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;

            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id==1 && $item->process_id==2){
                throw new \Exception('当前项目已【'.$item->process->name.'】，不能重复操作',404404);
            }
            if($item->schedule_id!=1 || !in_array($item->process_id,[1,4]) || $item->code != '2'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            $result=$this->hasNotice();
            $process=$result['process'];
            $worknotice=$result['worknotice'];
            /* ++++++++++ 执行 ++++++++++ */
            $worknotice->code='2';
            $worknotice->save();
            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
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

            /* ++++++++++ 部门审查 可操作人员 ++++++++++ */
            $process=Process::with(['processusers'=>function($query){
                $query->with('role');
            }])
                ->select(['id','schedule_id','menu_id'])
                ->find(3);
            $values=[];
            /* ++++++++++ 部门审查 工作提醒推送 ++++++++++ */
            foreach ($process->processusers as $user){
                $values[]=[
                    'item_id'=>$item->id,
                    'schedule_id'=>$process->schedule_id,
                    'process_id'=>$process->id,
                    'menu_id'=>$process->menu_id,
                    'dept_id'=>$user->dept_id,
                    'parent_id'=>$user->role->parent_id,
                    'role_id'=>$user->role_id,
                    'user_id'=>$user->id,
                    'url'=>route('g_check_dept_check',['item'=>$this->item->id],false),
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
            
            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);
            
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'操作失败';
            $sdata=null;
            $edata=null;
            $url=null;
            
            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if(request()->ajax()){
            return response()->json($result);
        }else{
            return view('gov.error')->with($result);
        }
    }

    /* ========== 项目审查 - 部门审查 ========== */
    public function check_dept_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[2,3])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==2 && $item->code=='1'){
                    throw new \Exception('当前项目未完成【'.$item->process->name.'】，不能进行当前操作',404404);
                }
                if($item->process_id==3 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if($item->process_id==3 && $item->code=='23'){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'file_cates'=>$file_cates,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.dept_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[2,3])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==2 && $item->code=='1'){
                    throw new \Exception('当前项目未完成【'.$item->process->name.'】，不能进行当前操作',404404);
                }
                if($item->process_id==3 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if($item->process_id==3 && $item->code=='23'){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                        }else{
                            /* ++++++++++ 提交区政府审查 可操作人员 ++++++++++ */
                            $process=Process::with(['processusers'=>function($query){
                                $query->with('role');
                            }])
                                ->select(['id','schedule_id','menu_id'])
                                ->find(6);
                            $values=[];
                            /* ++++++++++ 提交区政府审查 工作提醒推送 ++++++++++ */
                            foreach ($process->processusers as $user){
                                $values[]=[
                                    'item_id'=>$item->id,
                                    'schedule_id'=>$process->schedule_id,
                                    'process_id'=>$process->id,
                                    'menu_id'=>$process->menu_id,
                                    'dept_id'=>$user->dept_id,
                                    'parent_id'=>$user->role->parent_id,
                                    'role_id'=>$user->role_id,
                                    'user_id'=>$user->id,
                                    'url'=>route('g_iteminfo_info',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 审查驳回处理 可操作人员 ++++++++++ */
                    $process=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->find(5);
                    $values=[];
                    /* ++++++++++ 审查驳回处理 工作提醒推送 ++++++++++ */
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>route('g_check_roll_back',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 审查驳回处理 ========== */
    public function check_roll_back(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[3,7,5])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if((in_array($item->process_id,[3,7]) && $item->code!='23') || ($item->process_id==5 && $item->code=='2')){
                    throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='1';
                $item->save();

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.roll_back';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ++++++++++ 处理结果 ++++++++++ */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[3,7,5])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if((in_array($item->process_id,[3,7]) && $item->code!='23') || ($item->process_id==5 && $item->code=='2')){
                    throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $way=$request->input('way');
                if(blank($way) || !in_array($way,[0,1])){
                    throw new \Exception('请选择审查驳回处理方式',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新操作 ++++++++++ */
                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                $values=[];
                /* ++++++++++ 处理方式 不予受理 ++++++++++ */
                if($way){
                    /* ++++++++++ 不予受理 可操作人员 ++++++++++ */
                    $process=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->find(15);
                    /* ++++++++++ 不予受理 工作提醒推送 ++++++++++ */
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>route('g_check_item_stop',['item'=>$this->item->id],false),
                            'code'=>'0',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                }
                /* ++++++++++ 处理方式 重新提交审查资料 ++++++++++ */
                else{
                    /* ++++++++++ 重新提交审查资料 可操作人员 ++++++++++ */
                    $process=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->find(4);
                    /* ++++++++++ 重新提交审查资料 工作提醒推送 ++++++++++ */
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>route('g_check_iteminfo_retry',['item'=>$this->item->id],false),
                            'code'=>'0',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                }

                $field=['item_id','schedule_id','process_id','menu_id','dept_id','parent_id','role_id','user_id','url','code','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_work_notice',$field,$values,'updated_at');
                if(!$sqls){
                    throw new \Exception('操作失败',404404);
                }
                foreach ($sqls as $sql){
                    DB::statement($sql);
                }

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code=$worknotice->code;
                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 重新提交审查资料 ========== */
    public function check_iteminfo_retry(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[4,5])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if(($item->process_id==5 && $item->code=='1') || ($item->process_id==4 && $item->code=='2')){
                    throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='1';
                $item->save();

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'file_cates'=>$file_cates,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.iteminfo_retry';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ++++++++++ 处理结果 ++++++++++ */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[4,5])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if(($item->process_id==5 && $item->code=='1') || ($item->process_id==4 && $item->code=='2')){
                        throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $model=new Item();
                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'name'=>'required|unique:item,name,'.$this->item_id.',id',
                    'place'=>'required',
                    'map'=>'required'
                ];
                $messages=[
                    'required'=>':attribute 为必须项',
                    'unique'=>':attribute 已存在',
                ];
                $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }

//                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
//                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
//                $rules=[];
//                $messages=[];
//                foreach ($file_cates as $file_cate){
//                    $name='picture.'.$file_cate->filename;
//                    $rules[$name]='required';
//                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
//                }
//                $validator = Validator::make($request->all(),$rules,$messages);
//                if($validator->fails()){
//                    throw new \Exception($validator->errors()->first(),404404);
//                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新操作 ++++++++++ */
                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                $values=[];
                /* ++++++++++ 提交部门审查 可操作人员 ++++++++++ */
                $process=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->find(2);
                /* ++++++++++ 提交部门审查 工作提醒推送 ++++++++++ */
                foreach ($process->processusers as $user){
                    $values[]=[
                        'item_id'=>$item->id,
                        'schedule_id'=>$process->schedule_id,
                        'process_id'=>$process->id,
                        'menu_id'=>$process->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->id,
                        'url'=>route('g_iteminfo_info',['item'=>$this->item->id],false),
                        'code'=>'0',
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

                $item->fill($request->input());
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code=$worknotice->code;
                $item->save();
                if(blank($item)){
                    throw new \Exception('保存失败',404404);
                }

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 不予受理 ========== */
    public function check_item_stop(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[5,15])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if(($item->process_id==5 && $item->code=='1') || ($item->process_id==15 && $item->code=='2')){
                    throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='1';
                $item->save();

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.item_stop';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ++++++++++ 处理结果 ++++++++++ */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[5,15])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if(($item->process_id==5 && $item->code=='1') || ($item->process_id==15 && $item->code=='2')){
                    throw new \Exception('当前项目处于【'.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $model=new Itemnotice();
                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'infos'=>'required',
                    'picture'=>'required'
                ];
                $messages=[
                    'required'=>':attribute 为必须项',
                ];
                $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新操作 ++++++++++ */
                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 保存 ++++++++++ */
                $itemnotice=$model;
                $itemnotice->fill($request->input());
                $itemnotice->item_id=$this->item_id;
                $itemnotice->cate_id=1;
                $itemnotice->save();
                if(blank($itemnotice)){
                    throw new \Exception('保存失败',404404);
                }

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code=$worknotice->code;
                $item->save();
                if(blank($item)){
                    throw new \Exception('保存失败',404404);
                }

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 提交区政府审查 ========== */
    public function check_to_gov_check(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;

            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id==1 && $item->process_id==6){
                throw new \Exception('当前项目已【'.$item->process->name.'】，不能重复操作',404404);
            }
            if($item->schedule_id!=1 || $item->process_id!=3 || $item->code != '22'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            $result=$this->hasNotice();
            $process=$result['process'];
            $worknotice=$result['worknotice'];
            /* ++++++++++ 执行 ++++++++++ */
            $worknotice->code='2';
            $worknotice->save();
            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
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

            /* ++++++++++ 区政府审查 可操作人员 ++++++++++ */
            $process=Process::with(['processusers'=>function($query){
                $query->with('role');
            }])
                ->select(['id','schedule_id','menu_id'])
                ->find(7);
            $values=[];
            /* ++++++++++ 区政府审查 工作提醒推送 ++++++++++ */
            foreach ($process->processusers as $user){
                $values[]=[
                    'item_id'=>$item->id,
                    'schedule_id'=>$process->schedule_id,
                    'process_id'=>$process->id,
                    'menu_id'=>$process->menu_id,
                    'dept_id'=>$user->dept_id,
                    'parent_id'=>$user->role->parent_id,
                    'role_id'=>$user->role_id,
                    'user_id'=>$user->id,
                    'url'=>route('g_check_gov_check',['item'=>$this->item->id],false),
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

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'操作失败';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目审查 - 区政府审查 ========== */
    public function check_gov_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[6,7])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==7 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if($item->process_id==7 && $item->code=='23'){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'file_cates'=>$file_cates,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.gov_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[6,7])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==7 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if($item->process_id==7 && $item->code=='23'){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }

                /* ++++++++++ 删除相同工作推送 ++++++++++ */
                Worknotice::lockForUpdate()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$process->schedule_id],
                        ['process_id',$process->id],
                        ['menu_id',$process->menu_id],
                        ['code','20'],
                    ])
                    ->delete();
                $values=[];
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 开启项目启动配置 可操作人员 ++++++++++ */
                    $process=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->find(8);
                    $values=[];
                    /* ++++++++++ 开启项目启动配置 工作提醒推送 ++++++++++ */
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>route('g_check_start_set',['item'=>$this->item->id],false),
                            'code'=>'0',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 审查驳回处理 可操作人员 ++++++++++ */
                    $process=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->find(5);
                    /* ++++++++++ 审查驳回处理 工作提醒推送 ++++++++++ */
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>route('g_check_roll_back',['item'=>$this->item->id],false),
                            'code'=>'0',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 开启项目启动配置 ========== */
    public function check_start_set(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=7 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.start_set';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=7 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='1';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 项目启动配置 可操作人员 ++++++++++ */
                $processes=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->where('parent_id',8)
                    ->get();
                /* ++++++++++ 项目启动配置 工作提醒推送 ++++++++++ */
                $values=[];
                foreach($processes as $process){
                    switch ($process->id){
                        case 9: // 项目人员
                            $url=route('g_itemuser',['item'=>$this->item->id],false);
                            break;
                        case 10: // 项目时间
                            $url=route('g_itemtime',['item'=>$this->item->id],false);
                            break;
                        case 16: // 项目负责人
                            $url=route('g_itemadmin',['item'=>$this->item->id],false);
                            break;
                    }
                    foreach ($process->processusers as $user){
                        $values[]=[
                            'item_id'=>$item->id,
                            'schedule_id'=>$process->schedule_id,
                            'process_id'=>$process->id,
                            'menu_id'=>$process->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->id,
                            'url'=>$url,
                            'code'=>'0',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 配置项目负责人 ========== */
    public function check_set_itemadmin(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;

            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=1 || $item->process_id!=8 ||  $item->code!='1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 流程设置 ++++++++++ */
            $process=Process::sharedLock()->find(16);
            /* ++++++++++ 是否有工作推送 ++++++++++ */
            $worknotice=Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$this->item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->whereIn('code',['0','1'])
                ->first();
            if(blank($worknotice)){
                throw new \Exception('您没有执行此操作的权限',404404);
            }
            $count=Itemadmin::sharedLock()->where('item_id',$this->item->id)->count();
            if(!$count){
                throw new \Exception('项目负责人还未配置，不能提交',404404);
            }
            $worknotice->code='2';
            $worknotice->save();

            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
            }
            /* ++++++++++ 删除相同工作推送 ++++++++++ */
            Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                ])
                ->whereIn('code',['0','1'])
                ->delete();

            /* ++++++++++ 配置项目人员 完成数 ++++++++++ */
            $count_user=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',9],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();
            /* ++++++++++ 配置项目时间规划 完成数 ++++++++++ */
            $count_time=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',10],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();

            if($count_user && $count_time){
                /* ++++++++++ 项目配置提交审查 可操作人员 ++++++++++ */
                $process=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->find(11);
                $values=[];
                /* ++++++++++ 项目配置提交审查 工作提醒推送 ++++++++++ */
                foreach ($process->processusers as $user){
                    $values[]=[
                        'item_id'=>$item->id,
                        'schedule_id'=>$process->schedule_id,
                        'process_id'=>$process->id,
                        'menu_id'=>$process->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->id,
                        'url'=>route('g_check_set_to_check',['item'=>$this->item->id],false),
                        'code'=>'0',
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
                $item->process_id=8;
                $item->code='2';
                $item->save();
            }

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'操作失败';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目审查 - 配置项目人员 ========== */
    public function check_set_itemuser(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;

            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=1 || $item->process_id!=8 ||  $item->code!='1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 流程设置 ++++++++++ */
            $process=Process::sharedLock()->find(9);
            /* ++++++++++ 是否有工作推送 ++++++++++ */
            $worknotice=Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$this->item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->whereIn('code',['0','1'])
                ->first();
            if(blank($worknotice)){
                throw new \Exception('您没有执行此操作的权限',404404);
            }
            $count=Itemuser::sharedLock()->where('item_id',$this->item->id)->count();
            if(!$count){
                throw new \Exception('项目人员还未配置，不能提交',404404);
            }
            $worknotice->code='2';
            $worknotice->save();

            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
            }
            /* ++++++++++ 删除相同工作推送 ++++++++++ */
            Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                ])
                ->whereIn('code',['0','1'])
                ->delete();

            /* ++++++++++ 配置项目负责人 完成数 ++++++++++ */
            $count_admin=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',16],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();
            /* ++++++++++ 配置项目时间规划 完成数 ++++++++++ */
            $count_time=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',10],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();

            if($count_admin && $count_time){
                /* ++++++++++ 项目配置提交审查 可操作人员 ++++++++++ */
                $process=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->find(11);
                $values=[];
                /* ++++++++++ 项目配置提交审查 工作提醒推送 ++++++++++ */
                foreach ($process->processusers as $user){
                    $values[]=[
                        'item_id'=>$item->id,
                        'schedule_id'=>$process->schedule_id,
                        'process_id'=>$process->id,
                        'menu_id'=>$process->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->id,
                        'url'=>route('g_check_set_to_check',['item'=>$this->item->id],false),
                        'code'=>'0',
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
                $item->process_id=8;
                $item->code='2';
                $item->save();
            }

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'操作失败';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目审查 - 配置项目时间规划 ========== */
    public function check_set_itemtime(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;

            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=1 || $item->process_id!=8 ||  $item->code!='1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 流程设置 ++++++++++ */
            $process=Process::sharedLock()->find(10);
            /* ++++++++++ 是否有工作推送 ++++++++++ */
            $worknotice=Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$this->item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->whereIn('code',['0','1'])
                ->first();
            if(blank($worknotice)){
                throw new \Exception('您没有执行此操作的权限',404404);
            }
            $count=Itemtime::sharedLock()->where('item_id',$this->item->id)->count();
            if(!$count){
                throw new \Exception('项目时间规划还未配置，不能提交',404404);
            }
            $worknotice->code='2';
            $worknotice->save();

            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
            }
            /* ++++++++++ 删除相同工作推送 ++++++++++ */
            Worknotice::lockForUpdate()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',$process->id],
                    ['menu_id',$process->menu_id],
                ])
                ->whereIn('code',['0','1'])
                ->delete();

            /* ++++++++++ 配置项目人员 完成数 ++++++++++ */
            $count_user=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',9],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();
            /* ++++++++++ 配置项目负责人 完成数 ++++++++++ */
            $count_admin=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',16],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();

            if($count_user && $count_admin){
                /* ++++++++++ 项目配置提交审查 可操作人员 ++++++++++ */
                $process=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->find(11);
                $values=[];
                /* ++++++++++ 项目配置提交审查 工作提醒推送 ++++++++++ */
                foreach ($process->processusers as $user){
                    $values[]=[
                        'item_id'=>$item->id,
                        'schedule_id'=>$process->schedule_id,
                        'process_id'=>$process->id,
                        'menu_id'=>$process->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->id,
                        'url'=>route('g_check_set_to_check',['item'=>$this->item->id],false),
                        'code'=>'0',
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
                $item->process_id=8;
                $item->code='2';
                $item->save();
            }

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'操作失败';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目审查 - 项目配置提交审查 ========== */
    public function check_set_to_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=8 ||  $item->code!='2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.set_to_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=8 ||  $item->code!='2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 项目配置审查 可操作人员 ++++++++++ */
                $process=Process::with(['processusers'=>function($query){
                    $query->with('role');
                }])
                    ->select(['id','schedule_id','menu_id'])
                    ->find(12);
                $values=[];
                /* ++++++++++ 项目配置审查 工作提醒推送 ++++++++++ */
                foreach ($process->processusers as $user){
                    $values[]=[
                        'item_id'=>$item->id,
                        'schedule_id'=>$process->schedule_id,
                        'process_id'=>$process->id,
                        'menu_id'=>$process->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->id,
                        'url'=>route('g_check_set_check',['item'=>$this->item->id],false),
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 项目配置审查 ========== */
    public function check_set_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[11,12])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==12 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if(($item->process_id==12 && $item->code=='23') || ($item->process_id==11 && $item->code!='2')){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 项目人员 ++++++++++ */
                $itemusers=Itemuser::with(['user'=>function($query){
                    $query->select(['id','name']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 项目负责人 ++++++++++ */
                $itemadmins=Itemadmin::with(['dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 项目时间规划 ++++++++++ */
                $itemtimes=Schedule::with(['itemtime'=>function($query){
                    $query->where('item_id',$this->item_id);
                },'processes'=>function($query){
                    $query->select(['id','schedule_id','name'])->orderBy('sort','asc');
                }])
                    ->orderBy('sort','asc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices,'itemusers'=>$itemusers,'itemadmins'=>$itemadmins,'itemtimes'=>$itemtimes];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.set_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || !in_array($item->process_id,[11,12])){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                if($item->process_id==12 && $item->code=='22'){
                    throw new \Exception('当前项目已完成【'.$item->process->name.'】，不能重复操作',404404);
                }
                if(($item->process_id==12 && $item->code=='23') || ($item->process_id==11 && $item->code!='2')){
                    throw new \Exception('当前项目【'.$item->process->name.' - '.$item->state->name.'】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                        }else{
                            /* ++++++++++ 项目启动 可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',13],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 项目启动 工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_check_item_start',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 项目启动配置 可操作人员 ++++++++++ */
                    $processes=Process::with(['processusers'=>function($query){
                        $query->with('role');
                    }])
                        ->select(['id','schedule_id','menu_id'])
                        ->where('parent_id',8)
                        ->get();
                    /* ++++++++++ 项目启动配置 工作提醒推送 ++++++++++ */
                    $values=[];
                    foreach($processes as $process){
                        switch ($process->id){
                            case 9:
                                $url=route('g_itemuser',['item'=>$this->item->id],false);
                                break;
                            case 10:
                                $url=route('g_itemtime',['item'=>$this->item->id],false);
                                break;
                            case 16:
                                $url=route('g_itemadmin',['item'=>$this->item->id],false);
                                break;
                        }
                        foreach ($process->processusers as $user){
                            $values[]=[
                                'item_id'=>$item->id,
                                'schedule_id'=>$process->schedule_id,
                                'process_id'=>$process->id,
                                'menu_id'=>$process->menu_id,
                                'dept_id'=>$user->dept_id,
                                'parent_id'=>$user->role->parent_id,
                                'role_id'=>$user->role_id,
                                'user_id'=>$user->id,
                                'url'=>$url,
                                'code'=>'0',
                                'created_at'=>date('Y-m-d H:i:s'),
                                'updated_at'=>date('Y-m-d H:i:s'),
                            ];
                        }
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
                    $item->process_id=8;
                    $item->code='1';
                }

                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目审查 - 项目启动 ========== */
    public function check_item_start(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=12 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.check.item_start';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=1 || $item->process_id!=12 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 项目初步预算 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',14],
                    ])
                    ->get();
                $values=[];
                /* ++++++++++ 项目初步预算 工作提醒推送 ++++++++++ */
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
                        'url'=>route('g_initbudget',['item'=>$this->item->id],false),
                        'code'=>'0',
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目准备 - 初步预算审查 ========== */
    public function ready_init_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[14,17]) || ($item->process_id==14 && $item->code!='2') || ($item->process_id==17 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 初步预算 ++++++++++ */
                $init_budget=Initbudget::sharedLock()->where('item_id',$this->item_id)->first();
                $item_notice=Itemnotice::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',2],
                    ])
                    ->first();
                if(blank($init_budget)){
                    throw new \Exception('初步预算报告还未添加',404404);
                }

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'init_budget'=>$init_budget,
                    'item_notice'=>$item_notice,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.ready.init_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[14,17]) || ($item->process_id==14 && $item->code!='2') || ($item->process_id==17 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                        }else{
                            /* ++++++++++ 项目筹备 可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',18],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 项目筹备 工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_ready_prepare',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 重新预算 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',20],
                        ])
                        ->get();
                    /* ++++++++++ 重新预算 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_initbudget_edit',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目准备 - 开启项目筹备 ========== */
    public function ready_prepare(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || $item->process_id!=17 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.ready.prepare';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 开启 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || $item->process_id!=17 ||  $item->code!='22'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='1';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 项目筹备 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->where([
                        ['item_id',$item->id],
                    ])
                    ->whereIn('process_id',[21,22])
                    ->get();
                /* ++++++++++ 项目筹备 工作提醒推送 ++++++++++ */
                $values=[];
                foreach($itemusers as $user){
                    switch ($user->process_id){
                        case 21: // 资金
                            $url=route('g_ready_funds',['item'=>$this->item->id],false);
                            break;
                        case 22: // 房源
                            $url=route('g_itemhouse',['item'=>$this->item->id],false);
                            break;
                    }
                    $values[]=[
                        'item_id'=>$user->item_id,
                        'schedule_id'=>$user->schedule_id,
                        'process_id'=>$user->process_id,
                        'menu_id'=>$user->menu_id,
                        'dept_id'=>$user->dept_id,
                        'parent_id'=>$user->role->parent_id,
                        'role_id'=>$user->role_id,
                        'user_id'=>$user->user_id,
                        'url'=>$url,
                        'code'=>'0',
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目准备 - 项目资金 ========== */
    public function ready_funds(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',21],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                /* ++++++++++ 初步预算 ++++++++++ */
                $init_budget=Initbudget::sharedLock()->where('item_id',$this->item_id)->first();
                /* ++++++++++ 录入明细 ++++++++++ */
                $fundses=Funds::with('bank')->sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',1],
                    ])
                    ->get();

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',2)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'init_budget'=>$init_budget,
                    'fundses'=>$fundses,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.ready.funds';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 准备完毕 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || $item->process_id!=18 ||  $item->code!='1'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查工作推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
//                /* ++++++++++ 初步预算 ++++++++++ */
//                $init_amount=Initbudget::sharedLock()->where('item_id',$this->item_id)->value('money');
//                /* ++++++++++ 录入资金 ++++++++++ */
//                $funds_amount=Funds::sharedLock()
//                    ->where([
//                        ['item_id',$this->item_id],
//                        ['cate_id',1],
//                    ])
//                    ->sum('amount');
//                if(!$funds_amount || $funds_amount<$init_amount){
//                    throw new \Exception('当前项目资金未达到初步预算的资金总额',404404);
//                }

                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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
                /* ++++++++++ 冻结房源 完成数 ++++++++++ */
                $count_house=Worknotice::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$process->schedule_id],
                        ['process_id',22],
                        ['code','2'],
                        ['created_at',$worknotice->created_at],
                    ])
                    ->count();
                if($count_house){
                    /* ++++++++++ 项目筹备审查 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',19],
                        ])
                        ->get();
                    $values=[];
                    /* ++++++++++ 项目筹备审查 工作提醒推送 ++++++++++ */
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
                            'url'=>route('g_ready_prepare_check',['item'=>$this->item->id],false),
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
                    $item->schedule_id=2;
                    $item->process_id=18;
                    $item->code='2';
                    $item->save();
                }

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目准备 - 项目房源 ========== */
    public function ready_house(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=2 || $item->process_id!=18 ||  $item->code!='1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查工作推送 ++++++++++ */
            $result=$this->hasNotice();
            $process=$result['process'];
            $worknotice=$result['worknotice'];
//            /* ++++++++++ 初步预算 ++++++++++ */
//            $init_amount=Initbudget::sharedLock()->where('item_id',$this->item_id)->value('house');
//            /* ++++++++++ 冻结房源 ++++++++++ */
//            $house_count=Itemhouse::sharedLock()->where('item_id',$this->item_id)->count();
//            if(!$house_count || $house_count<$init_amount){
//                throw new \Exception('当前冻结房源未达到初步预算的房源总数',404404);
//            }

            $worknotice->code='2';
            $worknotice->save();
            if(blank($worknotice)){
                throw new \Exception('操作失败',404404);
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
            /* ++++++++++ 项目资金 完成数 ++++++++++ */
            $count_funds=Worknotice::sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['schedule_id',$process->schedule_id],
                    ['process_id',21],
                    ['code','2'],
                    ['created_at',$worknotice->created_at],
                ])
                ->count();
            if($count_funds){
                /* ++++++++++ 项目筹备审查 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',19],
                    ])
                    ->get();
                $values=[];
                /* ++++++++++ 项目筹备审查 工作提醒推送 ++++++++++ */
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
                        'url'=>route('g_ready_prepare_check',['item'=>$this->item->id],false),
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
                $item->schedule_id=2;
                $item->process_id=18;
                $item->code='2';
                $item->save();
            }

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目准备 - 项目筹备审查 ========== */
    public function ready_prepare_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[18,19]) || ($item->process_id==18 && $item->code!='2') || ($item->process_id==19 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 初步预算 ++++++++++ */
                $init_budget=Initbudget::sharedLock()->where('item_id',$this->item_id)->first();
                /* ++++++++++ 录入资金 ++++++++++ */
                $funds_amount=Funds::with('bank')
                    ->sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',1],
                    ])
                    ->sum('amount');
                /* ++++++++++ 冻结房源 ++++++++++ */
                $house_count=Itemhouse::sharedLock()->where('item_id',$this->item_id)->count();
//                if(blank($init_budget)){
//                    throw new \Exception('初步预算报告还未添加',404404);
//                }
//                if(!$funds_amount || $funds_amount<$init_budget->money){
//                    throw new \Exception('当前项目资金未达到初步预算的资金总额',404404);
//                }
//                if(!$house_count || $house_count<$init_budget->house){
//                    throw new \Exception('当前冻结房源未达到初步预算的房源总数',404404);
//                }

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'init_budget'=>$init_budget,
                    'funds_amount'=>$funds_amount,
                    'house_count'=>$house_count,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.ready.prepare_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[18,19]) || ($item->process_id==18 && $item->code!='2') || ($item->process_id==19 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
//                /* ++++++++++ 初步预算 ++++++++++ */
//                $init_budget=Initbudget::sharedLock()->where('item_id',$this->item_id)->first();
//                /* ++++++++++ 录入资金 ++++++++++ */
//                $funds_amount=Funds::with('bank')
//                    ->sharedLock()
//                    ->where([
//                        ['item_id',$this->item_id],
//                        ['cate_id',1],
//                    ])
//                    ->sum('amount');
//                /* ++++++++++ 冻结房源 ++++++++++ */
//                $house_count=Itemhouse::sharedLock()->where('item_id',$this->item_id)->count();
//                if(blank($init_budget)){
//                    throw new \Exception('初步预算报告还未添加',404404);
//                }
//                if(!$funds_amount || $funds_amount<$init_budget->money){
//                    throw new \Exception('当前项目资金未达到初步预算的资金总额',404404);
//                }
//                if(!$house_count || $house_count<$init_budget->house){
//                    throw new \Exception('当前冻结房源未达到初步预算的房源总数',404404);
//                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                        }else{
                            /* ++++++++++ 征收范围公告 可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])

                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',23],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 征收范围公告 工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_news_add',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 项目筹备 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                        ])
                        ->whereIn('process_id',[21,22])
                        ->get();
                    /* ++++++++++ 项目筹备 工作提醒推送 ++++++++++ */
                    $values=[];
                    foreach($itemusers as $user){
                        switch ($user->process_id){
                            case 21: // 资金
                                $url=route('g_ready_funds',['item'=>$this->item->id],false);
                                break;
                            case 22: // 房源
                                $url=route('g_itemhouse',['item'=>$this->item->id],false);
                                break;
                        }
                        $values[]=[
                            'item_id'=>$user->item_id,
                            'schedule_id'=>$user->schedule_id,
                            'process_id'=>$user->process_id,
                            'menu_id'=>$user->menu_id,
                            'dept_id'=>$user->dept_id,
                            'parent_id'=>$user->role->parent_id,
                            'role_id'=>$user->role_id,
                            'user_id'=>$user->user_id,
                            'url'=>$url,
                            'code'=>'0',
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

                    $item->schedule_id=2;
                    $item->process_id=18;
                    $item->code='1';
                }

                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目准备 - 征收范围公告审查 ========== */
    public function ready_range_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[23,24]) || ($item->process_id==23 && $item->code!='2') || ($item->process_id==24 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 范围公告 ++++++++++ */
                $news=News::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',1],
                    ])
                    ->first();
                $item_notice=Itemnotice::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',3],
                    ])
                    ->first();
                if(blank($news)){
                    throw new \Exception('项目征收范围公告还未添加',404404);
                }

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'news'=>$news,
                    'item_notice'=>$item_notice,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.ready.range_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=2 || !in_array($item->process_id,[23,24]) || ($item->process_id==23 && $item->code!='2') || ($item->process_id==24 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 范围公告 ++++++++++ */
                $news=News::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['cate_id',1],
                    ])
                    ->first();
                if(blank($news)){
                    throw new \Exception('项目征收范围公告还未添加',404404);
                }

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                $news->code=$code;
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                            $news->code='21';
                        }else{
                            /* ++++++++++ 入户调查 可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',25],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 入户调查 工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_survey',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                        $news->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 征收范围公告 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',23],
                        ])
                        ->get();
                    /* ++++++++++ 征收范围公告 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_news',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();
                $news->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 调查建档 - 入户调查 ========== */
    public function survey(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',$item->process_id],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                /* ++++++++++ 地块 ++++++++++ */
                // 地块性质
                $land_props=Itemland::with('landprop')
                    ->sharedLock()
                    ->where('item_id',$this->item_id)
                    ->select([DB::raw('SUM(`area`) AS `prop_area`,COUNT(`id`) AS `prop_num`'),'land_prop_id'])
                    ->groupBy('land_prop_id')
                    ->get();
                // 地块
                $lands=Itemland::with('landprop')
                    ->withCount(['households'=>function($query){
                        $query->where('item_id',$this->item_id);
                    },'households as privates_count'=>function($query){
                        $query->where('item_id',$this->item_id)->where('type',0);
                    },'households as publics_count'=>function($query){
                        $query->where('item_id',$this->item_id)->where('type',1);
                    }])
                    ->sharedLock()
                    ->where('item_id',$this->item_id)
                    ->get();
                /* ++++++++++ 被征收户 ++++++++++ */
                // 房屋状况
                $status_households=Householddetail::sharedLock()
                    ->select([DB::raw('COUNT(*) as `status_num`'),'status'])
                    ->where('item_id',$this->item_id)
                    ->groupBy('status')
                    ->get();
                // 征收意见
                $agree_households=Householddetail::sharedLock()
                    ->select([DB::raw('COUNT(*) as `agree_num`'),'agree'])
                    ->where('item_id',$this->item_id)
                    ->groupBy('agree')
                    ->get();
                // 补偿方式
                $repay_households=Householddetail::sharedLock()
                    ->select([DB::raw('COUNT(*) as `repay_num`'),'repay_way'])
                    ->where('item_id',$this->item_id)
                    ->groupBy('repay_way')
                    ->get();
                // 批准用途
                $defuse_households=Householddetail::with('defbuildinguse')
                    ->sharedLock()
                    ->select([DB::raw('COUNT(*) as `defuse_num`'),'def_use'])
                    ->where('item_id',$this->item_id)
                    ->groupBy('def_use')
                    ->get();
                // 产权争议
                $dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('dispute',1)
                    ->count();
                // 面积争议
                $area_dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereNotIn('area_dispute',[0,3,5])
                    ->count();
                // 合法性认定
                $legal_num=Householdbuilding::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereIn('code',['91','93'])
                    ->distinct()
                    ->count('household_id');
                // 资产确认
                $assets_num=Householdassets::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->distinct()
                    ->count('household_id');
                // 公共附属物确认
                $public_num=Itempublic::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'land_props'=>$land_props,
                    'lands'=>$lands,
                    'status_households'=>$status_households,
                    'agree_households'=>$agree_households,
                    'repay_households'=>$repay_households,
                    'defuse_households'=>$defuse_households,
                    'dispute_num'=>$dispute_num,
                    'area_dispute_num'=>$area_dispute_num,
                    'legal_num'=>$legal_num,
                    'assets_num'=>$assets_num,
                    'public_num'=>$public_num,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.survey.index';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 提交 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=3 || $item->process_id!=25 ||  $item->code!='1'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                // 产权争议
                $dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('dispute',1)
                    ->count();
                if($dispute_num){
                    throw new \Exception('存在产权争议未解决',404404);
                }
                // 面积争议
                $area_dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereNotIn('area_dispute',[0,3,5])
                    ->count();
                if($area_dispute_num){
                    throw new \Exception('存在面积争议未解决',404404);
                }
                // 合法性认定
                $legal_num=Householdbuilding::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereIn('code',['91','93'])
                    ->count();
                if($legal_num){
                    throw new \Exception('存在合法性认定未解决',404404);
                }
                // 资产确认
                $assets_num=Householdassets::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($assets_num){
                    throw new \Exception('存在资产确认未解决',404404);
                }
                // 公共附属物确认
                $public_num=Itempublic::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($public_num){
                    throw new \Exception('存在公共附属物确认未解决',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                /* ++++++++++ 入户调查数据审查 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',30],
                    ])
                    ->get();
                $values=[];
                /* ++++++++++ 入户调查数据审查 工作提醒推送 ++++++++++ */
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
                        'url'=>route('g_survey_check',['item'=>$this->item->id],false),
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

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 调查建档 - 入户调查数据审查 ========== */
    public function survey_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[25,30]) || ($item->process_id==25 && $item->code!='2') || ($item->process_id==30 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                // 产权争议
                $dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('dispute',1)
                    ->count();
                if($dispute_num){
                    throw new \Exception('存在产权争议未解决',404404);
                }
                // 面积争议
                $area_dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereNotIn('area_dispute',[0,3,5])
                    ->count();
                if($area_dispute_num){
                    throw new \Exception('存在面积争议未解决',404404);
                }
                // 合法性认定
                $legal_num=Householdbuilding::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereIn('code',['91','93'])
                    ->count();
                if($legal_num){
                    throw new \Exception('存在合法性认定未解决',404404);
                }
                // 资产确认
                $assets_num=Householdassets::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($assets_num){
                    throw new \Exception('存在资产确认未解决',404404);
                }
                // 公共附属物确认
                $public_num=Itempublic::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($public_num){
                    throw new \Exception('存在公共附属物确认未解决',404404);
                }

                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.survey.survey_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,[25,30]) || ($item->process_id==25 && $item->code!='2') || ($item->process_id==30 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                // 产权争议
                $dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('dispute',1)
                    ->count();
                if($dispute_num){
                    throw new \Exception('存在产权争议未解决',404404);
                }
                // 面积争议
                $area_dispute_num=Householddetail::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereNotIn('area_dispute',[0,3,5])
                    ->count();
                if($area_dispute_num){
                    throw new \Exception('存在面积争议未解决',404404);
                }
                // 合法性认定
                $legal_num=Householdbuilding::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->whereIn('code',['91','93'])
                    ->count();
                if($legal_num){
                    throw new \Exception('存在合法性认定未解决',404404);
                }
                // 资产确认
                $assets_num=Householdassets::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($assets_num){
                    throw new \Exception('存在资产确认未解决',404404);
                }
                // 公共附属物确认
                $public_num=Itempublic::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where(function($query){
                        $query->whereNull ('number')->orWhere('number',0);
                    })
                    ->count();
                if($public_num){
                    throw new \Exception('存在公共附属物确认未解决',404404);
                }

                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                        }else{
                            /* ++++++++++ 拟定征收意见稿 可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',31],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 拟定征收意见稿 工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_itemdraft',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 入户调查 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',25],
                        ])
                        ->get();
                    /* ++++++++++ 入户调查 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_survey',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 征收决定 - 征收意见稿审查 ========== */
    public function draft_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[31,32]) || ($item->process_id==31 && $item->code!='2') || ($item->process_id==32 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 征收意见稿 ++++++++++ */
                $itemdraft=Itemdraft::sharedLock()->where('item_id',$this->item_id)->first();
                if(blank($itemdraft)){
                    throw new \Exception('征收意见稿还未添加',404404);
                }

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'itemdraft'=>$itemdraft,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.decision.draft_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[31,32]) || ($item->process_id==31 && $item->code!='2') || ($item->process_id==32 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 征收意见稿 ++++++++++ */
                $itemdraft=Itemdraft::sharedLock()->where('item_id',$this->item_id)->first();
                if(blank($itemdraft)){
                    throw new \Exception('征收意见稿还未添加',404404);
                }

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                $itemdraft->code=$code;
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                            $itemdraft->code='21';
                        }else{
                            /* ++++++++++ 发布征收意见稿  可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',33],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 发布征收意见稿  工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_draft_notice_add',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                        $itemdraft->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 拟定征收意见稿 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',31],
                        ])
                        ->get();
                    /* ++++++++++ 拟定征收意见稿 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_itemdraft',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();
                $itemdraft->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 征收决定 - 风险评估报告审查 ========== */
    public function riskreport_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[34,35]) || ($item->process_id==34 && $item->code!='2') || ($item->process_id==35 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();
                /* ++++++++++ 社会稳定风险评估报告 ++++++++++ */
                $risk_report=Itemriskreport::lockForUpdate()->where('item_id',$this->item_id)->first();
                if(blank($risk_report)){
                    throw new \Exception('社会稳定风险评估报告还未添加',404404);
                }

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                    'risk_report'=>$risk_report,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.decision.riskreport_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[34,35]) || ($item->process_id==34 && $item->code!='2') || ($item->process_id==35 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 社会稳定风险评估报告 ++++++++++ */
                $risk_report=Itemriskreport::lockForUpdate()->where('item_id',$this->item_id)->first();
                if(blank($risk_report)){
                    throw new \Exception('社会稳定风险评估报告还未添加',404404);
                }

                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                $risk_report->code=$code;
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                            $risk_report->code='21';
                        }else{
                            /* ++++++++++ 正式征收方案提交审查  可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',36],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 正式征收方案提交审查  工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_itemprogram',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                        $risk_report->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 社会稳定风险评估 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',34],
                        ])
                        ->get();
                    /* ++++++++++ 社会稳定风险评估 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_itemriskreport',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();
                $risk_report->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 征收决定 - 正式征收方案提交审查 ========== */
    public function program_to_check(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=4 || $item->process_id!=36 || $item->code != '1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查推送 ++++++++++ */
            $result=$this->hasNotice();
            $process=$result['process'];
            $worknotice=$result['worknotice'];
            $worknotice->code='2';
            $worknotice->save();
            /* ++++++++++ 检查方案 ++++++++++ */
            $program_count=Itemprogram::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            if(!$program_count){
                throw new \Exception('正式征收方案还未添加',404404);
            }
            $subject_count=Itemsubject::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            if(!$subject_count){
                throw new \Exception('补偿科目还未设置',404404);
            }
            $crowd_count=Itemcrowd::sharedLock()
                ->where('item_id',$this->item_id)
                ->get();
            if(!$crowd_count){
                throw new \Exception('特殊人群优惠政策还未设置',404404);
            }
            $house_rate_count=Itemhouserate::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            if(!$house_rate_count){
                throw new \Exception('产权调换房优惠政策还未设置',404404);
            }
            $object_count=Itemobject::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            if(!$object_count){
                throw new \Exception('其他补偿事项的补偿方案还未设置',404404);
            }
            $reward_count=Itemreward::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            if(!$reward_count){
                throw new \Exception('产权调换房的签约奖励政策还未设置',404404);
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

            /* ++++++++++ 正式征收方案审查 可操作人员 ++++++++++ */
            $itemusers=Itemuser::with(['role'=>function($query){
                $query->select(['id','parent_id']);
            }])
                ->sharedLock()
                ->where([
                    ['item_id',$item->id],
                    ['process_id',37],
                ])
                ->get();
            $values=[];
            /* ++++++++++ 正式征收方案审查 工作提醒推送 ++++++++++ */
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
                    'url'=>route('g_program_check',['item'=>$this->item->id],false),
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
            $msg = '操作成功';
            $sdata = null;
            $edata = null;
            $url = route('g_itemprocess',['item'=>$this->item_id]);
            DB::commit();
        }catch (\Exception $exception){
            $code = 'error';
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '操作成功';
            $sdata = null;
            $edata = null;
            $url = null;
            DB::rollBack();
        }
        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 征收决定 - 正式征收方案审查 ========== */
    public function program_check(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[36,37]) || ($item->process_id==36 && $item->code!='2') || ($item->process_id==37 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 更新项目状态 ++++++++++ */
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='21';
                $item->save();

                /* ++++++++++ 工作日志 ++++++++++ */
                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=[
                    'item'=>$item,
                    'worknotices'=>$worknotices,
                ];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.decision.program_check';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || !in_array($item->process_id,[36,37]) || ($item->process_id==36 && $item->code!='2') || ($item->process_id==37 && $item->code!='21')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                /* ++++++++++ 正式征收方案 ++++++++++ */
                $item_program=Itemprogram::lockForUpdate()->where('item_id',$this->item_id)->first();
                if(blank($item_program)){
                    throw new \Exception('正式征收方案还未添加',404404);
                }
                /* ++++++++++ 表单验证 ++++++++++ */
                $rules=[
                    'code'=>'required|in:22,23',
                    'infos'=>'required',
                ];
                $messages=[
                    'code.required'=>'请选择审查结果',
                    'code.in'=>'请选择正确的审查结果',
                    'infos.required'=>'请填写审查意见',
                ];
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                $code=$request->input('code');

                /* ++++++++++ 执行 ++++++++++ */
                $worknotice->fill($request->input());
                $worknotice->code=$code;
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
                }
                $item_program->code=$code;
                /* ++++++++++ 审查通过 ++++++++++ */
                if($code=='22'){
                    /* ++++++++++ 同级完成数 ++++++++++ */
                    $worknotice_sames=Worknotice::sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['parent_id',$worknotice->parent_id],
                            ['code','22'],
                        ])
                        ->count();
                    /* ++++++++++ 同级完成数达到限制 ++++++++++ */
                    if($worknotice_sames>=$process->number){
                        /* ++++++++++ 删除同级工作推送 ++++++++++ */
                        Worknotice::lockForUpdate()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['parent_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->delete();

                        /* ++++++++++ 是否存在上级 ++++++++++ */
                        $worknotice_par=Worknotice::sharedLock()
                            ->where([
                                ['item_id',$item->id],
                                ['schedule_id',$process->schedule_id],
                                ['process_id',$process->id],
                                ['menu_id',$process->menu_id],
                                ['role_id',$worknotice->parent_id],
                                ['code','20'],
                            ])
                            ->count();
                        /* ++++++++++ 存在上级 ++++++++++ */
                        if($worknotice_par){
                            $item->schedule_id=$worknotice->schedule_id;
                            $item->process_id=$worknotice->process_id;
                            $item->code='21';
                            $item_program->code='21';
                        }else{
                            /* ++++++++++ 发布征收决定公告  可操作人员 ++++++++++ */
                            $itemusers=Itemuser::with(['role'=>function($query){
                                $query->select(['id','parent_id']);
                            }])
                                ->where([
                                    ['item_id',$item->id],
                                    ['process_id',38],
                                ])
                                ->get();
                            $values=[];
                            /* ++++++++++ 发布征收决定公告  工作提醒推送 ++++++++++ */
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
                                    'url'=>route('g_program_notice_add',['item'=>$this->item->id],false),
                                    'code'=>'0',
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
                        }
                    }
                    /* ++++++++++ 同级完成数未达限制 ++++++++++ */
                    else{
                        $item->schedule_id=$worknotice->schedule_id;
                        $item->process_id=$worknotice->process_id;
                        $item->code='21';
                        $item_program->code='21';
                    }
                }
                /* ++++++++++ 审查驳回 ++++++++++ */
                else{
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$process->schedule_id],
                            ['process_id',$process->id],
                            ['menu_id',$process->menu_id],
                            ['code','20'],
                        ])
                        ->delete();

                    /* ++++++++++ 正式征收方案提交审查 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',36],
                        ])
                        ->get();
                    /* ++++++++++ 正式征收方案提交审查 工作提醒推送 ++++++++++ */
                    $values=[];
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
                            'url'=>route('g_program',['item'=>$this->item->id],false),
                            'code'=>'0',
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
                }

                $item->save();
                $item_program->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目实施 - 项目开始实施 ========== */
    public function pay_start(Request $request){
        if($request->isMethod('get')){
            /* ********** 查询 ********** */
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || $item->process_id!=38 ||  $item->code!='2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotices=Worknotice::with(['process'=>function($query){
                    $query->select(['id','name','type']);
                },'dept'=>function($query){
                    $query->select(['id','name']);
                },'role'=>function($query){
                    $query->select(['id','name']);
                },'user'=>function($query){
                    $query->select(['id','name']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('schedule_id',$process->schedule_id)
                    ->whereNotIn('code',['0','20'])
                    ->orderBy('updated_at','desc')
                    ->orderBy('id','desc')
                    ->sharedLock()
                    ->get();

                $code='success';
                $msg='查询成功';
                $sdata=['item'=>$item,'worknotices'=>$worknotices];
                $edata=null;
                $url=null;

                $view='gov.itemprocess.pay.pay_start';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='gov.error';
            }
            DB::commit();

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 审查结果 ********** */
        else{
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || $item->process_id!=38 ||  $item->code!='2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];

                $worknotice->code='2';
                $worknotice->save();
                if(blank($worknotice)){
                    throw new \Exception('操作失败',404404);
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

                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code='1';
                $item->save();

                $code='success';
                $msg='操作成功';
                $sdata=null;
                $edata=null;
                $url=route('g_itemprocess',['item'=>$this->item->id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 项目实施 - 项目实施完成 ========== */
    public function pay_end(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=5 || $item->process_id!=39 ||  $item->code!='1'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查操作权限 ++++++++++ */
            $count=Itemuser::sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['schedule_id',5],
                    ['process_id',44],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                throw new \Exception('您没有执行此操作的权限',404404);
            }

            $item->schedule_id=5;
            $item->process_id=44;
            $item->code='2';
            $item->save();

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 项目审计 - 项目结束 ========== */
    public function item_end(Request $request){
        DB::beginTransaction();
        try{
            $item=$this->item;
            if(blank($item)){
                throw new \Exception('项目不存在',404404);
            }
            /* ++++++++++ 检查项目状态 ++++++++++ */
            if($item->schedule_id!=6 || $item->process_id!=45 ||  $item->code!='2'){
                throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
            }
            /* ++++++++++ 检查操作权限 ++++++++++ */
            $count=Itemuser::sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['schedule_id',6],
                    ['process_id',46],
                    ['user_id',session('gov_user.user_id')],
                ])
                ->count();
            if(!$count){
                throw new \Exception('您没有执行此操作的权限',404404);
            }

            $item->schedule_id=6;
            $item->process_id=46;
            $item->code='2';
            $item->save();

            $code='success';
            $msg='操作成功';
            $sdata=null;
            $edata=null;
            $url=route('g_itemprocess',['item'=>$this->item->id]);

            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;

            DB::rollBack();
        }

        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }
}