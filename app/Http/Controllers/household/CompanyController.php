<?php
/*
|--------------------------------------------------------------------------
| 被征户--评估公司
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class CompanyController extends BaseController{

    public function info(Request $request){
        $id=$request->input('id');
        if (!$id) {
            $result = ['code' => 'error', 'message' => '请先选择数据', 'sdata' => null, 'edata' => null, 'url' => null];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.error')->with($result);
            }
        }
        DB::beginTransaction();
        try{
            $company=Company::with(['companyuser'=>function($query){
                $query->select(['name','phone','id']);
            }])
                ->sharedLock()
                ->find($id);
            if(blank($company)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$company;
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
        $result=[
            'code'=>$code,
            'message'=>$msg,
            'sdata'=>$sdata,
            'edata'=>null,
            'url'=>null];
        if($request->is('api/*') || $request->ajax()){
            return response()->json($result);
        }else {
            return view('household.company.info')->with($result);
        }
    }
}