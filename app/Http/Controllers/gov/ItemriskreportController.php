<?php
/*
|--------------------------------------------------------------------------
| 项目-风险评估报告
|--------------------------------------------------------------------------
*/
namespace  App\Http\Controllers\gov;
use App\Http\Model\Household;
use App\Http\Model\Itemrisk;
use App\Http\Model\Itemriskreport;
use App\Http\Model\Itemuser;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemriskreportController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 详情页 ========== */
    public function index(Request $request){
        /* ********** 查询条件 ********** */
        $where[] = ['item_id',$this->item_id];
        /* ********** 查询 ********** */
        $model=new Itemriskreport();
        DB::beginTransaction();
        try{
            /* ********** 评估报告 ********** */
            $itemriskreport=$model
                ->with(['state'=>function($query){
                    $query->select(['code','name']);
                }])
                ->where($where)
                ->sharedLock()
                ->first();
            /* ********** 风险调查 ********** */
            $item_risk_num=Itemrisk::sharedLock()
                ->where('item_id',$this->item_id)
                ->select([DB::raw('COUNT(`household_id`) AS `risk_num`'),'agree'])
                ->groupBy('agree')
                ->get();
            $number=$item_risk_num->sum('risk_num');
            $household_num=Household::sharedLock()->where('item_id',$this->item_id)->count();

            /*补偿方式*/
            $repay_way=Itemrisk::sharedLock()
                ->where('item_id',$this->item_id)
                ->select([DB::raw('COUNT(`household_id`) AS `risk_num`'),'repay_way'])
                ->groupBy('repay_way')
                ->get();

            /*过渡方式*/
            $transit_way=Itemrisk::sharedLock()
                ->where('item_id',$this->item_id)
                ->select([DB::raw('COUNT(`household_id`) AS `risk_num`'),'transit_way'])
                ->groupBy('transit_way')
                ->get();

            /*搬迁方式*/
            $move_way=Itemrisk::sharedLock()
                ->where('item_id',$this->item_id)
                ->select([DB::raw('COUNT(`household_id`) AS `risk_num`'),'move_way'])
                ->groupBy('move_way')
                ->get();

            $code='success';
            $msg='查询成功';
        }catch(\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
        }

        $sdata=[
            'item_id'=>$this->item_id,
            'itemriskreport'=>$itemriskreport,
            'item_risk_num'=>$item_risk_num,
            'number'=>$number,
            'household_num'=>$household_num,
            'repay_way'=>$repay_way,
            'transit_way'=>$transit_way,
            'move_way'=>$move_way,
        ];
        $edata=null;
        $url=null;
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];

        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.itemriskreport.index')->with($result);
        }
    }

    /* ========== 添加页 ========== */
    public function add(Request $request){
        $item_id=$this->item_id;
        $model=new Itemriskreport();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($item->schedule_id!=4 || $item->process_id!=33 || $item->code != '2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',33],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->count();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }

                /* ********** 评估报告 ********** */
                $risk_report=Itemriskreport::sharedLock()->where('item_id',$this->item_id)->first();
                if(filled($risk_report)){
                    throw new \Exception('社会稳定风险评估报告已添加',404404);
                }
                /* ********** 风险调查 ********** */
                $item_risk_num=Itemrisk::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->select([DB::raw('COUNT(`household_id`) AS `risk_num`'),'agree'])
                    ->groupBy('agree')
                    ->get();
                $number=$item_risk_num->sum('risk_num');
                $household_num=Household::sharedLock()->where('item_id',$this->item_id)->count();
                if($number<$household_num){
                    throw new \Exception('还有被征收户没有完成风险评估调查',404404);
                }

                $code = 'success';
                $msg = '请求成功';
                $sdata = [
                    'item_id'=>$item_id,
                    'item_risk_num'=>$item_risk_num,
                ];
                $edata = new Itemriskreport();
                $url = null;

                $view='gov.itemriskreport.add';
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
        /* ********** 保存 ********** */
        else{
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'content'=>'required',
                'name'=>'required',
                'agree' => 'required',
                'picture' => 'required',
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
                if($item->schedule_id!=4 || $item->process_id!=33 || $item->code!='2'){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }

                /* ********** 评估报告 ********** */
                $risk_report=Itemriskreport::sharedLock()->where('item_id',$this->item_id)->first();
                if(filled($risk_report)){
                    throw new \Exception('社会稳定风险评估报告已添加',404404);
                }
                /* ********** 风险调查 ********** */
                $item_risk_num=Itemrisk::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->count();
                $household_num=Household::sharedLock()->where('item_id',$this->item_id)->count();
                if($item_risk_num<$household_num){
                    throw new \Exception('还有被征收户没有完成风险评估调查',404404);
                }
                /* ++++++++++ 是否有推送 ++++++++++ */
                $result=$this->hasNotice();
                $process=$result['process'];
                $worknotice=$result['worknotice'];
                $worknotice->code='2';
                $worknotice->save();

                /* ++++++++++ 批量赋值 ++++++++++ */
                $itemriskreport = $model;
                $itemriskreport->fill($request->all());
                $itemriskreport->addOther($request);
                $itemriskreport->item_id=$this->item_id;
                $itemriskreport->code='20';
                $itemriskreport->save();
                if (blank($itemriskreport)) {
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

                /* ++++++++++ 风险评估报告审查 可操作人员 ++++++++++ */
                $itemusers=Itemuser::with(['role'=>function($query){
                    $query->select(['id','parent_id']);
                }])
                    ->where([
                        ['item_id',$item->id],
                        ['process_id',35],
                    ])
                    ->get();
                $values=[];
                /* ++++++++++ 风险评估报告审查 工作提醒推送 ++++++++++ */
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
                        'url'=>route('g_riskreport_check',['item'=>$this->item->id],false),
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

                /*修改被征户状态*/
                $household=Household::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where('code',66)
                    ->update(['code'=>67]);
                if(blank($household)){
                    throw new \Exception('操作失败！',404404);
                }

                /*更改项目状态*/
                $item->schedule_id=$worknotice->schedule_id;
                $item->process_id=$worknotice->process_id;
                $item->code=$worknotice->code;
                $item->save();

                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemriskreport;
                $edata = null;
                $url = route('g_itemriskreport',['item'=>$this->item_id]);
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
        $model=new Itemriskreport();
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,['34','35']) || ($item->process_id =='34' && $item->code!='2') || ($item->process_id =='35' && $item->code!='23')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',34],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }

                $risk_report=Itemriskreport::sharedLock()->where('item_id',$this->item_id)->first();
                if(blank($risk_report)){
                    throw new \Exception('社会稳定风险评估报告还未添加',404404);
                }

                $code='success';
                $msg='请求成功';
                $sdata=['item'=>$this->item,'risk_report'=>$risk_report,'item_id'=>$this->item_id,];
                $edata=$model;
                $url=null;

                $view='gov.itemriskreport.edit';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;

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
                'name'=>'required',
                'agree' => 'required',
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];

            $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
            if($validator->fails()){
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            DB::beginTransaction();
            try{
                $item=$this->item;
                if(blank($item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if(!in_array($item->process_id,['34','35']) || ($item->process_id =='34' && $item->code!='2') || ($item->process_id =='35' && $item->code!='23')){
                    throw new \Exception('当前项目处于【'.$item->schedule->name.' - '.$item->process->name.'('.$item->state->name.')】，不能进行当前操作',404404);
                }
                /* ++++++++++ 检查操作权限 ++++++++++ */
                $count=Itemuser::sharedLock()
                    ->where([
                        ['item_id',$item->id],
                        ['schedule_id',$item->schedule_id],
                        ['process_id',34],
                        ['user_id',session('gov_user.user_id')],
                    ])
                    ->get();
                if(!$count){
                    throw new \Exception('您没有执行此操作的权限',404404);
                }
                /* ++++++++++ 审查驳回处理 ++++++++++ */
                if($item->process_id==35 && $item->code=='23'){
                    /* ++++++++++ 删除相同工作推送 ++++++++++ */
                    Worknotice::lockForUpdate()
                        ->where([
                            ['item_id',$item->id],
                            ['schedule_id',$item->schedule_id],
                            ['process_id',34],
                            ['code','0'],
                        ])
                        ->delete();
                    /* ++++++++++ 风险评估报告审查 可操作人员 ++++++++++ */
                    $itemusers=Itemuser::with(['role'=>function($query){
                        $query->select(['id','parent_id']);
                    }])
                        ->sharedLock()
                        ->where([
                            ['item_id',$item->id],
                            ['process_id',35],
                        ])
                        ->get();
                    $values=[];
                    /* ++++++++++ 风险评估报告审查 工作提醒推送 ++++++++++ */
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
                            'url'=>route('g_riskreport_check',['item'=>$this->item->id],false),
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
                    $item->process_id=34;
                    $item->code='22';
                    $item->save();
                }
                /* ++++++++++ 社会稳定风险评估报告 ++++++++++ */
                $risk_report=Itemriskreport::lockForUpdate()->where('item_id',$this->item_id)->first();
                if(blank($risk_report)){
                    throw new \Exception('社会稳定风险评估报告还未添加',404404);
                }

                /* ++++++++++ 批量赋值 ++++++++++ */
                $risk_report->fill($request->input());
                $risk_report->editOther($request);
                $risk_report->code='22';
                $risk_report->save();
                if(blank($risk_report)){
                    throw new \Exception('保存失败',404404);
                }

                $code='success';
                $msg='保存成功';
                $sdata=['news'=>$risk_report];
                $edata=null;
                $url=route('g_itemriskreport',['item'=>$this->item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'保存失败';
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
}