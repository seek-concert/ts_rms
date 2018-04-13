<?php
/*
|--------------------------------------------------------------------------
| 被征收户-通知公告
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
use App\Http\Model\News;
use App\Http\Model\Item;
use App\Http\Model\Itemcrowd;
use App\Http\Model\Itemhouserate;
use App\Http\Model\Itemobject;
use App\Http\Model\Itemprogram;
use App\Http\Model\Itemreward;
use App\Http\Model\Itemsubject;
use App\Http\Model\Itemuser;
use App\Http\Model\Itemdraft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends BaseController{
    public function info(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error', 'message'=>'错误操作', 'sdata'=>null, 'edata'=>null, 'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('household.error')->with($result);
            }
        }

        DB::beginTransaction();
        try{
            $news=News::with(['newscate'=>function($query){
                $query->select(['id','name']);
            },'state'=>function($query){
                $query->select(['code','name']);
            }])
                ->sharedLock()
                ->find($id);
            if(blank($news)){
                throw new \Exception('该通知公告不存在',404404);
            }

            $item=Item::sharedLock()->find($this->item_id);
            if (blank($item)){
                throw new \Exception('该项目不存在',404404);
            }

            $itemdraft=null;
            $program=null;
            /*征收方案明细*/
            if($news->cate_id==3){
                $itemprogram=Itemprogram::sharedLock()
                    ->where([['item_id',$this->item_id],['code',22]])
                    ->first();
                $subjects=Itemsubject::with(['subject'=>function($query){
                    $query->select(['id','name']);
                }])
                    ->sharedLock()
                    ->where('item_id',$this->item_id)
                    ->get();
                $crowds=Itemcrowd::with(['cate'=>function($query){
                    $query->select(['id','name']);
                },'crowd'=>function($query){
                    $query->select(['id','name']);
                }])
                    ->sharedLock()
                    ->where('item_id',$this->item_id)
                    ->get();
                $house_rates=Itemhouserate::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->orderBy('start_area','asc')
                    ->get();
                $objects=Itemobject::with(['object'=>function($query){
                    $query->select(['id','name','num_unit']);
                }])
                    ->sharedLock()
                    ->where('item_id',$this->item_id)
                    ->get();
                $rewards=Itemreward::sharedLock()
                    ->where('item_id',$this->item_id)
                    ->orderBy('start_at','asc')
                    ->get();
                $program= [
                    'itemprogram'=>$itemprogram,
                    'subjects'=>$subjects,
                    'crowds'=>$crowds,
                    'house_rates'=>$house_rates,
                    'objects'=>$objects,
                    'rewards'=>$rewards
                ];
            }elseif($news->cate_id==2){
                $itemdraft=Itemdraft::where([['item_id',$this->item_id],['code',22]])
                    ->sharedLock()
                    ->first();
            }

            $code='success';
            $msg='请求成功';
            $sdata=['item'=>$item,'news'=>$news,'program'=>$program,'itemdraft'=>$itemdraft];
            $edata=null;
            $url=null;
            $view='household.news.info';
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;
            $view='household.error';
            DB::rollback();
        }
        $result=['code'=>$code, 'message'=>$msg, 'sdata'=>$sdata, 'edata'=>$edata, 'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view($view)->with($result);
        }
    }
}