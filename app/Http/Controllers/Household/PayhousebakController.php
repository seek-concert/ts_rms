<?php
/*
|--------------------------------------------------------------------------
| 兑付--安置房备选
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
use App\Http\Model\Item;
use App\Http\Model\Pay;
use App\Http\Model\Itemctrl;
use App\Http\Model\Household;
use App\Http\Model\Itemhouserate;
use App\Http\Model\Paysubject;
use App\Http\Model\Payunit;
use App\Http\Model\Payhouse;
use App\Http\Model\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Model\Payhousebak;

class PayhousebakController extends BaseController{
    public $item;
    public $item_id;

    /*选定的安置房缓存表*/
    public function index(Request $request){
        $household_id=session('household_user.user_id');
        $item_id=$this->item_id=session('household_user.item_id');
        $this->item=Item::find($item_id);
        DB::beginTransaction();
        try{
            $allhouse=Payhousebak::with(['house'=>function($query){
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
                ['household_id',$household_id],
                ['item_id',$item_id]
            ])
                ->sharedLock()
                ->get();
            if (blank($allhouse)){
                throw new \Exception('暂未选择房源', 404404);
            }

            /* ++++++++++ 兑付 ++++++++++ */
            $pay=Pay::sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['household_id',$household_id],
                ])
                ->first();
            if(blank($pay)){
                throw new \Exception('错误操作',404404);
            }

            $house_ids=DB::table('pay_house_bak')->where('household_id',$household_id)->pluck('house_id')->toArray();
            /* ++++++++++ 被征收户 ++++++++++ */
            $household=Household::sharedLock()
                ->select(['id','item_id','land_id','building_id','unit','floor','number','type','code'])
                ->find($pay->household_id);
            if(!in_array($household->code,['68','76'])){
                throw new \Exception('被征收户【'.$household->state->name.'】，不能选房',404404);
            }

            $count=Payhouse::sharedLock()
                ->where([
                    ['item_id',$pay->item_id],
                    ['household_id',$pay->household_id],
                ])
                ->count();
            if($count){
                throw new \Exception('已有选房数据，请进入修改页面',404404);
            }

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
                ->orderBy('area','desc')
                ->get();
            if(blank($houses)){
                throw new \Exception('当前选择的房源已被占用',404404);
            }
            $houses=$houses->sortByDesc(function($house,$key){
                return $house->itemhouseprice->price;
            });
            $house_rates=Itemhouserate::sharedLock()->where('item_id',$this->item_id)->orderBy('start_area','asc')->get();

            $total=$pay->total;

            /* ++++++++++ 可调换安置房的补偿额 ++++++++++ */
            $resettle_total=Paysubject::sharedLock()
                ->where([
                    ['item_id',$pay->item_id],
                    ['household_id',$pay->household_id],
                    ['pay_id',$pay->id],
                ])
                ->whereIn('subject_id',[1,2,4,11,12])
                ->sum('amount');

            if($household->getOriginal('type')==1){ // 公房
                $pay_unit=Payunit::sharedLock()
                    ->where([
                        ['item_id',$this->item_id],
                        ['household_id',$household->id],
                        ['pay_id',$pay->id],
                    ])
                    ->first();
                $total -= $pay_unit->amount;
                $resettle_total -= $pay_unit->amount;
            }
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
                        'area'=>$house->area,
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

                                $plus_data=[
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

                                $plus_data=[
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

                            $plus_data=[
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

                    // 上浮累计面积 超过限制
                    if($plus_area>=30){
                        break;
                    }
                }

                $house->amount=$house_amount;
                $house->amount_plus=$plus_toal;
                $house->total=$house_amount + $plus_toal;
                $house->housepluses=$plus_data;
                $resettles[]=$house;
                $resettle_ids[]=$house->id;
                $end_total -= $house->total;

            }

            $fails=array_diff($house_ids,$resettle_ids);

            $code='success';
            $msg='查询成功';
            $sdata=[
                'allhouse'=>$allhouse,
                'resettles'=>$resettles,
                'resettle_total'=>$resettle_total,
                'last_total'=>$end_total,
                'plus_area'=>$plus_area,
            ];
            $edata=$fails;
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
            return view('household.payhousebak.index')->with($result);
        }
    }

    /*添加安置房(缓存表)*/
    public function add(Request $request){
        $household_id=session('household_user.user_id');
        $item_id=session('household_user.item_id');
        DB::beginTransaction();
        try{
            /* ++++++++++ 兑付 ++++++++++ */
            $pay=Pay::sharedLock()
                ->where([
                    ['item_id',$item_id],
                    ['household_id',$household_id],
                ])
                ->first();
            if(blank($pay)){
                throw new \Exception('错误操作',404404);
            }
            if($pay->getOriginal('repay_way')==0){
                throw new \Exception('选择货币补偿的不能选择安置房',404404);
            }

            $house_id=$request->input('house_id');
            if(blank($house_id)){
                throw new \Exception('请选择房源',404404);
            }

            /* ++++++++++ 选房时间 ++++++++++ */
            $itemctrl=Itemctrl::sharedLock()
                ->where([
                    ['item_id',$item_id],
                    ['cate_id',3],
                    ['start_at','<=',date('Y-m-d H:i:s')],
                    ['end_at','>=',date('Y-m-d H:i:s')],
                ])
                ->first();
            if(blank($itemctrl)){
                throw new \Exception('还未到选房时间',404404);
            }

            /* ++++++++++ 被征收户 ++++++++++ */
            $household=Household::with(['itemland'=>function($query){
                $query->with('adminunit')->select(['id','address','admin_unit_id']);
            },'itembuilding'=>function($query){
                $query->select(['id','building']);
            },'state'=>function($query){
                $query->select(['code','name']);
            }])
                ->sharedLock()
                ->select(['id','item_id','land_id','building_id','unit','floor','number','type','code'])
                ->find($pay->household_id);
            if(!in_array($household->code,['68','76'])){
                throw new \Exception('被征收户【'.$household->state->name.'】，不能选房',404404);
            }

            /* ++++++++++ 产权调换房 ++++++++++ */
            $houses=House::with(['itemhouseprice'=>function($query){
                $query->where([
                    ['start_at','<=',$this->item->created_at],
                    ['end_at','>=',$this->item->created_at],
                ]);
            }])
                ->sharedLock()
                ->where('id',$request->input('house_id'))
                ->where('code','151')
                ->orderBy('area','desc')
                ->get();
            if(blank($houses)){
                throw new \Exception('当前选择的房源已被占用',404404);
            }

            $payhousebak=Payhousebak::where([
                ['household_id',$household_id],
                ['item_id',$item_id],
                ['house_id',$request->input('house_id')]
            ])
                ->sharedLock()
                ->first();
            if (filled($payhousebak)){
                throw new \Exception('已选择过该房源', 404404);
            }
            $payhousebak=new Payhousebak();
//            $payhousebak->fill($request->all());

            $payhousebak->item_id=$item_id;
            $payhousebak->house_id=$request->input('house_id');
            $payhousebak->household_id=$household_id;
            $payhousebak->land_id=session('household_user.land_id');
            $payhousebak->building_id=session('household_user.building_id');
            $payhousebak->save();
            if (blank($payhousebak)) {
                throw new \Exception('该房源不存在', 404404);
            }
            $code = 'success';
            $msg = '选房成功';
            $sdata = $payhousebak;
            $edata = null;
            $url = null;
            $view='household.payhousebak.index';
            DB::commit();
        }catch (\Exception $exception){
            $code = 'error';
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
            $sdata = null;
            $edata = null;
            $url = null;
            $view='household.error';
            DB::rollBack();
        }
        /* ********** 结果 ********** */
        $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /*房源详情*/
    public function info(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('household.error')->with($result);
            }
        }
        DB::beginTransaction();
        $house=House::sharedLock()
            ->find($id);
        /* ********** 查询 ********** */
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($house)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$house;
            $edata=null;
            $url=null;

            $view='household.payhousebak.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }
}