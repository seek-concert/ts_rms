<?php
/*
|--------------------------------------------------------------------------
| 项目信息
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\Estate;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Funds;
use App\Http\Model\Household;
use App\Http\Model\Householddetail;
use App\Http\Model\Item;
use App\Http\Model\Itemadmin;
use App\Http\Model\Itemhouse;
use App\Http\Model\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IteminfoController extends BaseitemController
{
    /* ========== 初始化 ========== */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 项目概述 ========== */
    public function index(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            if(blank($this->item)){
                throw new \Exception('项目不存在',404404);
            }
            //项目概述
            $itemadmins=Itemadmin::with(['user'=>function($query){
                $query->select(['id','name']);
            }])
                ->sharedLock()
                ->where('item_id',$this->item_id)
                ->get();
            $household_num=Household::sharedLock()->where('item_id',$this->item_id)->count();

            //被征收户状态
            $household_nums = Household::sharedLock()
                ->with(['state'])
                ->select([DB::raw('COUNT(*) as `household_num`'),'code'])
                ->where('item_id',$this->item_id)
                ->groupBy('code')
                ->get();
            $data['household_nums'] = $household_nums;
            //房源使用情况
            $house_nums = Itemhouse::sharedLock()
                ->select([DB::raw('count(item_house.house_id) as `house_num`'),'sc.name as names'])
                ->leftJoin('house as h','h.id','=','item_house.house_id')
                ->leftJoin('a_state_code as sc','sc.code','=','h.code')
                ->where('item_house.item_id',$this->item_id)
                ->groupBy('names')
                ->get();
            $data['house_nums'] = $house_nums;
            //资金使用情况
            $funds_income_sums = Funds::sharedLock()
                ->select([DB::raw('sum(amount) as `funds_sum`')])
                ->where('item_id',$this->item_id)
                ->where('amount','>',0)
                ->first();
            $funds_expenditure_sums = Funds::sharedLock()
                ->select([DB::raw('sum(amount) as `funds_sum`')])
                ->where('item_id',$this->item_id)
                ->where('amount','<',0)
                ->first();
            $funds[0]['funds_sums'] = $funds_income_sums['funds_sum'];
            $funds[0]['name_info'] = '收入总额';
            $funds[1]['funds_sums'] = $funds_expenditure_sums['funds_sum']?:'0';
            $funds[1]['name_info'] = '支出总额';
            $data['funds'] = $funds;
            //兑付比例
            $pay_count = Household::sharedLock()
                ->select([DB::raw('count(*) as `pay_count`')])
                ->where('item_id',$this->item_id)
                ->where('code',80)
                ->first();
            $pay_counts = Household::sharedLock()
                ->select([DB::raw('count(*) as `pay_count`')])
                ->where('item_id',$this->item_id)
                ->where('code','<>',80)
                ->first();
            $pay[0]['pay_count'] = $pay_count['pay_count'];
            $pay[0]['name_info'] = '已兑付';
            $pay[1]['pay_count'] = $pay_counts['pay_count'];
            $pay[1]['name_info'] = '未兑付或兑付中';
            $data['pay_count'] = $pay;

            $code='success';
            $msg='查询成功';
            $sdata=['item'=>$this->item,'itemadmins'=>$itemadmins,'household_num'=>$household_num];
            $edata=$data;
            $url=null;

            $view='gov.iteminfo.index';
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

    /* ========== 项目信息 ========== */
    public function info(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
            $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');

            if(blank($this->item)){
                throw new \Exception('项目不存在',404404);
            }

            //摸底调查
            $household = Household::sharedLock()
                ->select([DB::raw('COUNT(*) as `survey_num`')])
                ->where('item_id',$this->item_id)
                ->first();
            $household_detail = Householddetail::sharedLock()
                ->select([DB::raw('count(*) as `survey_num`')])
                ->where('item_id',$this->item_id)
                ->first();
            $survey_nums = [];
            $survey_nums[0]['survey_num'] = $household['survey_num']-$household_detail['survey_num'];
            $survey_nums[0]['name_info'] = '未调查';
            $survey_nums[1]['survey_num'] = $household_detail['survey_num'];
            $survey_nums[1]['name_info'] = '已调查';
            //确权确户
            $household_count = Household::sharedLock()
                ->select([DB::raw('count(*) as `household_count`')])
                ->whereBetween('code',[60,62])
                ->where('item_id',$this->item_id)
                ->first();
            $household_nums = Household::sharedLock()
                ->select([DB::raw('count(*) as `household_num`')])
                ->where('code','>',62)
                ->where('item_id',$this->item_id)
                ->first();
            $property_nums = [];
            $property_nums[0]['property_num'] =$household_count['household_count'];
            $property_nums[0]['name_info'] = '未确权';
            $property_nums[1]['property_num'] = $household_nums['household_num'];
            $property_nums[1]['name_info'] = '已确权';
            //评估状况
            $com_count = Estate::sharedLock()
                ->with(['state'])
                ->select([DB::raw('count(*) as `com_count`'),'code'])
                ->whereBetween('code',[0,136])
                ->where('item_id',$this->item_id)
                ->groupBy('code')
                ->get();
            //协议签约
            $contract_count = Household::sharedLock()
                ->with(['state'])
                ->select([DB::raw('count(*) as `contract_count`'),'code'])
                ->whereBetween('code',[60,80])
                ->where('item_id',$this->item_id)
                ->groupBy('code')
                ->get();

            $data['survey_nums'] = $survey_nums;
            $data['property_nums'] = $property_nums;
            $data['com_count'] = $com_count;
            $data['contract_count'] = $contract_count;
            $data['item'] = $this->item;
            $code='success';
            $msg='查询成功';
            $sdata=$data;
            $edata=$file_cates;
            $url=null;

            $view='gov.iteminfo.info';
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


    /* ========== 修改项目 ========== */
    public function edit(Request $request){
        if($request->isMethod('get')){
            try{
                if(blank($this->item)){
                    throw  new \Exception('数据不存在',404404);
                }

                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($this->item->schedule_id!=1 || $this->item->process_id!=1 || $this->item->code != '2'){
                    throw new \Exception('当前项目处于【'.$this->item->schedule->name.' - '.$this->item->process->name.'('.$this->item->state->name.')】，不能进行当前操作',404404);
                }

                /* ********** 获取数据 ********** */
                DB::beginTransaction();

                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();

                DB::commit();

                $code='success';
                $msg='查询成功';
                $sdata=$this->item;
                $edata=$file_cates;
                $url=null;

                $view='gov.iteminfo.edit';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;

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
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            DB::beginTransaction();
            try{
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

                if(blank($this->item)){
                    throw new \Exception('项目不存在',404404);
                }
                /* ++++++++++ 检查项目状态 ++++++++++ */
                if($this->item->schedule_id!=1 || $this->item->process_id!=1 || $this->item->code != '2'){
                    throw new \Exception('当前项目处于【'.$this->item->schedule->name.' - '.$this->item->process->name.'('.$this->item->state->name.')】，不能进行当前操作',404404);
                }

                $item=$this->item;

                $item->fill($request->input());

                $item->save();
                if(blank($item)){
                    throw new \Exception('保存失败',404404);
                }

                $code='success';
                $msg='保存成功';
                $sdata=$item;
                $edata=null;
                $url=route('g_iteminfo_info',['item'=>$this->item_id]);

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