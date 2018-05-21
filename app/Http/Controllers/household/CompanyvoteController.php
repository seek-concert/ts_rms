<?php
/*
|--------------------------------------------------------------------------
| 项目-评估公司投票
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Companyvote;
use App\Http\Model\Company;
use App\Http\Model\Household;
use App\Http\Model\Itemctrl;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class  CompanyvoteController extends BaseController
{


    public function index(Request $request){

        $per_page=15;
        $page=$request->input('page',1);

        DB::beginTransaction();
        $total=Company::sharedLock()
                ->count();

        $companys=Company::withCount(['companyvotes'=>function($query){
            $query->where('item_id',$this->item_id);
        }])
            ->orderBy('companyvotes_count','desc')
            ->sharedLock()
            ->where('type',0)
            ->offset($per_page*($page-1))
            ->limit($per_page)
            ->get();
        $companys=new LengthAwarePaginator($companys,$total,$per_page,$page);
        $companys->withPath(route('h_itemcompanyvote'));
        $companyvote=Companyvote::with(['company'=>function($query){
            $query->withCount(['companyvotes'=>function($query){
                $query->where('item_id',$this->item_id);
            }]);
        }])
            ->where([
            ['household_id',$this->household_id],
            ['item_id',$this->item_id],
        ])
            ->sharedLock()
            ->first();
        /* ++++++++++ 选房时间 ++++++++++ */
        $itemctrl=Itemctrl::sharedLock()
            ->where([
                ['item_id',$this->item_id],
                ['cate_id',1],
                ['start_at','<=',date('Y-m-d H:i:s')],
                ['end_at','>=',date('Y-m-d H:i:s')],
            ])
            ->first();

        DB::commit();
        /* ********** 结果 ********** */

        $result=[
            'code'=>'success',
            'message'=>'请求成功',
            'sdata'=>[
                'item'=>$this->item_id,
                'companyvote'=>$companyvote,
                'itemctrl'=>$itemctrl,
                'companys'=>$companys
            ],
            'edata'=>null,
            'url'=>null];
        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else {
            return view('household.itemcompanyvote.index')->with($result);
        }
    }

    public function add(Request $request){
        DB::beginTransaction();
        try{
            /* ++++++++++ 投票时间 ++++++++++ */
            $itemctrl=Itemctrl::sharedLock()
                ->where([
                    ['item_id',$this->item_id],
                    ['cate_id',1],
                    ['start_at','<=',date('Y-m-d H:i:s')],
                    ['end_at','>=',date('Y-m-d H:i:s')],
                ])
                ->first();
            if(blank($itemctrl)){
                throw new \Exception('还未到投票时间',404404);
            }

            $companyvote=Companyvote::where([
                ['household_id',$this->household_id],
                ['item_id',$this->item_id],
            ])
                ->sharedLock()
                ->first();
            if (filled($companyvote)){
                throw new \Exception('一户只有一次投票机会', 404404);
            }
            $companyvote=new Companyvote();
            $companyvote->fill($request->all());
            $companyvote->addOther($request);
            $companyvote->item_id=$this->item_id;
            $companyvote->household_id=$this->household_id;
            $companyvote->save();
            if (blank($companyvote)) {
                throw new \Exception('投票失败', 404404);
            }
            $code = 'success';
            $msg = '投票成功';
            $sdata = $companyvote;
            $edata = null;
            $url = route('h_itemcompanyvote');
            DB::commit();
        }catch (\Exception $exception){
            $code = 'error';
            $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
            $sdata = null;
            $edata = null;
            $url = null;
            DB::rollBack();
        }
        /* ********** 结果 ********** */
        $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];

        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else {
            return view('household.error');
        }


    }

    public function info(Request $request)
    {

        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'暂未投票','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('household.error')->with($result);
            }
        }
       DB::beginTransaction();
        $companyvote=Companyvote::sharedLock()
        ->find($id);
        /* ********** 查询 ********** */
      DB::commit();

        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($companyvote)){
            $code='warning';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;
        }else{
            $code='success';
            $msg='获取成功';
            $sdata=$companyvote;
            $edata=null;
            $url=null;

            $view='household.itemcompanyvote.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') || $request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }


    /*-修改页面*/
    public function edit(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            $result = ['code' => 'error', 'message' => '请先选择数据', 'sdata' => null, 'edata' => null, 'url' => null];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view('household.error')->with($result);
            }
        }
        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $model =Companyvote ::with(['item' => function ($query) {
                $query->select(['id', 'name']);
            }, 'household' => function ($query) {
                $query->select(['id', 'username']);
            }])
                ->sharedLock()
                ->find($id);
            $model->company = Company::where('type',0)->pluck('name', 'id');
            DB::commit();

            /* ++++++++++ 数据不存在 ++++++++++ */
            if (blank($model)) {
                $code = 'warning';
                $msg = '数据不存在';
                $sdata = null;
                $edata = null;
                $url = null;
            } else {
                $code = 'success';
                $msg = '获取成功';
                $sdata = $model;
                $edata = null;
                $url = null;
                $view = 'household.itemcompanyvote.edit';
            }
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' =>null, 'url' => $url];
            if ($request->ajax()) {
                return response()->json($result);
            } else {
                return view($view)->with($result);
            }
        } else {

            $company_id=$request->input('company_id');

            if(!$company_id){
                $result=['code'=>'error', 'message'=>'请选择评估机构', 'sdata'=>null, 'edata'=>null, 'url'=>null];
                if($request->ajax()){
                    return response()->json($result);
                }else {
                    return view('household.error')->with($result);
                }
            }

            /* ********** 更新 ********** */
            DB::beginTransaction();
            try {
                $company=Company::sharedLock()->find($company_id);
                if(blank($company)){
                    throw new \Exception('评估机构不存在',404404);
                }

                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $model = Companyvote::lockForUpdate()->find($id);
                if (blank($model)) {
                    throw new \Exception('指定数据项不存在', 404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $model->fill($request->all());
                $model->editOther($request);
                $model->item_id = $this->item_id;
                $model->household_id = $this->household_id;
                $model->save();
                if (blank($model)) {
                    throw new \Exception('修改失败', 404404);
                }
                $code = 'success';
                $msg = '修改成功';
                $sdata = $model;
                $edata = null;
                $url = route('h_itemcompanyvote_info');

                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络异常';
                $sdata = null;
                $edata = $exception->getMessage();
                $url = null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result = ['code' => $code, 'message' => $msg, 'sdata' => $sdata, 'edata' => $edata, 'url' => $url];
            return response()->json($result);
        }

    }
}