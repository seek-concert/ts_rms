<?php
/*
|--------------------------------------------------------------------------
| 被征户-项目房源
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;

use App\Http\Model\House;
use App\Http\Model\Itemhouse;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
class ItemhouseController extends BaseController{

    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request){
        /* ********** 查询条件 ********** */
        $where=[];
        $where[] = ['item_id', $this->item_id];
        $infos['item_id'] =  $this->item_id;
        $select=['item_id','house_id','type','created_at'];
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'created_at';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        $model=new Itemhouse();
        DB::beginTransaction();
        try{
            $total=$model->sharedLock()
                ->where('item_id',$this->item_id)
                ->count();

            $itemhouses=$model
                ->with(['item'=>function($query){
                    $query->select(['id','name']);
                },
                    'house'=>function($query){
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
                ->where($where)
                ->select($select)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->get();

            $itemhouses=new LengthAwarePaginator($itemhouses,$total,$per_page,$page);
            $itemhouses->withPath(route('h_itemhouse'));
            if(blank($itemhouses)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$itemhouses;
            $edata=$infos;
            $url=null;
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=$infos;
            $url=null;
        }
        DB::commit();

        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('household.itemhouse.index')->with($result);
        }
    }

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
        $house->market=DB::table('house_price')->where('house_id',$id)->pluck('market');
        $house->market[]=DB::table('house_price')->where('house_id',$id)->orderBy('created_at','desc')->value('market');
        $house->price=DB::table('house_price')->where('house_id',$id)->pluck('price');
        $house->price[]=DB::table('house_price')->where('house_id',$id)->orderBy('created_at','desc')->value('price');
        $date=DB::table('house_price')->where('house_id',$id)->pluck('start_at');
        $date[]=DB::table('house_price')->where('house_id',$id)->orderBy('created_at','desc')->value('end_at');
        $house->date=$date;
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

            $view='household.itemhouse.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }
}