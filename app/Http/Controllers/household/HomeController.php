<?php
/*
|--------------------------------------------------------------------------
| 被征户--首页
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Menu;
use App\Http\Model\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
    public function index(Request $request){
        DB::beginTransaction();
        /* ++++++++++ 通知公告 ++++++++++ */
        $news=News::sharedLock()
            ->with(['newscate'=>function($query){
            $query->select(['id','name','infos']);
        },'state'=>function($query){
            $query->select(['code','name']);
        }])
            ->where('code',22)
            ->where('item_id',$this->item_id)
            ->sharedLock()
            ->orderBy('is_top','desc')
            ->orderBy('release_at','asc')
            ->get();
        DB::commit();
        /* ********** 结果 ********** */
        $result=[
            'code'=>'success',
            'message'=>'请求成功',
            'sdata'=>$news,
            'edata'=>null,
            'url'=>null
        ];
        if($request->is('api/*') || $request->ajax()){
            return response()->json($result);
        }else {
            return view('household.home')->with($result);
        }

    }

}
