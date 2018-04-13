<?php
/*
|--------------------------------------------------------------------------
| 被征户-产权调换房
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;

use App\Http\Model\House;
use App\Http\Model\Household;
use App\Http\Model\Householdmember;
use App\Http\Model\Item;
use App\Http\Model\Menu;
use App\Http\Model\Itemctrl;
use App\Http\Model\Itemhouse;
use App\Http\Model\Itemhouserate;
use App\Http\Model\Itemprogram;
use App\Http\Model\Pay;
use App\Http\Model\Payhouse;
use App\Http\Model\Payhouseplus;
use App\Http\Model\Payreserve;
use App\Http\Model\Payhousebak;
use App\Http\Model\Paysubject;
use App\Http\Model\Payunit;
use App\Http\Model\Process;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayhouseController extends BaseController
{
    public $item;

    /* ========== 开始选房 ========== */
    public function add(Request $request)
    {
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
            $this->item=Item::find($this->item_id);
            if(blank($this->item)){
                throw new \Exception('项目不存在',404404);
            }

            /* ++++++++++ 兑付 ++++++++++ */
            $pay = Pay::sharedLock()
                ->where([
                    ['item_id', $this->item_id],
                    ['id', $pay_id],
                ])
                ->first();
            if (blank($pay)) {
                throw new \Exception('错误操作', 404404);
            }
            if ($pay->getOriginal('repay_way') == 0) {
                throw new \Exception('选择货币补偿的不能选择安置房', 404404);
            }

            $house_ids = Payhousebak::where([['household_id', $this->household_id], ['house_type', 1]])->pluck('house_id')->toArray();
            $transits = Payhousebak::where([['household_id', $this->household_id], ['house_type', 2]])->pluck('house_id')->toArray();
            if (blank($house_ids)) {
                throw new \Exception('暂未选择安置房', 404404);
            }

            /*删除旧的选房*/
            $pay_house=Payhouse::sharedLock()
                        ->where([
                            ['item_id',$this->item_id],
                            ['household_id',$this->household_id]
                        ])
                        ->get();
            if(filled($pay_house)){
                $re=Payhouse::where([
                    ['household_id',$this->household_id],
                    ['item_id',$this->item_id]
                ])
                    ->delete();
                if($re===false) throw new \Exception('操作失败，请稍后重试!',404404);
            }

            /*删除旧的选房上浮数据*/
            $pay_house_plus=Payhouseplus::sharedLock()
                        ->whereIn('house_id',$house_ids)
                        ->where([
                            ['item_id',$this->item_id],
                            ['household_id',$this->household_id]
                        ])
                        ->get();
            if(filled($pay_house_plus)){
                $re=Payhouseplus::where([
                    ['household_id',$this->household_id],
                    ['item_id',$this->item_id]
                ])
                    ->whereIn('house_id',$house_ids)
                    ->delete();
                if($re===false) throw new \Exception('操作失败，请稍后重试!',404404);
            }


           /* if ($house_ids == $transits) {
                throw new \Exception('暂未选择临时周转房', 404404);
            }*/

            /* ++++++++++ 临时周转房过渡 ++++++++++ */
            if ($pay->getOriginal('transit_way')  && blank($transits)) {
                throw new \Exception('暂未选择临时周转房', 404404);
            } /* ++++++++++ 货币过渡 ++++++++++ */
            else {
                if (filled($transits)) {
                    throw new \Exception('选择货币过渡则不能选择临时周转房', 404404);
                }
            }

            /* ++++++++++ 被征收户 ++++++++++ */
            $household = Household::sharedLock()
                ->select(['id', 'item_id', 'land_id', 'building_id', 'unit', 'floor', 'number', 'type', 'code'])
                ->find($pay->household_id);
            if (!in_array($household->code, ['68'])) {
                throw new \Exception('被征收户【' . $household->state->name . '】，不能请求签约', 404404);
            }

            $count = Payhouse::sharedLock()
                ->where([
                    ['item_id', $pay->item_id],
                    ['household_id', $this->household_id],
                ])
                ->count();
            if ($count) {
                throw new \Exception('已有选房数据，请进入修改页面', 404404);
            }

            /* ++++++++++ 产权调换房 ++++++++++ */
            $houses = House::with(['itemhouseprice' => function ($query) {
                $query->where([
                    ['start_at', '<=', $this->item->created_at],
                    ['end_at', '>=', $this->item->created_at],
                ]);
            }])
                ->sharedLock()
                ->whereIn('id', $house_ids)
                ->where('code', '151')
                ->orderBy('area', 'desc')
                ->get();
            if (blank($houses)) {
                throw new \Exception('当前选择的房源已被占用', 404404);
            }

            $houses = $houses->sortByDesc(function ($house, $key) {
                return $house->itemhouseprice->price;
            });

            $house_rates = Itemhouserate::sharedLock()->where('item_id', $this->item_id)->orderBy('start_area', 'asc')->get();

            /* ++++++++++ 可调换安置房的补偿额 ++++++++++ */
            $resettle_total = Paysubject::sharedLock()
                ->where([
                    ['item_id', $this->item_id],
                    ['household_id', $this->household_id],
                    ['pay_id', $pay->id],
                ])
                ->whereIn('subject_id', [1, 2, 4, 11, 12])
                ->sum('total');

            $last_total = $resettle_total; // 产权调换后结余补偿款
            $plus_area = 0; // 上浮累计面积
            $house_data = [];
            $plus_data = [];
            $resettles = [];
            foreach ($houses as $house) {
                $house_amount = $house->area * $house->itemhouseprice->price; // 房屋安置优惠价值
                $plus_toal = 0;

                $last_total -= $house_amount; // 结余补偿款
                // 可完全优惠
                if ($last_total >= 0) {

                } // 不能完全优惠
                else {
                    // 原补偿款结余为正
                    if (($last_total + $house_amount) >= 0) {
                        $def_area = round(($last_total + $house_amount) / $house->itemhouseprice->price, 2); // 补偿款可完全优惠面积
                        $last_area = $house->area - $def_area; // 房屋面积与补偿款可完全优惠面积之差：上浮面积
                    } else {
                        // 上浮累计面积 超过限制
                        if ($plus_area >= 30) {
                            break;
                        }
                        $last_area = $house->area;
                    }
                    // 优惠上浮
                    foreach ($house_rates as $rate) {
                        // 在上浮优惠区间
                        if ($rate->end_area != 0 && $rate->rate != 0) {
                            // 上浮累计面积不在当前区间
                            if ($plus_area > $rate->end_area) {
                                continue;
                            }
                            // 上浮累计面积加上浮面积 在当前区间
                            if (($plus_area + $last_area) <= $rate->end_area) {
                                $plus_area += $last_area;
                                $amount = $last_area * $house->itemhouseprice->price * $rate->rate / 100;
                                $plus_toal += $amount;

                                $plus_data[] = [
                                    'item_id' => $pay->item_id,
                                    'household_id' => $pay->household_id,
                                    'land_id' => $pay->land_id,
                                    'building_id' => $pay->building_id,
                                    'house_id' => $house->id,
                                    'start' => $rate->start_area,
                                    'end' => $rate->end_area,
                                    'area' => $last_area,
                                    'market' => $house->itemhouseprice->market,
                                    'price' => $house->itemhouseprice->price,
                                    'rate' => $rate->rate,
                                    'agio' => $house->itemhouseprice->market - $house->itemhouseprice->price,
                                    'amount' => $amount,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                break;
                            } // 上浮累计面积加上浮面积 超出当前区间
                            else {
                                $up_area = $rate->end_area - $plus_area;
                                $amount = $up_area * $house->itemhouseprice->price * $rate->rate / 100;
                                $plus_area += $up_area;
                                $last_area -= $up_area;
                                $plus_toal += $amount;

                                $plus_data[] = [
                                    'item_id' => $pay->item_id,
                                    'household_id' => $pay->household_id,
                                    'land_id' => $pay->land_id,
                                    'building_id' => $pay->building_id,
                                    'house_id' => $house->id,
                                    'start' => $rate->start_area,
                                    'end' => $rate->end_area,
                                    'area' => $up_area,
                                    'market' => $house->itemhouseprice->market,
                                    'price' => $house->itemhouseprice->price,
                                    'rate' => $rate->rate,
                                    'agio' => $house->itemhouseprice->market - $house->itemhouseprice->price,
                                    'amount' => $amount,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                            }
                        } // 超过上浮优惠区间
                        else {
                            $plus_area += $last_area;
                            $amount = ($house->itemhouseprice->market - $house->itemhouseprice->price) * $last_area;
                            $plus_toal += $amount;

                            $plus_data[] = [
                                'item_id' => $pay->item_id,
                                'household_id' => $pay->household_id,
                                'land_id' => $pay->land_id,
                                'building_id' => $pay->building_id,
                                'house_id' => $house->id,
                                'start' => $rate->start_area,
                                'end' => $rate->end_area,
                                'area' => $last_area,
                                'market' => $house->itemhouseprice->market,
                                'price' => $house->itemhouseprice->price,
                                'rate' => $rate->rate,
                                'agio' => $house->itemhouseprice->market - $house->itemhouseprice->price,
                                'amount' => $amount,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                            break;
                        }
                    }
                }
                $resettles[] = $house->id;
                $house_data[] = [
                    'item_id' => $pay->item_id,
                    'household_id' => $pay->household_id,
                    'land_id' => $pay->land_id,
                    'building_id' => $pay->building_id,
                    'house_id' => $house->id,
                    'area' => $house->area,
                    'market' => $house->itemhouseprice->market,
                    'price' => $house->itemhouseprice->price,
                    'amount' => $house_amount,
                    'amount_plus' => $plus_toal,
                    'total' => $house_amount + $plus_toal,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                // 上浮累计面积 超过限制
                if ($plus_area >= 30) {
                    break;
                }
            }

            $field = ['item_id', 'household_id', 'land_id', 'building_id', 'house_id', 'area', 'market', 'price', 'amount', 'amount_plus', 'total', 'created_at', 'updated_at'];
            $sqls = batch_update_or_insert_sql('pay_house', $field, $house_data, 'updated_at');
            if (!$sqls) {
                throw new \Exception('保存失败', 404404);
            }
            foreach ($sqls as $sql) {
                DB::statement($sql);
            }
            // 产权调换房 上浮
            if (filled($plus_data)) {
                $field = ['item_id', 'household_id', 'land_id', 'building_id', 'house_id', 'start', 'end', 'area', 'market', 'price', 'rate', 'agio', 'amount', 'created_at', 'updated_at'];
                $sqls = batch_update_or_insert_sql('pay_house_plus', $field, $plus_data, 'updated_at');
                if (!$sqls) {
                    throw new \Exception('保存失败', 404404);
                }
                foreach ($sqls as $sql) {
                    DB::statement($sql);
                }
            }

            /* ++++++++++ 临时周转房 ++++++++++ */
            if ($pay->getOriginal('transit_way')) {
                $transit_data = [];
                foreach ($transits as $house_id) {
                    $transit_data[] = [
                        'item_id' => $pay->item_id,
                        'household_id' => $pay->household_id,
                        'land_id' => $pay->land_id,
                        'building_id' => $pay->building_id,
                        'pay_id' => $pay->id,
                        'pact_id' => 0,
                        'house_id' => $house_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
                $field = ['item_id', 'household_id', 'land_id', 'building_id', 'pay_id', 'pact_id', 'house_id', 'created_at', 'updated_at'];
                $sqls = batch_update_or_insert_sql('pay_transit', $field, $transit_data, 'updated_at');
                if (!$sqls) {
                    throw new \Exception('保存失败', 404404);
                }
                foreach ($sqls as $sql) {
                    DB::statement($sql);
                }
            }

            $household->code=69;
            $household->save();
            if(blank($household)){
                throw new \Exception('保存失败', 404404);
            }

            /*锁定有效选房*/
            $house_lock=House::lockForUpdate()
                ->whereIn('id',$resettles)
                ->update(['code'=>155]);
            if(blank($house_lock)){
                throw new \Exception('保存失败', 404404);
            }

            /*消息推送至征收管理端的相关人员*/
            $param=['item'=>$this->item_id,'id'=>$pay_id];
            $this->send_work_notice(40,'g_pay_info',$param);

            $fails = array_diff($house_ids, $resettles);
            $code = 'success';
            $msg = '保存成功';
            $sdata = [
                'resettles' => $resettles,
            ];
            $edata = filled($fails) ? $fails : null;
            $url = null;

            DB::commit();
        } catch (\Exception $exception) {
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