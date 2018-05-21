<?php
/*
|--------------------------------------------------------------------------
| 被征户--入围机构
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Itemcompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
class ItemcompanyController extends BaseController{



    public function index(Request $request){

        $per_page=15;
        $page=$request->input('page',1);

        DB::beginTransaction();
        try{
            $total=Itemcompany::sharedLock()
                ->where('item_id',$this->item_id)
                ->count();
            $item_companys=Itemcompany::with(['company'=>function($query){
                $query->select();
            }])
                ->withCount(['companyvotes'=>function($query){
                $query->where('item_id',$this->item_id);
                }])
                ->where('item_id',$this->item_id)
                ->orderBy('companyvotes_count','desc')
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();

            $item_companys=new LengthAwarePaginator($item_companys,$total,$per_page,$page);
            $item_companys->withPath(route('h_itemcompany'));

            if(blank($item_companys)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$item_companys;
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
            return view('household.itemcompany.index')->with($result);
        }
    }
}