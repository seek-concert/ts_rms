<?php
/*
|--------------------------------------------------------------------------
| 被征户--兑付 汇总
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Itemreward;
use App\Http\Model\Itemrisk;
use App\Http\Model\Item;
use App\Http\Model\Assess;
use App\Http\Model\Assets;
use App\Http\Model\Estate;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Household;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdobject;
use App\Http\Model\Householdmember;
use App\Http\Model\Menu;
use App\Http\Model\Paytransit;
use App\Http\Model\Itemprogram;
use App\Http\Model\Pact;
use App\Http\Model\Pay;
use App\Http\Model\House;
use App\Http\Model\Itemhouserate;
use App\Http\Model\Payhouse;
use App\Http\Model\Paybuilding;
use App\Http\Model\Payobject;
use App\Http\Model\Paypublic;
use App\Http\Model\Paysubject;
use App\Http\Model\Payunit;
use App\Http\Model\Publicdetail;
use App\Http\Model\Payhousebak;
use App\Http\Controllers\Controller;
use App\Http\Model\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayController extends BaseController{

    public function info(Request $request){
        $itemrisk = Itemrisk::sharedLock()
            ->where([
                ['item_id', session('household_user.item_id')],
                ['household_id', $this->household_id]
            ])
            ->first();;
        if (blank($itemrisk)) {
            $result=['code'=>'error','message'=>'暂无社会稳定风险评估','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);
        }

        $household = Household::with('state')
            ->sharedLock()
            ->select(['id', 'item_id', 'land_id', 'building_id', 'unit', 'floor', 'number', 'type', 'code'])
            ->where([
                ['item_id', $this->item_id],
                ['id', $this->household_id]
            ])
            ->first();
        if (blank($household)) {
            $result=['code'=>'error','message'=>'被征收户不存在','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);
        }

        $household_detail = Householddetail::sharedLock()
            ->where([
                ['item_id', $this->item_id],
                ['household_id', $this->household_id],
            ])
            ->select(['item_id','household_id','register','has_assets','area_dispute','repay_way','def_use'])
            ->first();
      if (blank($household_detail)){
          $result=['code'=>'error','message'=>'暂无被征户详情','sdata'=>null,'edata'=>null,'url'=>null];
          return view('household.error')->with($result);
      }
        if(!in_array($household_detail->getOriginal('area_dispute'),[0,3])){
            $result=['code'=>'error','message'=>'被征收户存在面积争议','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);
        }
        /* ++++++++++ 评估汇总 ++++++++++ */
        $assess = Assess::sharedLock()
            ->where([
                ['item_id',$this->item_id],
                ['household_id',$this->household_id],
                ['code','136']
            ])
            ->first();

        if (blank($assess)) {
            $result=['code'=>'error','message'=>'暂无有效的评估数据','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);

        }

        /* ++++++++++ 房产评估 ++++++++++ */
        $estate = Estate::sharedLock()
            ->where([
                ['item_id',$this->item_id],
                ['household_id',$this->household_id],
                ['assess_id',$assess->id],
                ['code','136']
            ])
            ->select(['id', 'item_id', 'household_id', 'assess_id', 'code'])
            ->first();

        $estatebuildings = Estatebuilding::with(['householdbuilding' => function ($query) {
            $query->select(['id', 'code']);
        }])
            ->where([
                ['item_id', $this->item_id],
                ['household_id', $this->household_id],
                ['assess_id', $assess->id],
                ['estate_id', $estate->id],
            ])
            ->sharedLock()
            ->get();
        if (blank($estate) || blank($estatebuildings)) {
            $result=['code'=>'error','message'=>'暂无有效的房产评估数据','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);
        }

        if ($household_detail->getOriginal('has_assets')) {
            $assets = Assets::sharedLock()
                ->where([
                    ['item_id', $this->item_id],
                    ['household_id', $this->household_id],
                    ['assess_id', $assess->id],
                ])
                ->select(['id', 'item_id', 'household_id', 'assess_id', 'code'])
                ->first();
            if (blank($assets)) {
                $result=['code'=>'error','message'=>'暂无有效的资产评估数据','sdata'=>null,'edata'=>null,'url'=>null];
                return view('household.error')->with($result);
            }
        }

        /* ++++++++++ 正式方案 ++++++++++ */
        $program=Itemprogram::sharedLock()
            ->where([
                ['item_id',$this->item_id],
                ['code','22'],
            ])
            ->first();

        if(blank($program)){
            $result=['code'=>'error','message'=>'暂无通过审查的正式征收方案数据','sdata'=>null,'edata'=>null,'url'=>null];
            return view('household.error')->with($result);
        }

        /*房屋合法性*/
        $register_total=0;  // 合法房屋及附属物 评估总额
        $legal_total=0; // 合法临建 评估总额
        $destroy_total=0; // 违建自行拆除补助 评估总额
        $legal_area=0; // 合法面积
        foreach ($estatebuildings as $building) {
            if(in_array($building->code,['91','93'])){
                $result=['code'=>'error','message'=>'存在合法性争议的房屋','sdata'=>null,'edata'=>null,'url'=>null];
                return view('household.error')->with($result);
            }
            switch ($building->code){
                case '92':
                    $price=$building->price;
                    $amount=$building->amount;
                    $legal_total +=$amount;
                    $legal_area += $building->real_outer;
                    break;
                case '94':
                    $price=$building->householdbuilding->buildingdeal->price;
                    $amount=$building->householdbuilding->buildingdeal->amount;
                    $destroy_total +=$amount;
                    break;
                case '95':
                    $price=$building->price;
                    $amount=$building->amount;
                    $legal_total +=$amount;
                    $legal_area += $building->real_outer;
                    break;
                default:
                    $price=$building->price;
                    $amount=$building->amount;
                    $register_total +=$amount;
                    $legal_area += $building->real_outer;
            }

        }

        $model=Pay::with(['itemland' => function ($query) {
                $query->select(['id', 'address']);
                }, 'itembuilding'=>function($query){
                    $query->select(['id', 'building']);
                }])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->first();

        if (blank($model)){
            DB::beginTransaction();
            try{
                /* ++++++++++ 批量赋值 ++++++++++ */
                $pay = new Pay();
                $pay->item_id = $this->item_id;
                $pay->household_id = $this->household_id;
                $pay->land_id = $household->land_id;
                $pay->building_id = $household->building_id;
                $pay->repay_way = $itemrisk->getOriginal('repay_way');
                $pay->transit_way = $itemrisk->getOriginal('transit_way');
                $pay->move_way = $itemrisk->getOriginal('move_way');
                $pay->total = 0;
                $pay->save();
                if (blank($pay)) {
                    throw new \Exception('保存失败', 404404);
                }

                $subject_data = [];
                /* ++++++++++ 合法房屋及附属物、合法临建、违建自行拆除补助 ++++++++++ */
                $building_data = [];
                foreach ($estatebuildings as $building) {
                    $building_data[]=[
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'assess_id'=>$assess->id,
                        'estate_id'=>$estate->id,
                        'household_building_id'=>$building->household_building_id,
                        'estate_building_id'=>$building->id,
                        'pay_id'=>$pay->id,
                        'code'=>$building->code,
                        'real_outer'=>$building->real_outer,
                        'real_use'=>$building->real_use,
                        'struct_id'=>$building->struct_id,
                        'layout_id'=>$building->layout_id,
                        'direct'=>$building->direct,
                        'floor'=>$building->floor,
                        'price'=>$price,
                        'amount'=>$amount,
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                $field=['item_id','household_id','land_id','building_id','assess_id','estate_id','household_building_id','estate_building_id','pay_id','code','real_outer','real_use','struct_id','layout_id','direct','floor','price','amount','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('pay_building',$field,$building_data,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach($sqls as $sql){
                    DB::statement($sql);
                }

                /* ++++++++++ 合法房屋及附属物 ++++++++++ */
                if ($register_total) {
                    $subject_data[] = [
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'pay_id'=>$pay->id,
                        'pact_id'=>0,
                        'subject_id'=>1,
                        'total_id'=>0,
                        'calculate'=>null,
                        'amount'=>$register_total,
                        'code'=>'110',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 合法临建 ++++++++++ */
                if($legal_total){
                    $subject_data[]=[
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'pay_id'=>$pay->id,
                        'pact_id'=>0,
                        'subject_id'=>2,
                        'total_id'=>0,
                        'calculate'=>null,
                        'amount'=>$legal_total,
                        'code'=>'110',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }

                /* ++++++++++ 违建自行拆除补助 ++++++++++ */
                if($destroy_total){
                    $subject_data[]=[
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'pay_id'=>$pay->id,
                        'pact_id'=>0,
                        'subject_id'=>3,
                        'total_id'=>0,
                        'calculate'=>null,
                        'amount'=>$destroy_total,
                        'code'=>'110',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }

                /* ++++++++++ 固定资产 ++++++++++ */
                if($household_detail->getOriginal('has_assets')){
                    $subject_data[]=[
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'pay_id'=>$pay->id,
                        'pact_id'=>0,
                        'subject_id'=>6,
                        'total_id'=>0,
                        'calculate'=>null,
                        'amount'=>$assess->assets,
                        'code'=>'110',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 公共附属物 ++++++++++ */
                $public_total=0;
                $pay_public=Paypublic::sharedLock()
                    ->select(DB::raw('count(*) as `public_count`,sum(`avg`) as `public_total`'))
                    ->where([
                        ['item_id',$this->item_id],
                        ['land_id',$household->land_id],
                    ])
                    ->first();
                if(filled($pay_public) && $pay_public->public_count){
                    $public_total=$pay_public->public_total;
                }else{
                    $public_details=Publicdetail::with(['itempublic'=>function($query){
                        $query->select(['id','item_id','land_id','building_id','name','num_unit','number']);
                    }])
                        ->sharedLock()
                        ->where([
                            ['item_id',$this->item_id],
                            ['land_id',$household->land_id],
                        ])
                        ->get();
                    if(filled($public_details)){
                        /* ++++++++++ 地块户数 ++++++++++ */
                        $land_num=Household::sharedLock()
                            ->where([
                                ['item_id',$this->item_id],
                                ['land_id',$household->land_id],
                            ])
                            ->count();
                        /* ++++++++++ 楼栋户数 ++++++++++ */
                        $building_num=Household::sharedLock()
                            ->where([
                                ['item_id',$this->item_id],
                                ['land_id',$household->land_id],
                                ['building_id',$household->building_id],
                            ])
                            ->count();
                        $public_data=[];
                        foreach($public_details as $public){
                            $household_num=$public->building_id?$building_num:$land_num;
                            $avg=$public->amount/$household_num;
                            $public_total += $avg;

                            $public_data[]=[
                                'item_id'=>$this->item_id,
                                'land_id'=>$public->land_id,
                                'building_id'=>$public->building_id,
                                'item_public_id'=>$public->item_public_id,
                                'com_public_id'=>$public->com_public_id,
                                'com_pub_detail_id'=>$public->id,
                                'name'=>$public->itempublic->name,
                                'num_unit'=>$public->itempublic->num_unit,
                                'number'=>$public->itempublic->number,
                                'price'=>$public->price,
                                'amount'=>$public->amount,
                                'household'=>$household_num,
                                'avg'=>$avg,
                                'created_at'=>date('Y-m-d H:i:s'),
                                'updated_at'=>date('Y-m-d H:i:s'),
                            ];
                        }
                        $field=['item_id','land_id','building_id','item_public_id','com_public_id','com_pub_detail_id','name','num_unit','number','price','amount','household','avg','created_at','updated_at'];
                        $sqls=batch_update_or_insert_sql('pay_public',$field,$public_data,'updated_at');
                        if(!$sqls){
                            throw new \Exception('数据错误',404404);
                        }
                        foreach($sqls as $sql){
                            DB::statement($sql);
                        }
                    }
                }
                if($public_total){
                    $portion=100;
                    $total=$public_total;
                    // 公房
                    if($household->getOriginal('type')){
                        $portion=$program->portion_renter;
                        $total *= $program->portion_renter/100;

                        $unit_total=$public_total*$program->portion_holder/100;
                        $unit_data[]=[
                            'item_id'=>$this->item_id,
                            'household_id'=>$this->household_id,
                            'land_id'=>$household->land_id,
                            'unit_id'=>$household->itemland->admin_unit_id,
                            'pay_id'=>$pay->id,
                            'subject_id'=>4,
                            'pact_id'=>0,
                            'total_id'=>0,
                            'calculate'=>number_format($public_total,2).' × '.$program->portion_holder.'% = '.number_format($unit_total,2),
                            'amount'=>$public_total,
                            'portion'=>$program->portion_holder,
                            'total'=>$unit_total,
                            'code'=>'110',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                    $subject_data[]=[
                        'item_id'=>$this->item_id,
                        'household_id'=>$this->household_id,
                        'land_id'=>$household->land_id,
                        'building_id'=>$household->building_id,
                        'pay_id'=>$pay->id,
                        'pact_id'=>0,
                        'subject_id'=>4,
                        'total_id'=>0,
                        'calculate'=>null,
                        'amount'=>$public_total,
                        'portion'=>$portion,
                        'total'=>$total,
                        'code'=>'110',
                        'created_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ];
                }
                /* ++++++++++ 公房单位补偿 ++++++++++ */
                if(filled($unit_data)){
                    Payunit::insert($unit_data);
                }

                /* ++++++++++ 其他补偿事项 ++++++++++ */
                $household_objects=Householdobject::with(['itemobject'=>function($query){
                    $query->where('item_id',$this->item_id);
                },'object'])
                    ->sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['household_id',$this->household_id],
                    ])
                    ->get();
                $object_total=0;
                if(filled($household_objects)){
                    $object_data=[];
                    foreach($household_objects as $object){
                        $amount=$object->number*$object->itemobject->price;
                        $object_total +=$amount;

                        $object_data[]=[
                            'item_id'=>$this->item_id,
                            'household_id'=>$this->household_id,
                            'land_id'=>$household->land_id,
                            'building_id'=>$household->building_id,
                            'household_obj_id'=>$object->id,
                            'item_object_id'=>$object->itemobject->id,
                            'object_id'=>$object->object_id,
                            'pay_id'=>$pay->id,
                            'name'=>$object->object->name,
                            'num_unit'=>$object->object->num_unit,
                            'number'=>$object->number,
                            'price'=>$object->itemobject->price,
                            'amount'=>$amount,
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                    $field=['item_id','household_id','land_id','building_id','household_obj_id','item_object_id','object_id','pay_id','name','num_unit','number','price','amount','created_at','updated_at'];

                    $sqls=batch_update_or_insert_sql('pay_object',$field,$object_data,'updated_at');
                    if(!$sqls){
                        throw new \Exception('数据错误',404404);
                    }
                    foreach($sqls as $sql){
                        DB::statement($sql);
                    }

                    if($object_total){
                        $subject_data[]=[
                            'item_id'=>$this->item_id,
                            'household_id'=>$this->household_id,
                            'land_id'=>$household->land_id,
                            'building_id'=>$household->building_id,
                            'pay_id'=>$pay->id,
                            'pact_id'=>0,
                            'subject_id'=>5,
                            'total_id'=>0,
                            'calculate'=>null,
                            'amount'=>$object_total,
                            'code'=>'110',
                            'created_at'=>date('Y-m-d H:i:s'),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ];
                    }
                }

                /* ++++++++++ 补偿科目 ++++++++++ */
                $field=['item_id','household_id','land_id','building_id','pay_id','pact_id','subject_id','total_id','calculate','amount','code','created_at','updated_at'];
                $sqls=batch_update_or_insert_sql('pay_subject',$field,$subject_data,'updated_at');
                if(!$sqls){
                    throw new \Exception('数据错误',404404);
                }
                foreach($sqls as $sql){
                    DB::statement($sql);
                }

                $pay->total=$register_total+$legal_total+$destroy_total+$public_total+$object_total+$assess->assets;
                $pay->code=68;
                $pay->save();
                $model=Pay::with(['itemland' => function ($query) {
                    $query->select(['id', 'address']);
                }, 'itembuilding'=>function($query){
                    $query->select(['id', 'building']);
                }])
                    ->where('item_id',$this->item_id)
                    ->where('household_id',$this->household_id)
                    ->first();
                DB::commit();

            }catch (\Exception $exception){
                DB::rollback();
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
                $sdata = null;
                $edata = null;
                $url = null;
                $view='household.error';
                $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
                return view($view)->with($result);
            }

        }

        if (filled($model)){

            DB::beginTransaction();
            try{
                /* ++++++++++ 签约奖励 ++++++++++ */
                $pay_reward=Paysubject::sharedLock()
                            ->where([['item_id',$this->item_id],['household_id',$this->household_id]])
                            ->whereIn('subject_id',[11,12])
                            ->first();

                $item_reward=Itemreward::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->where([
                        ['start_at','<=',date('Y-m-d')],
                        ['end_at','>=',date('Y-m-d')],
                    ])
                    ->first();

                /*初始化签约数据*/
                $reward_total=0;
                $reward_amount=0;
                filled($pay_reward)?  $old_total=$pay_reward->total:  $old_total=0;
                $household->getOriginal('type')==0 ? $reward_portion = 100 : $reward_portion = $program->portion_holder;
                $household_detail->def_use == 1? $reward_subject_id = 11: $reward_subject_id = 12;

                /*在签约奖励期内计算签约奖励*/
                if(filled($item_reward)) {
                    /*住宅*/
                    if ($household_detail->def_use == 1) {
                        $reward_amount = $item_reward->price * $legal_area;
                        if (!$model->repay_way) {
                            $reward_amount += $program->reward_house * $legal_area;
                        }
                    } /*非住宅*/
                    else {
                        $reward_amount = $register_total * $item_reward->portion / 100;
                        if (!$model->repay_way) {
                            $reward_amount += $register_total * $program->reward_other / 100;
                        }
                    }
                    $reward_total=$reward_amount*$reward_portion/100;
                }

                if(blank($pay_reward)){
                    $pay_reward=new Paysubject();
                }
                $pay_reward->item_id=$this->item_id;
                $pay_reward->household_id=$this->household_id;
                $pay_reward->land_id=$household->land_id;
                $pay_reward->building_id=$household->building_id;
                $pay_reward->pay_id=$model->id;
                $pay_reward->pact_id=0;
                $pay_reward->subject_id=$reward_subject_id;
                $pay_reward->total_id=0;
                $pay_reward->calculate=null;
                $pay_reward->amount=$reward_amount;
                $pay_reward->total=$reward_total;
                $pay_reward->portion=$reward_portion;
                $pay_reward->code='110';
                $pay_reward->save();

                 if (blank($pay_reward)){
                     throw new \Exception('保存失败',404404);
                 }

                 $model->total=$model->total+$reward_total-$old_total;
                 $model->save();

                /* ++++++++++ 被征收户 ++++++++++ */
                $household=Household::with(['itemland'=>function($query){
                    $query->with('adminunit')->select(['id','address']);
                },'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },'state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->sharedLock()
                    ->select(['id','item_id','land_id','building_id','unit','floor','number','type','code'])
                    ->find($model->household_id);
                $household_detail=Householddetail::with(['defbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },'realbuildinguse'=>function($query){
                    $query->select(['id','name']);
                }])
                    ->sharedLock()
                    ->select(['id','household_id','status','register','reg_outer','def_use','real_use','has_assets'])
                    ->where('household_id',$model->household_id)
                    ->first();
                /* ++++++++++ 补偿科目 ++++++++++ */
                $subjects=Paysubject::with(['subject','state'=>function($query){
                    $query->select(['code','name']);
                }])
                    ->sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['pay_id',$model->id],
                    ])
                    ->get();
                /* ++++++++++ 公房单位、承租人、产权人 ++++++++++ */
                $pay_unit=null;
                if($household->getOriginal('type')){
                    $pay_unit=Payunit::sharedLock()
                        ->where([
                            ['item_id',$this->item_id],
                            ['household_id',$household->id],
                            ['pay_id',$model->id],
                        ])
                        ->first();

                    $holder_type=2;
                }else{
                    $holder_type=1;
                }
                $holder=Householdmember::sharedLock()
                    ->select(['id','item_id','household_id','name','holder','portion'])
                    ->where([
                        ['item_id',$this->item_id],
                        ['household_id',$household->id],
                        ['holder',$holder_type],
                    ])
                    ->orderBy('portion','desc')
                    ->first();

                /* ++++++++++ 安置房 ++++++++++ */
                $resettles=null;
                if($model->getOriginal('repay_way')==1){
                    $house_ids=DB::table('pay_house_bak')->where([['household_id',$this->household_id],['house_type',1]])->pluck('house_id')->toArray();

                    /* ++++++++++ 产权调换房 ++++++++++ */
                    $houses=House::with(['housecommunity','layout','itemhouseprice'=>function($query){
                        $query->where([
                            ['start_at','<=',$this->item->created_at],
                            ['end_at','>=',$this->item->created_at],
                        ]);
                    }])
                        ->sharedLock()
                        ->whereIn('id',$house_ids)
                        ->where('code','151')
                        ->orderBy('house.area','desc')
                        ->get();

                    if(blank($houses)){
                        throw new \Exception('当前选择的房源已被占用',404404);
                    }

                    $houses=$houses->sortByDesc(function($house,$key){
                        return $house->itemhouseprice->price;
                    });
                    $house_rates=Itemhouserate::sharedLock()->where('item_id',$this->item_id)->orderBy('start_area','asc')->get();

                    $total=$model->total;
                    /* ++++++++++ 可调换安置房的补偿额 ++++++++++ */
                    $resettle_total=Paysubject::sharedLock()
                        ->where([
                            ['item_id',$this->item_id],
                            ['household_id',$this->household_id],
                            ['pay_id',$model->id],
                        ])
                        ->whereIn('subject_id',[1,2,4,11,12])
                        ->sum('total');

                    $end_total=$total;
                    $last_total=$resettle_total; // 产权调换后结余补偿款
                    $plus_area=0; // 上浮累计面积
                    $resettles=[];
                    $resettle_ids=[];
                    foreach($houses as $house){
                        $house_amount=$house->area * $house->itemhouseprice->price; // 房屋安置优惠价值
                        $plus_toal=0;
                        $plus_data=[];
                        $last_total -= $house_amount; // 结余补偿款

                        // 可完全优惠
                        if($last_total>=0){
                            $plus_data=[
                                'area'=>0,
                                'market'=>$house->itemhouseprice->market,
                                'price'=>$house->itemhouseprice->price,
                                'agio'=>$house->itemhouseprice->market - $house->itemhouseprice->price,
                                'amount'=>$house_amount,
                            ];
                        }
                        // 不能完全优惠
                        else{
                            // 原补偿款结余为正
                            if(($last_total+$house_amount)>=0){
                                $def_area=($last_total+$house_amount)/$house->itemhouseprice->price; // 补偿款可完全优惠面积
                                $last_area=$house->area-$def_area; // 房屋面积与补偿款可完全优惠面积之差：上浮面积
                            }else{
                                // 上浮累计面积 超过限制
                                if($plus_area>=30){
                                    break;
                                }
                                $last_area=$house->area;
                            }
                            // 优惠上浮
                            foreach($house_rates as $rate){
                                // 在上浮优惠区间
                                if($rate->end_area !=0 && $rate->rate !=0){
                                    // 上浮累计面积不在当前区间
                                    if($plus_area>$rate->end_area){
                                        continue;
                                    }
                                    // 上浮累计面积加上浮面积 在当前区间
                                    if(($plus_area+$last_area) <= $rate->end_area){
                                        $plus_area += $last_area;
                                        $amount=$last_area * $house->itemhouseprice->price * $rate->rate/100;
                                        $plus_toal += $amount;

                                        $plus_data[]=[
                                            'start'=>$rate->start_area,
                                            'end'=>$rate->end_area,
                                            'area'=>$last_area,
                                            'market'=>$house->itemhouseprice->market,
                                            'price'=>$house->itemhouseprice->price,
                                            'rate'=>$rate->rate,
                                            'agio'=>$house->itemhouseprice->market - $house->itemhouseprice->price,
                                            'amount'=>$amount,
                                        ];
                                        break;
                                    }
                                    // 上浮累计面积加上浮面积 超出当前区间
                                    else{
                                        $up_area=$rate->end_area - $plus_area;
                                        $amount=$up_area * $house->itemhouseprice->price * $rate->rate/100;
                                        $plus_area += $up_area;
                                        $last_area -= $up_area;
                                        $plus_toal += $amount;

                                        $plus_data[]=[
                                            'start'=>$rate->start_area,
                                            'end'=>$rate->end_area,
                                            'area'=>$up_area,
                                            'market'=>$house->itemhouseprice->market,
                                            'price'=>$house->itemhouseprice->price,
                                            'rate'=>$rate->rate,
                                            'agio'=>$house->itemhouseprice->market - $house->itemhouseprice->price,
                                            'amount'=>$amount,
                                        ];
                                    }
                                }
                                // 超过上浮优惠区间
                                else{
                                    $plus_area += $last_area;
                                    $amount = ($house->itemhouseprice->market - $house->itemhouseprice->price) * $last_area ;
                                    $plus_toal += $amount;

                                    $plus_data[]=[
                                        'start'=>$rate->start_area,
                                        'end'=>$rate->end_area,
                                        'area'=>$last_area,
                                        'market'=>$house->itemhouseprice->market,
                                        'price'=>$house->itemhouseprice->price,
                                        'rate'=>$rate->rate,
                                        'agio'=>$house->itemhouseprice->market - $house->itemhouseprice->price,
                                        'amount'=>$amount,
                                    ];
                                    break;
                                }
                            }
                        }
                        $house->market=$house->itemhouseprice->market;
                        $house->price=$house->itemhouseprice->price;
                        $house->amount=$house_amount;
                        $house->amount_plus=$plus_toal;
                        $house->total=$house_amount + $plus_toal;
                        $house->housepluses=$plus_data;
                        $resettles[]=$house;
                        $resettle_ids[]=$house->id;
                        $end_total -= $house->total;

                        // 上浮累计面积 超过限制
                        if($plus_area>=30){
                            break;
                        }
                    }
                }
                /* ++++++++++ 临时周转房 ++++++++++ */
                $paytransits=null;
                if($model->getOriginal('transit_way')==1){
                    $paytransits=Payhousebak::with(['house'=>function($query){
                        $query->with([
                            'housecommunity'=> function ($query) {
                                $query->select(['id','name']);
                            },
                            'layout'=> function ($query) {
                                $query->select(['id','name']);
                            },
                            'housecompany'=> function ($query) {
                                $query->select(['id','name']);
                            }]);
                    }])
                        ->where([
                            ['household_id',$this->household_id],
                            ['item_id',$this->item_id],
                            ['house_type',2]
                        ])
                        ->sharedLock()
                        ->get();
                }
                /* ++++++++++ 协议 ++++++++++ */
                $pacts=Pact::with(['pactcate','state'])
                    ->sharedLock()
                    ->where([
                        ['item_id',$household->item_id],
                        ['household_id',$household->id],
                        ['pay_id',$model->id],
                    ])
                    ->get();
                $code='success';
                $msg='请求成功';
                $sdata=[
                    'item'=>$this->item,
                    'pay'=>$model,
                    'household'=>$household,
                    'household_detail'=>$household_detail,
                    'subjects'=>$subjects,
                    'pay_unit'=>$pay_unit,
                    'holder'=>$holder,
                    'payhouses'=>$resettles,
                    'paytransits'=>$paytransits,
                    'pacts'=>$pacts,
                ];
                $edata=new Pay();
                $url=null;
                $view='household.pay.info';
                DB::commit();
            }catch (\Exception $exception){

                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;
                $view='household.error';
                DB::rollback();
            }

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->is('api/*') ||$request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
    }

    /* ========== 修改兑付方式 ========== */
    public function edit(Request $request){
        $pay_id=$request->input('id');
        if(!$pay_id){
            $result=['code'=>'error','message'=>'请选择兑付数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('household.error')->with($result);
            }
        }
        if($request->isMethod('get')){
            DB::beginTransaction();
            try{
                /* ++++++++++ 兑付汇总 ++++++++++ */
                $pay=Pay::sharedLock()
                    ->where('id',$pay_id)
                    ->first();
                if(blank($pay)){
                    throw new \Exception('兑付数据不存在',404404);
                }
                /* ++++++++++ 被征收户 ++++++++++ */
                $household=Household::sharedLock()
                    ->select(['id','item_id','land_id','building_id','unit','floor','number','type','code'])
                    ->find($pay->household_id);
                if(!in_array($household->code,['67','69','75'])){
                    throw new \Exception('被征收户【'.$household->state->name.'】，不能修改',404404);
                }
                $code='success';
                $msg='请求成功';
                $sdata=$pay;
                $edata=new Pay();
                $url=null;

                $view='household.pay.edit';
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='household.error';
            }
            DB::commit();
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->is('api/*') ||$request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 保存 ********** */
        else {
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'repay_way' => 'required|boolean',
                'transit_way' => 'required|boolean',
                'move_way' => 'required|boolean',
            ];
            $messages = [
                'required' => ':attribute 为必须项',
                'boolean' => '请选择 :attribute 的正确选项',
            ];
            $model = new Pay();
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result = ['code' => 'error', 'message' => $validator->errors()->first(), 'sdata' => null, 'edata' => null, 'url' => null];
                return response()->json($result);
            }
            DB::beginTransaction();
            try{
                /* ++++++++++ 兑付汇总 ++++++++++ */
                $pay=Pay::lockForUpdate()
                    ->find($pay_id);

                if(blank($pay)){
                    throw new \Exception('兑付数据不存在',404404);
                }
                if($request->input('repay_way') ==0 && $request->input('transit_way')!=0){
                    throw new \Exception('选择货币作为补偿方式的只能选择货币作为过渡方式',404404);
                }
                /* ++++++++++ 被征收户 ++++++++++ */
                $household=Household::sharedLock()
                    ->select(['id','item_id','land_id','building_id','unit','floor','number','type','code'])
                    ->find($pay->household_id);
                if(!in_array($household->code,['67','68','75'])){
                    throw new \Exception('被征收户【'.$household->state->name.'】，不能修改',404404);
                }

                $household = Household::with('state')
                    ->sharedLock()
                    ->select(['id', 'item_id', 'land_id', 'building_id', 'unit', 'floor', 'number', 'type', 'code'])
                    ->where([
                        ['item_id', $this->item_id],
                        ['id', $this->household_id]
                    ])
                    ->first();
                if (blank($household)) {
                    throw new \Exception('被征收户不存在', 404404);
                }

                $household_detail = Householddetail::sharedLock()
                    ->where([
                        ['item_id', $this->item_id],
                        ['household_id', $this->household_id],
                    ])
                    ->select(['item_id','household_id','register','has_assets','area_dispute','repay_way','def_use'])
                    ->first();

                if(!in_array($household_detail->getOriginal('area_dispute'),[0,3])){
                    throw new \Exception('被征收户存在面积争议',404404);
                }
                /* ++++++++++ 评估汇总 ++++++++++ */
                $assess = Assess::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['household_id',$this->household_id],
                        ['code','136']
                    ])
                    ->first();

                if (blank($assess)) {
                    throw new \Exception('暂无有效的评估数据', 404404);
                }

                /* ++++++++++ 房产评估 ++++++++++ */
                $estate = Estate::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['household_id',$this->household_id],
                        ['assess_id',$assess->id],
                        ['code','136']
                    ])
                    ->select(['id', 'item_id', 'household_id', 'assess_id', 'code'])
                    ->first();

                $estatebuildings = Estatebuilding::with(['householdbuilding' => function ($query) {
                    $query->select(['id', 'code']);
                }])
                    ->where([
                        ['item_id', $this->item_id],
                        ['household_id', $this->household_id],
                        ['assess_id', $assess->id],
                        ['estate_id', $estate->id],
                    ])
                    ->sharedLock()
                    ->get();
                if (blank($estate) || blank($estatebuildings)) {
                    throw new \Exception('暂无有效的房产评估数据', 404404);
                }

                if ($household_detail->getOriginal('has_assets')) {
                    $assets = Assets::sharedLock()
                        ->where([
                            ['item_id', $this->item_id],
                            ['household_id', $this->household_id],
                            ['assess_id', $assess->id],
                        ])
                        ->select(['id', 'item_id', 'household_id', 'assess_id', 'code'])
                        ->first();
                    if (blank($assets)) {
                        throw new \Exception('暂无有效的资产评估数据', 404404);
                    }
                }

                /* ++++++++++ 正式方案 ++++++++++ */
                $program=Itemprogram::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['code','22'],
                    ])
                    ->select(['id','item_id','code','item_end','portion_holder'])
                    ->first();
                if(blank($program)){
                    throw new \Exception('暂无通过审查的正式征收方案数据',404404);
                }
                $pay->fill($request->input());
                $pay->save();
                if(blank($pay)){
                    throw new \Exception('保存失败',404404);
                }
                $code='success';
                $msg='保存成功';
                $sdata=[
                    'item'=>$this->item,
                    'pay'=>$pay,
                ];
                $edata=new Pay();
                $url=route('h_pay_info');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'保存失败';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }

            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 确认签约 ========== */
    public function confirm(Request $request){
        $pay_id=$request->input('pay_id');
        if (!$pay_id) {
            $result = ['code' => 'error', 'message' => '暂无兑付数据', 'sdata' => null, 'edata' => null, 'url' => null];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.error')->with($result);
            }
        }
        DB::beginTransaction();
        try {
            /* ++++++++++ 兑付 ++++++++++ */
            $pay = Pay::sharedLock()
                ->find($pay_id);
            if (blank($pay)) {
                throw new \Exception('错误操作', 404404);
            }

            /*修改被征户状态（签约中）*/
            $household=Household::sharedLock()
                ->find($this->household_id);
            $household->code=70;
            $household->save();
            if(blank($household)){
                throw new \Exception('保存失败', 404404);
            }

            /*消息推送至征收管理端的相关人员*/
            $param = ['item' => $this->item_id, 'id' => $pay_id];
            $this->send_work_notice(41, 'g_pact_add', $param);

            $code = 'success';
            $msg = '提交成功';
            $sdata = null;
            $edata = null;
            $url = null;
            DB::commit();

        }catch (\Exception $exception) {
            $code = 'error';
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '保存失败';
            $sdata = null;
            $edata = null;
            $url = null;

            DB::rollBack();
        }
        $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view('household.error')->with($result);
        }
    }

}