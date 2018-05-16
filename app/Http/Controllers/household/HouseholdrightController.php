<?php
namespace App\Http\Controllers\household;
use App\Http\Model\Householdbuilding;
use App\Http\Model\Householdassets;
use Illuminate\Http\Request;
use App\Http\Model\Householddetail;
use App\Http\Model\Householdright;
use App\Http\Model\Estate;
use Illuminate\Support\Facades\DB;
use App\Http\Model\Itempublic;
use App\Http\Model\Householdbuildingarea;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Assess;
use App\Http\Model\Household;
use App\Http\Model\Householdmember;
/*
|--------------------------------------------------------------------------
| 被征收户-确权确户
|--------------------------------------------------------------------------
*/
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/27
 * Time: 15:56
 */
class HouseholdrightController extends BaseController{
    public function index(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{

            /*产权解决详情*/
            $householdright=Householdright::sharedLock()
                ->where('household_id',$this->household_id)
                ->where('item_id',$this->item_id)
                ->sharedLock()
                ->first();

            $member = Householdmember::sharedLock()
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->get();

            /*合法性认定*/
            $householdbuildings=Householdbuilding::sharedLock()
                ->with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'buildingstruct'=>function($query){
                    $query->select(['id','name']);
                },
                'defbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'realbuildinguse'=>function($query){
                    $query->select(['id','name']);
                },
                'landlayout'=>function($query){
                    $query->select(['id','name','area','picture']);
                },
                'state'=>function($query){
                    $query->select(['id','code','name']);
                }])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->orderBy('code','desc')
                ->sharedLock()
                ->get();
            $building_check=0;
            foreach ($householdbuildings as $k=>$v){
                if(!in_array($v->code,[90,92,94,95])){
                    $building_check=1;
                    break;
                }
            }

            /*面积争议*/
            $householdbuildingarea=Householdbuildingarea::sharedLock()
                ->where('household_id',$this->household_id)
                ->where('item_id',$this->item_id)
                ->first();

            /*被征户信息*/
            $householddetail=Householddetail::sharedLock()
                ->with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'household'=>function($query){
                    $query->select(['code','id']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'layout'=>function($query){
                    $query->select(['id','name']);
                }
                ])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->first();


            /*资产详情*/
            $householdassetss=Householdassets::sharedLock()
                ->with([
                    'itemland'=>function($query){
                        $query->select(['id','address']);
                    },
                    'itembuilding'=>function($query){
                        $query->select(['id','building']);
                    }])
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->sharedLock()
                ->get();

            /*房产详情*/
            $householdestate=Estate::sharedLock()
                ->where('item_id',$this->item_id)
                ->where('household_id',$this->household_id)
                ->sharedLock()
                ->first();
            $file_table_id=Filetable::where('name','item_household_detail')->sharedLock()->value('id');
            $detail_filecates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');

            /*公共附属物*/
            $itempublics=Itempublic::sharedLock()
                ->with(['itembuilding'])
                ->where('item_id',$this->item_id)
                ->where('land_id',session('household_user.land_id'))
                ->sharedLock()
                ->get();

            if(blank($householddetail)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=[
                'householddetail'=>$householddetail,
                'householdright'=>$householdright,
                'householdbuildings'=>$householdbuildings,
                'householdbuildingarea'=>$householdbuildingarea,
                'householdassets'=>$householdassetss,
                'householdestate'=>$householdestate,
                'detail_filecates'=>$detail_filecates,
                'itempublics'=>$itempublics,
                'building_check'=>$building_check,
                'member'=>$member,
                'membermodel' => new Householdmember()
            ];
            $edata=null;
            $url=null;
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
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('household.householdright.index')->with($result);
        }
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $householdright=Householdright::sharedLock()
            ->where('household_id',$this->household_id)
            ->where('item_id',$this->item_id)
            ->sharedLock()
            ->first();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($householdright)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$householdright;
            $edata=null;
            $url=null;

            $view='gov.householdright.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    public function confirm(Request $request){
        DB::beginTransaction();
        try{
            $household=Household::sharedLock()
                ->with('householddetail')
                ->find($this->household_id);
            if($household->code!=62){
                throw new \Exception('被征户【'.$household->state->name.'】，不能进行该操作！', 404404);
            }

            if(!in_array($household->householddetail->getOriginal('area_dispute'),[0,3,5])){
                throw new \Exception('面积争议待处理！', 404404);
            }

            if(!in_array($household->householddetail->getOriginal('dispute'),[0,2])){
                throw new \Exception('产权争议待处理！', 404404);
            }

            $codes = DB::table('item_household_building')->where('household_id',$this->household_id)->pluck('code')->toArray();
            foreach ($codes as $k=>$v){
                if(!in_array($v,[90,92,94,95])){
                    throw new \Exception('房屋建筑合法性认定中！', 404404);
                    break;
                }
            }

            $household->code=63;
            $household->save();
            if(blank($household)){
                throw new \Exception('保存失败！', 404404);
            }

            $code = 'success';
            $msg = '提交成功';
            $sdata = null;
            $edata = null;
            $url = route('h_householdright');
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