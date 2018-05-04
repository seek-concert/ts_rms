<?php
/*
|--------------------------------------------------------------------------
| 项目-评估机构投票
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\Company;
use App\Http\Model\Companyvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompanyvoteController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        DB::beginTransaction();

        $companys=Company::withCount(['companyvotes'=>function($query){
            $query->where('item_id',$this->item_id);
        }])
            ->where([
                ['type',0],
//                ['code',41],
            ])
            ->orderBy('companyvotes_count','desc')
            ->sharedLock()
            ->get();

        DB::commit();

        /* ********** 结果 ********** */
        $result=[
            'code'=>'success',
            'message'=>'请求成功',
            'sdata'=>[
                'item'=>$this->item,
                'companys'=>$companys,
            ],
            'edata'=>null,
            'url'=>null];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.companyvote.index')->with($result);
        }
    }

    /* ========== 投票详情 ========== */
    public function info(Request $request){
        $company_id=$request->input('company_id');
        if(!$company_id){
            $result=['code'=>'error', 'message'=>'请选择评估机构', 'sdata'=>null, 'edata'=>null, 'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('gov.error')->with($result);
            }
        }

        DB::beginTransaction();
        try{
            $company=Company::sharedLock()->find($company_id);
            if(blank($company)){
                throw new \Exception('评估机构不存在',404404);
            }
            if($company->getOriginal('type') != 0 || $company->getOriginal('code') == 0){
                throw new \Exception('错误操作',404404);
            }
            $households=Companyvote::with(['household'=>function($query){
                $query->with(['itemland','itembuilding'])
                    ->select(['id','item_id','land_id','building_id','unit','floor','number','username']);
            }])
                ->sharedLock()
                ->get();
            if(blank($households)){
                throw new \Exception('该机构没有投票',404404);
            }

            $code='success';
            $msg='获取成功';
            $sdata=['item'=>$this->item,'company'=>$company,'households'=>$households];
            $edata=null;
            $url=null;

            $view='gov.companyvote.info';
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络错误';
            $sdata=null;
            $edata=null;
            $url=null;

            $view='gov.error';
        }

        DB::commit();

        $result=['code'=>$code, 'message'=>$msg, 'sdata'=>$sdata, 'edata'=>$edata, 'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }
}