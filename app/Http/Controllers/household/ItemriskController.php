<?php
/*
|--------------------------------------------------------------------------
| 被征户-社会风险评估
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;

use App\Http\Model\Assess;
use App\Http\Model\Itemhouse;
use App\Http\Model\Itemrisk;
use App\Http\Model\Household;
use App\Http\Model\Layout;
use App\Http\Model\Itemtopic;
use App\Http\Model\Itemrisktopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class  ItemriskController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    public function info(Request $request)
    {
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        try{
            $itemrisk = Itemrisk::with(
                ['item' => function ($query) {
                    $query->select(['id', 'name']);
                }, 'building' => function ($query) {
                    $query->select(['id', 'building']);
                }, 'land' => function ($query) {
                    $query->select(['id', 'address']);
                }])
                ->where('household_id', $this->household_id)
                ->where('item_id', $this->item_id)
                ->sharedLock()
                ->first();
            if(blank($itemrisk)){
                throw new \Exception('没有参与评估调查',404404);
            }
            $itemtopic=Itemrisktopic::sharedLock()
                ->where('risk_id',$itemrisk->id)
                ->where('item_id',$this->item_id)
                ->orderBy('topic_id','asc')
                ->get();

            $code = 'success';
            $msg = '获取成功';
            $sdata = [
                'topic'=>$itemtopic,
                'risk'=>$itemrisk
            ];
            $edata = null;
            $url = null;
        }catch (\Exception $exception){
            $code = 'warning';
            $msg = $exception->getCode()==404404?$exception->getMessage():'网络错误';
            $sdata = null;
            $edata = null;
            $url = null;
        }
        DB::commit();

        $view = 'household.itemrisk.info';
        $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
        if ($request->ajax()) {
            return response()->json($result);
        } else {
            return view($view)->with($result);
        }
    }

    /*社会稳定风险评估-添加页面*/
    public function add(Request $request)
    {
        $land_id = session('household_user.land_id');
        $building_id = session('household_user.building_id');


        if ($request->isMethod('get')) {

            DB::beginTransaction();
            try{
                $itemrisk = Itemrisk::with(
                    ['item' => function ($query) {
                        $query->select(['id', 'name']);
                    }, 'land' => function ($query) {
                        $query->select(['id', 'address']);
                    }, 'building' => function ($query) {
                        $query->select(['id', 'building']);
                    }])
                    ->where('household_id', $this->household_id)
                    ->where('item_id', $this->item_id)
                    ->sharedLock()
                    ->first();
                if (filled($itemrisk)) {
                    throw new \Exception('社会稳定风险评估不允许重复添加！', 404404);
                }

               $itemtopic=Itemtopic::with(['topic' => function ($query) {
                   $query->select(['id', 'name']);
                    }])
                   ->where('item_id', $this->item_id)
                   ->orderBy('id','asc')
                   ->sharedLock()
                   ->get();

                $household = Household::with(
                    ['item' => function ($query) {
                        $query->select(['id', 'name']);
                    }, 'itemland' => function ($query) {
                        $query->select(['id', 'address']);
                    }, 'itembuilding' => function ($query) {
                        $query->select(['id', 'building']);
                    }])
                    ->sharedLock()
                    ->find(session('household_user.user_id'));
                if($household->code!=65) {
                    throw new \Exception('您现处于【' . $household->state->name . '】状态，不能进行意见调查', 404404);
                }
                $household->code=66;
                $household->save();

                $household->layout = Layout::pluck('name', 'id');
                $result = ['code' => 'success', 'message' => '请求成功', 'sdata' =>['household'=>$household,'topic'=>$itemtopic] , 'edata' => new Itemrisk(), 'url' => null];
                DB::commit();

                $view='household.itemrisk.add';
            }catch (\Exception $exception){
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $result = ['code' => 'error', 'message' => $msg, 'sdata' => null, 'edata' => null, 'url' => null];
                DB::rollBack();

                $view='household.error';
            }

            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view($view)->with($result);
            }
        } /*数据保存*/
        else {
            $model = new Itemrisk();
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'agree' => 'required',
                'repay_way' => 'required',
                'layout_id' => 'required',
                'transit_way' => 'required',
                'move_way' => 'required',
                'move_fee' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }

            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {

                if($request->input('repay_way') ==0 && $request->input('transit_way')!=0){
                    throw new \Exception('选择货币补偿只能选择货币过渡',404404);
                }
                $household = Household::sharedLock()
                    ->find(session('household_user.user_id'));
                if($household->code!=65){
                    throw new \Exception('您现处于【'.$household->state->name.'】状态，不能进行意见调查', 404404);
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $itemrisk = $model;
                $itemrisk->fill($request->all());
                $itemrisk->addOther($request);
                $itemrisk->item_id = $this->item_id;
                $itemrisk->land_id = $land_id;
                $itemrisk->household_id = $this->household_id;
                $itemrisk->building_id = $building_id;

                $itemrisk->save();
                if (blank($itemrisk)) {
                    throw new \Exception('添加失败', 404404);
                }
                if(filled($request->input('topic'))){
                    /*自选话题*/
                    foreach ($request->input('topic') as $key=>$value){
                        $topic_data[]=[
                            'item_id'=>$this->item_id,
                            'risk_id'=>$itemrisk->id,
                            'topic_id'=>$key,
                            'answer'=>$value,
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s')
                        ];
                    }
                    $field=['item_id','risk_id','topic_id','answer','created_at','updated_at'];
                    $sqls=batch_update_or_insert_sql('item_risk_topic',$field,$topic_data,'updated_at');
                    if(!$sqls){
                        throw new \Exception('数据错误',404404);
                    }
                    foreach($sqls as $sql){
                        DB::statement($sql);
                    }
                }

                $household = Household::sharedLock()
                    ->find(session('household_user.user_id'));
                $household->code=66;
                $household->save();
                if(blank($household)){
                    throw new \Exception('保存失败!',404404);
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $itemrisk;
                $edata = null;
                $url = route('h_itemrisk_info', ['item' => $this->item_id]);
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
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);
        }
    }

    /*社会稳定风险评估-修改页面*/
    public function edit(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            $result = ['code' => 'error', 'message' => '请先选择数据', 'sdata' => null, 'edata' => null, 'url' => null];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            try{
                $household = Household::with(
                    ['item' => function ($query) {
                        $query->select(['id', 'name']);
                    }, 'itemland' => function ($query) {
                        $query->select(['id', 'address']);
                    }, 'itembuilding' => function ($query) {
                        $query->select(['id', 'building']);
                    }])
                    ->sharedLock()
                    ->find($this->household_id);
                if (blank($household)){
                    throw new \Exception('被征户不存在', 404404);
                }

                $risk = Itemrisk::with(['item' => function ($query) {
                    $query->select(['id', 'name']);
                }, 'building' => function ($query) {
                    $query->select(['id', 'building']);
                }, 'land' => function ($query) {
                    $query->select(['id', 'address']);
                }])
                    ->sharedLock()
                    ->find($id);
                if (blank($risk)){
                    throw new \Exception('意见调查不存在', 404404);
                }
                $risk->layout = Layout::pluck('name', 'id');

                $itemtopic=Itemrisktopic::sharedLock()
                    ->where('risk_id',$id)
                    ->where('item_id',session('household_user.item_id'))
                    ->orderBy('topic_id','asc')
                    ->get();
                $result = ['code' => 'success', 'message' => '请求成功', 'sdata' =>['household'=>$household,'risk'=>$risk,'topic'=>$itemtopic] , 'edata' => new Itemrisk(), 'url' => null];
                $view='household.itemrisk.edit';
                DB::commit();
            }catch (\Exception $exception){
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $result = ['code' => 'error', 'message' => $msg, 'sdata' => null, 'edata' => null, 'url' => null];
                $view='household.error';
                DB::rollBack();
            }

            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view($view)->with($result);
            }
        } else {
            $model = new Itemrisk();
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'agree' => 'required',
                'repay_way' => 'required'
            ];
            $messages = [
                'required' => ':attribute必须填写'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try {
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $itemrisk = Itemrisk::lockForUpdate()->find($id);
                if (blank($itemrisk)) {
                    throw new \Exception('指定数据项不存在', 404404);
                }

                if($request->input('repay_way') ==0 && $request->input('transit_way')!=0){
                    throw new \Exception('选择货币补偿只能选择货币过渡',404404);
                }

                /* ++++++++++ 处理其他数据 ++++++++++ */
                $itemrisk->fill($request->all());
                $itemrisk->editOther($request);
                $itemrisk->save();
                if (blank($itemrisk)) {
                    throw new \Exception('修改失败', 404404);
                }

                /*自选话题*/
                Itemrisktopic::where([['item_id',$this->item_id],['risk_id',$id]])
                    ->delete();
                foreach ($request->input('topic') as $key=>$value){
                    $topic_data[]=[
                        'item_id'=>$this->item_id,
                        'risk_id'=>$itemrisk->id,
                        'topic_id'=>$key,
                        'answer'=>$value,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s')
                    ];
                }
                $field=['item_id','risk_id','topic_id','answer','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('item_risk_topic',$field,$topic_data,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach($sqls as $sql){
                    DB::statement($sql);
                }

                $item_household=Household::sharedLock()
                                ->find($this->household_id);
                $item_household->code=67;
                $item_household->save();


                $code = 'success';
                $msg = '修改成功';
                $sdata = $itemrisk;
                $edata = null;
                $url = route('h_itemrisk_info');
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $sdata = $exception;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);
        }

    }
}