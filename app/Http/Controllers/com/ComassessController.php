<?php
/*
|--------------------------------------------------------------------------
| 评估
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Assess;
use App\Http\Model\Assets;
use App\Http\Model\Comassessvaluer;
use App\Http\Model\Companyhousehold;
use App\Http\Model\Companyvaluer;
use App\Http\Model\Compublic;
use App\Http\Model\Compublicdetail;
use App\Http\Model\Estate;
use App\Http\Model\Estatebuilding;
use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Household;
use App\Http\Model\Householdassets;
use App\Http\Model\Itemland;
use App\Http\Model\Itemprogram;
use App\Http\Model\Itempublic;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ComassessController extends BaseitemController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ++++++++++ 评估首页[房产-资产] ++++++++++ */
    public function index(Request $request){
        $infos = [];
        $where = [];
        $item_id = $this->item_id;
        $infos['item_id'] = $item_id;
        $where[] = ['item_id',$item_id];

        $company_id = session('com_user.company_id');
        $infos['company_id'] = $company_id;
        $where[] = ['company_id',$company_id];
        $type = session('com_user.type');
        $infos['type'] = $type;
        /* ********** 每页条数 ********** */
        $per_page=15;
        $page=$request->input('page',1);
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            /* ********** 是否有资产 ********** */
            if($type==1){
                $total=Companyhousehold::sharedLock()
                    ->leftJoin('item_household as h', 'item_company_household.household_id','=','h.id')
                    ->leftJoin('com_assess_estate as c', 'h.id', '=', 'c.household_id')
                    ->where('item_company_household.item_id',$item_id)
                    ->where('item_company_household.company_id',$company_id)
                    ->whereBetween('c.code',[130,136])
                    ->where('c.has_assets',1)
                    ->count();
                $households = Companyhousehold::with([
                    'household'=>function($querys){
                        $querys->with([
                            'itemland' => function ($query) {
                                $query->select(['id', 'address']);
                            },
                            'itembuilding' => function ($query) {
                                $query->select(['id', 'building']);
                            },
                            'estates'=>function($query){
                                $query->whereBetween('code',[130,136])->select(['id','household_id','code','has_assets']);
                            },
                            'assets'=>function($query){
                                $query->whereBetween('code',[130,136])->select(['id','household_id','code']);
                            }
                        ]);
                    }])
                    ->where($where)
                    ->offset($per_page*($page-1))
                    ->limit($per_page)
                    ->get();
            }else{
                $total=Companyhousehold::sharedLock()
                    ->leftJoin('item_household as h', 'item_company_household.household_id', '=', 'h.id')
                    ->leftJoin('com_assess_estate as c', 'h.id', '=', 'c.household_id')
                    ->where('item_company_household.item_id',$item_id)
                    ->where('item_company_household.company_id',$company_id)
                    ->whereBetween('c.code',[130,136])
                    ->count();
                $households = Companyhousehold::with([
                    'household'=>function($querys){
                        $querys->with([
                            'itemland' => function ($query) {
                                $query->select(['id', 'address']);
                            },
                            'itembuilding' => function ($query) {
                                $query->select(['id', 'building']);
                            },
                            'estates'=>function($query){
                                $query->whereBetween('code',[130,136])->select(['id','household_id','code','has_assets']);
                            }
                        ]);
                    }])
                    ->where($where)
                    ->offset($per_page*($page-1))
                    ->limit($per_page)
                    ->get();
            }
            $households=new LengthAwarePaginator($households,$total,$per_page,$page);
            $households->withPath(route('c_comassess'));
            if(blank($households)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$households;
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
            return view('com.comassess.index')->with($result);
        }
    }

    /* ++++++++++ 开始评估[房产-资产] ++++++++++ */
    public function add(Request $request){
      $item_id = $this->item_id;
      $household_id = $request->input('household_id');
      if(blank($household_id)){
          $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
          if($request->ajax()){
              return response()->json($result);
          }else {
              return view('com.error')->with($result);
          }
      }
      $item = $this->item;
      $type = session('com_user.type');
      $company_id = session('com_user.company_id');
      if($request->isMethod('get')){
          /*----------- 资产房产公共数据 ---------------*/
          $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
          $data['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');
          $data['item_id'] = $item_id;
          $data['item'] = $item;
          $data['type'] = $type;
          $data['household_id'] = $household_id;
          $data['household'] = Household::with([
              'itemland'=>function($query){
                  $query->select(['id','address']);
              },
              'itembuilding'=>function($query){
                  $query->select(['id','building']);
              },
              'householddetail'=>function($query){
                  $query->select(['id','household_id','register','reg_inner','reg_outer',
                      'balcony','dispute','picture','def_use','real_use',
                      'has_assets','agree','repay_way','layout_id']);
              }])->sharedLock()
              ->find($household_id);
          $household = $data['household'];
          /*=================== 添加汇总及房产资产数据 ====================*/
          DB::beginTransaction();
          try{
              /*----------- 【添加评估-汇总】---------------*/
              $comassess = Assess::where('item_id',$item_id)->where('household_id',$household_id)->first();
              if(blank($comassess)){
                  $comassess = new Assess();
                  $comassess->item_id = $item_id;
                  $comassess->household_id = $household_id;
                  $comassess->land_id = $household->land_id;
                  $comassess->building_id = $household->building_id;
                  $comassess->assets = 0;
                  $comassess->estate = 0;
                  $comassess->code = 130;
                  $comassess->save();
                  if(blank($comassess)){
                      throw new \Exception('数据异常',404404);
                  }
              }
              /*----------- 添加评估数据-【房产】【资产】---------------*/
              if($type==0){
                  /*=== 房产 ===*/
                  $estate = Estate::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->lockForUpdate()->first();
                  if(blank($estate)){
                      throw new \Exception('数据异常',404404);
                  }
                  if($estate->assess_id==0){
                      $estate->assess_id = $comassess->id;
                      $estate->save();
                      if(blank($estate)){
                          throw new \Exception('数据异常',404404);
                      }
                  }

                  $data['estatebuildings'] = Estatebuilding::where('item_id',$item_id)
                      ->where('household_id',$household_id)
                      ->where('company_id',$company_id)
                      ->get();
              }else{
                  /*=== 资产 ===*/
                  $assets = Assets::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->sharedLock()->first();
                  if(blank($assets)){
                     $assets = new Assets();
                     $assets->item_id = $item_id;
                     $assets->household_id = $household_id;
                     $assets->land_id = $household->land_id;
                     $assets->building_id = $household->building_id;
                     $assets->assess_id = $comassess->id;
                     $assets->company_id = $company_id;
                     $assets->total = 0;
                     $assets->code = 130;
                     $assets->save();
                      if(blank($assets)){
                          throw new \Exception('数据异常',404404);
                      }
                  }
                  $data['assets'] = $assets;

                  $data['householdassetss']=Householdassets::with([
                          'itemland'=>function($query){
                              $query->select(['id','address']);
                          },
                          'itembuilding'=>function($query){
                              $query->select(['id','building']);
                          }])
                      ->where('item_id',$item_id)
                      ->where('household_id',$household_id)
                      ->sharedLock()
                      ->get();
              }
              $data['valuer'] = Companyvaluer::where('company_id',$company_id)->where('valid_at','>=',date('Y-m-d'))->get()?:[];
              $data['estate'] = Estate::with([
                  'defbuildinguse'=>function($query){
                      $query->select(['id','name']);
                  },
                  'realbuildinguse'=>function($query){
                      $query->select(['id','name']);
                  }])
                  ->where('item_id',$item_id)
                  ->where('household_id',$household_id)
                  ->first();

              $code = 'success';
              $msg = '请求成功';
              $sdata = $data;
              $edata = null;
              $url = null;
              DB::commit();
          }catch (\Exception $exception){
              $code = 'error';
              $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
              $sdata = null;
              $edata = null;
              $url = null;
              DB::rollBack();
          }
          if($code=='error'){
              $view = 'com.error';
          }else{
              $view = 'com.comassess.add';
          }
          /* ********** 结果 ********** */
          $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
          return view($view)->with($result);
      }else{
          /*================================【开始评估】=======================================*/
        /*------------------- 数据填写验证 -----------------------*/
        $valuer_id = $request->input('valuer_id');
        $picture = $request->input('picture');
        if($type==0){
            $prices = $request->input('price');
            $price_datas = [];
            $household_ids = [];
            $i = 0;
            foreach ($prices as $k=>$v){
                if(blank($v)){
                    $result=['code'=>'error','message'=>'评估单价不能为空','sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
                $household_ids[] = $k;
                $price_datas[$i]['id'] = $k;
                $price_datas[$i]['price'] = $v;
                $i++;
            }
        }else{
            $total = $request->input('total');
            if(blank($total)){
                $result=['code'=>'error','message'=>'资产总价不能为空','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
        }
          /*------------------- 数据填写验证 -----------------------*/
        $rules=[
            'valuer_id'=>'required'
        ];
        $messages=[
            'required'=>':attribute 不能为空'
        ];
        $fild_msg = [
            'valuer_id'=>'评估师'
        ];
        $validator = Validator::make($request->all(), $rules, $messages, $fild_msg);
        if ($validator->fails()) {
            $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        /*------------------- 被征户基本信息 -----------------------*/
        $household=Household::sharedLock()->find($household_id);
         if($type==0){
            /*============================【房产数据】==============================*/
             /*------------------- 房产汇总信息 -----------------------*/
             $estates = Estate::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->first();
             /*------------------- 评估价格修改数据 -----------------------*/
            $realputer = Estatebuilding::select(['id','real_outer','real_use'])->whereIn('id',$household_ids)->get();
            /*****************【数据验证】**********************/
            if(count($price_datas) == count($realputer)){
                $main_total = 0;
                $tag_total = 0;
                foreach ($realputer as $k=>$v){
                    if($price_datas[$k]['id']==$v->id){
                        $amount = $price_datas[$k]['price']*$v->real_outer;
                        $price_datas[$k]['amount'] = $amount;
                        $price_datas[$k]['updated_at'] = date('Y-m-d H:i:s');
                        if($v->real_use==5){
                            /*附属物价格*/
                            $tag_total += $amount;
                        }else{
                            /*主体建筑价格*/
                            $main_total += $amount;
                        }
                    }
                }
                /*总价*/
                $total = $tag_total+ $main_total;
            }else{
                $result=['code'=>'error','message'=>'数据异常','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /*------------------- 【评估师评估记录数据组装】 -----------------------*/
            $valuer_datas = [];
            foreach ($valuer_id as $key=>$val){
                $valuer_datas[$key]['item_id'] = $item_id;
                $valuer_datas[$key]['household_id'] = $household_id;
                $valuer_datas[$key]['land_id'] = $household->land_id;
                $valuer_datas[$key]['building_id'] = $household->building_id;
                $valuer_datas[$key]['assess_id'] = $estates->assess_id;
                $valuer_datas[$key]['assets_id'] = 0;
                $valuer_datas[$key]['estate_id'] = $estates->id;
                $valuer_datas[$key]['company_id'] = $company_id;
                $valuer_datas[$key]['valuer_id'] = $val;
                $valuer_datas[$key]['created_at'] = date('Y-m-d H:i:s');
                $valuer_datas[$key]['updated_at'] = date('Y-m-d H:i:s');
            }
        }else{
            /*============================【资产数据】==============================*/
            /*------------------- 资产汇总信息 -----------------------*/
            $assets = Assets::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->first();
            /*------------------- 评估师评估记录数据 -----------------------*/
            $valuer_datas = [];
            foreach ($valuer_id as $key=>$val){
                $valuer_datas[$key]['item_id'] = $item_id;
                $valuer_datas[$key]['household_id'] = $household_id;
                $valuer_datas[$key]['land_id'] = $household->land_id;
                $valuer_datas[$key]['building_id'] = $household->building_id;
                $valuer_datas[$key]['assess_id'] = $assets->assess_id;
                $valuer_datas[$key]['assets_id'] = $assets->id;
                $valuer_datas[$key]['estate_id'] = 0;
                $valuer_datas[$key]['company_id'] = $company_id;
                $valuer_datas[$key]['valuer_id'] = $val;
                $valuer_datas[$key]['created_at'] = date('Y-m-d H:i:s');
                $valuer_datas[$key]['updated_at'] = date('Y-m-d H:i:s');
            }
        }

          /*============================【评估操作】==============================*/
        /* ********** 评估 ********** */
        DB::beginTransaction();
        try{
            if($type==0){
                /* ++++++++++ 评估房产建筑价格 ++++++++++ */
                $upd_field = ['id','price','amount','updated_at'];
                $sqls = batch_update_sql('com_estate_building', $upd_field, $price_datas,$upd_field);
                if (!$sqls) {
                    throw new \Exception('数据错误', 404404);
                }
                foreach ($sqls as $sql) {
                    DB::statement($sql);
                }
                /* ++++++++++ 修改房产评估汇总数据 ++++++++++ */
                $estate = Estate::where('id',$estates->id)->update(['main_total'=>$main_total,'tag_total'=>$tag_total,'code'=>'132','total'=>$total,'updated_at'=>date('Y-m-d H:i:s')]);
                if(blank($estate)){
                    throw new \Exception('数据错误', 404404);
                }
                /* ++++++++++ 修改评估汇总数据 ++++++++++ */
                /*检测是否资产和房产都已评估完成*/
                if($estates->getOriginal('has_assets')==0){
                    /*已评估完成*/
                    $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                    if(blank($comassess)){
                        throw new \Exception('数据错误', 404404);
                    }
                }else{
                    $assets =  Assets::sharedLock()->where('assess_id',$estates->assess_id)->value('code');
                    if($assets==132){
                        /*已评估完成*/
                        $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($comassess)){
                            throw new \Exception('数据错误', 404404);
                        }
                    }else{
                        $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'131','updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($comassess)){
                            throw new \Exception('数据错误', 404404);
                        }
                    }
                }
                /* ++++++++++ 查询评估师评估记录数据 ++++++++++ */
                $comassessvaluers = Comassessvaluer::where('household_id',$household_id)->where('estate_id','<>','0')->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                if(blank($comassessvaluers)){
                    throw new \Exception('数据错误', 404404);
                }
            }else{
                /* ++++++++++ 修改资产评估汇总数据 ++++++++++ */
                $assetss = Assets::where('id',$assets->id)->update(['total'=>$total,'code'=>132,'updated_at'=>date('Y-m-d H:i:s')]);
                if(blank($assetss)){
                    throw new \Exception('数据错误', 404404);
                }
                /* ++++++++++ 修改评估汇总数据 ++++++++++ */
                /*检测是否资产和房产都已评估完成*/
                $estate_code =  Estate::sharedLock()->where('assess_id',$assets->assess_id)->value('code');
                if($estate_code==132){
                    /*已评估完成*/
                    $comassess = Assess::where('id',$assets->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                    if(blank($comassess)){
                        throw new \Exception('数据错误', 404404);
                    }
                }else{
                    $comassess = Assess::where('id',$assets->assess_id)->update(['estate'=>$total,'code'=>'131','updated_at'=>date('Y-m-d H:i:s')]);
                    if(blank($comassess)){
                        throw new \Exception('数据错误', 404404);
                    }
                }
                /* ++++++++++ 查询评估师评估记录数据 ++++++++++ */
                $comassessvaluers = Comassessvaluer::where('household_id',$household_id)->where('assets_id','<>','0')->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                if(blank($comassessvaluers)){
                    throw new \Exception('数据错误', 404404);
                }
            }
            /* ++++++++++ 添加评估师评估记录 ++++++++++ */
            $comassessvaluer = new Comassessvaluer();
            $comassessvaluer::insert($valuer_datas);
            if(blank($comassessvaluer)){
                throw new \Exception('数据错误', 404404);
            }

            $code='success';
            $msg='修改成功';
            $sdata=null;
            $edata=null;
            $url=route('c_comassess',['item'=>$item_id]);

            DB::commit();
        }catch (\Exception $exception){

            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
            $sdata=null;
            $edata=null;
            $url=null;
            DB::rollBack();
        }

            /******结果*****/
          $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
          return response()->json($result);
      }
    }

    /* ++++++++++ 修改评估[房产-资产] ++++++++++ */
    public function info(Request $request){
        $item_id = $this->item_id;
        $household_id = $request->input('household_id');
        if(blank($household_id)){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else {
                return view('com.error')->with($result);
            }
        }
        $item = $this->item;
        $type = session('com_user.type');
        $company_id = session('com_user.company_id');
        if($request->isMethod('get')){
            /*----------- 资产房产公共数据 ---------------*/
            $data['item_program'] = Itemprogram::where('item_id',$item_id)->first();
            $data['item_id'] = $item_id;
            $data['item'] = $item;
            $data['type'] = $type;
            $data['household_id'] = $household_id;
            $data['household'] = Household::with([
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'householddetail'=>function($query){
                    $query->select(['id','household_id','register','reg_inner','reg_outer',
                        'balcony','dispute','picture','def_use','real_use',
                        'has_assets','agree','repay_way','layout_id']);
                }])->sharedLock()
                ->find($household_id);
            $household = $data['household'];
            /*=================== 添加汇总及房产资产数据 ====================*/
            DB::beginTransaction();
            try{
                /*----------- 【添加评估-汇总】---------------*/
                $comassess = Assess::where('item_id',$item_id)->where('household_id',$household_id)->first();
                if(blank($comassess)){
                    $comassess = new Assess();
                    $comassess->item_id = $item_id;
                    $comassess->household_id = $household_id;
                    $comassess->land_id = $household->land_id;
                    $comassess->building_id = $household->building_id;
                    $comassess->assets = 0;
                    $comassess->estate = 0;
                    $comassess->code = 0;
                    $comassess->save();
                    if(blank($comassess)){
                        throw new \Exception('数据异常',404404);
                    }
                }
                /*----------- 添加评估数据-【房产】【资产】---------------*/
                if($type==0){
                    /*=== 房产 ===*/
                    $estate = Estate::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->lockForUpdate()->first();
                    if(blank($estate)){
                        throw new \Exception('数据异常',404404);
                    }
                    if($estate->assess_id==0){
                        $estate->assess_id = $comassess->id;
                        $estate->save();
                        if(blank($estate)){
                            throw new \Exception('数据异常',404404);
                        }
                    }
                    $data['estatebuildings'] = Estatebuilding::where('item_id',$item_id)
                        ->where('household_id',$household_id)
                        ->where('company_id',$company_id)
                        ->get();

                }else{
                    /*=== 资产 ===*/
                    $assets = Assets::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->sharedLock()->first();
                    if(blank($assets)){
                        $assets = new Assets();
                        $assets->item_id = $item_id;
                        $assets->household_id = $household_id;
                        $assets->land_id = $household->land_id;
                        $assets->building_id = $household->building_id;
                        $assets->assess_id = $comassess->id;
                        $assets->company_id = $company_id;
                        $assets->total = 0;
                        $assets->code = 130;
                        $assets->save();
                        if(blank($assets)){
                            throw new \Exception('数据异常',404404);
                        }
                    }
                    $data['assets'] = $assets;

                    $data['householdassetss']=Householdassets::with([
                        'itemland'=>function($query){
                            $query->select(['id','address']);
                        },
                        'itembuilding'=>function($query){
                            $query->select(['id','building']);
                        }])
                        ->where('item_id',$item_id)
                        ->where('household_id',$household_id)
                        ->sharedLock()
                        ->get();
                }
                $data['comassessvaluers'] = Comassessvaluer::where('item_id',$item_id)
                    ->where('household_id',$household_id)
                    ->where('company_id',$company_id)
                    ->pluck('valuer_id');
                $data['estate'] = Estate::with([
                    'defbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    },
                    'realbuildinguse'=>function($query){
                        $query->select(['id','name']);
                    }])
                    ->where('item_id',$item_id)
                    ->where('household_id',$household_id)
                    ->first();
                $data['valuer'] = Companyvaluer::where('company_id',$company_id)->where('valid_at','>=',date('Y-m-d'))->get()?:[];
                $file_table_id=Filetable::where('name','com_assess_estate')->sharedLock()->value('id');
                $data['filecates']=Filecate::where('file_table_id',$file_table_id)->sharedLock()->pluck('name','filename');

                $code = 'success';
                $msg = '请求成功';
                $sdata = $data;
                $edata = null;
                $url = null;
                DB::commit();
            }catch (\Exception $exception){
                $code = 'error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            if($code=='error'){
                $view = 'com.error';
            }else{
                $view = 'com.comassess.info';
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return view($view)->with($result);
        }else{
            /*================================【开始评估】=======================================*/
            /*------------------- 数据填写验证 -----------------------*/
            $valuer_id = $request->input('valuer_id');
            $picture = $request->input('picture');
            if($type==0){
                $prices = $request->input('price');
                $price_datas = [];
                $household_ids = [];
                $i = 0;
                foreach ($prices as $k=>$v){
                    if(blank($v)){
                        $result=['code'=>'error','message'=>'评估单价不能为空','sdata'=>null,'edata'=>null,'url'=>null];
                        return response()->json($result);
                    }
                    $household_ids[] = $k;
                    $price_datas[$i]['id'] = $k;
                    $price_datas[$i]['price'] = $v;
                    $i++;
                }
            }else{
                $total = $request->input('total');
                if(blank($total)){
                    $result=['code'=>'error','message'=>'资产总价不能为空','sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
            }
            /*------------------- 数据填写验证 -----------------------*/
            $rules=[
                'valuer_id'=>'required',
                'picture'=>'required'
            ];
            $messages=[
                'required'=>':attribute 不能为空'
            ];
            $fild_msg = [
                'valuer_id'=>'评估师',
                'picture'=>'评估报告'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $fild_msg);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /*------------------- 被征户基本信息 -----------------------*/
            $household=Household::sharedLock()->find($household_id);
            if($type==0){
                /*============================【房产数据】==============================*/
                /*------------------- 房产汇总信息 -----------------------*/
                $estates = Estate::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->first();
                /*------------------- 评估价格修改数据 -----------------------*/
                $realputer = Estatebuilding::select(['id','real_outer','real_use'])->whereIn('id',$household_ids)->get();
                /*****************【数据验证】**********************/
                if(count($price_datas) == count($realputer)){
                    $main_total = 0;
                    $tag_total = 0;
                    foreach ($realputer as $k=>$v){
                        if($price_datas[$k]['id']==$v->id){
                            $amount = $price_datas[$k]['price']*$v->real_outer;
                            $price_datas[$k]['amount'] = $amount;
                            $price_datas[$k]['updated_at'] = date('Y-m-d H:i:s');
                            if($v->real_use==5){
                                /*附属物价格*/
                                $tag_total += $amount;
                            }else{
                                /*主体建筑价格*/
                                $main_total += $amount;
                            }
                        }
                    }
                    /*总价*/
                    $total = $tag_total+ $main_total;
                }else{
                    $result=['code'=>'error','message'=>'数据异常','sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
                /*------------------- 【评估师评估记录数据组装】 -----------------------*/
                $valuer_datas = [];
                foreach ($valuer_id as $key=>$val){
                    $valuer_datas[$key]['item_id'] = $item_id;
                    $valuer_datas[$key]['household_id'] = $household_id;
                    $valuer_datas[$key]['land_id'] = $household->land_id;
                    $valuer_datas[$key]['building_id'] = $household->building_id;
                    $valuer_datas[$key]['assess_id'] = $estates->assess_id;
                    $valuer_datas[$key]['assets_id'] = 0;
                    $valuer_datas[$key]['estate_id'] = $estates->id;
                    $valuer_datas[$key]['company_id'] = $company_id;
                    $valuer_datas[$key]['valuer_id'] = $val;
                    $valuer_datas[$key]['created_at'] = date('Y-m-d H:i:s');
                    $valuer_datas[$key]['updated_at'] = date('Y-m-d H:i:s');
                }
            }else{
                /*============================【资产数据】==============================*/
                /*------------------- 资产汇总信息 -----------------------*/
                $assets = Assets::where('item_id',$item_id)->where('household_id',$household_id)->where('company_id',$company_id)->first();
                /*------------------- 评估师评估记录数据 -----------------------*/
                $valuer_datas = [];
                foreach ($valuer_id as $key=>$val){
                    $valuer_datas[$key]['item_id'] = $item_id;
                    $valuer_datas[$key]['household_id'] = $household_id;
                    $valuer_datas[$key]['land_id'] = $household->land_id;
                    $valuer_datas[$key]['building_id'] = $household->building_id;
                    $valuer_datas[$key]['assess_id'] = $assets->assess_id;
                    $valuer_datas[$key]['assets_id'] = $assets->id;
                    $valuer_datas[$key]['estate_id'] = 0;
                    $valuer_datas[$key]['company_id'] = $company_id;
                    $valuer_datas[$key]['valuer_id'] = $val;
                    $valuer_datas[$key]['created_at'] = date('Y-m-d H:i:s');
                    $valuer_datas[$key]['updated_at'] = date('Y-m-d H:i:s');
                }
            }

            /*============================【评估操作】==============================*/
            /* ********** 评估 ********** */
            DB::beginTransaction();
            try{
                if($type==0){
                    /* ++++++++++ 评估房产建筑价格 ++++++++++ */
                    $upd_field = ['id','price','amount','updated_at'];
                    $sqls = batch_update_sql('com_estate_building', $upd_field, $price_datas,$upd_field);
                    if (!$sqls) {
                        throw new \Exception('数据错误', 404404);
                    }
                    foreach ($sqls as $sql) {
                        DB::statement($sql);
                    }
                    /* ++++++++++ 修改房产评估汇总数据 ++++++++++ */
                    $estate = Estate::where('id',$estates->id)->update(['main_total'=>$main_total,'tag_total'=>$tag_total,'total'=>$total,'code'=>131,'picture'=>json_encode($picture),'updated_at'=>date('Y-m-d H:i:s')]);
                    if(blank($estate)){
                        throw new \Exception('数据错误', 404404);
                    }

                    /* ++++++++++ 修改评估汇总数据 ++++++++++ */
                    /*检测是否资产和房产都已评估完成*/
                    if($estates->getOriginal('has_assets')==0){
                        /*已评估完成*/
                        $estate = Estate::where('id',$estates->id)->update(['code'=>132,'updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($estate)){
                            throw new \Exception('数据错误', 404404);
                        }
                        $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($comassess)){
                            throw new \Exception('数据错误', 404404);
                        }
                    }else{
                        $assets =  Assets::sharedLock()->where('assess_id',$estates->assess_id)->value('code');
                        if($assets==131||$assets==132){
                            /*已评估完成*/
                            $estate = Estate::where('id',$estates->id)->update(['code'=>132,'updated_at'=>date('Y-m-d H:i:s')]);
                            if(blank($estate)){
                                throw new \Exception('数据错误', 404404);
                            }
                            $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                            if(blank($comassess)){
                                throw new \Exception('数据错误', 404404);
                            }
                        }else{
                            $comassess = Assess::where('id',$estates->assess_id)->update(['estate'=>$total,'code'=>'131','updated_at'=>date('Y-m-d H:i:s')]);
                            if(blank($comassess)){
                                throw new \Exception('数据错误', 404404);
                            }
                        }
                    }

                    /* ++++++++++ 查询评估师评估记录数据 ++++++++++ */
                    $comassessvaluers = Comassessvaluer::where('household_id',$household_id)->where('estate_id','<>','0')->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                    if(blank($comassessvaluers)){
                        throw new \Exception('数据错误', 404404);
                    }
                }else{
                    /* ++++++++++ 修改资产评估汇总数据 ++++++++++ */
                    $assetss = Assets::where('id',$assets->id)->update(['total'=>$request->input('total'),'code'=>131,'picture'=>json_encode($picture),'updated_at'=>date('Y-m-d H:i:s')]);
                    if(blank($assetss)){
                        throw new \Exception('数据错误', 404404);
                    }
                    /* ++++++++++ 修改评估汇总数据 ++++++++++ */
                    /*检测是否资产和房产都已评估完成*/
                    $estate_code =  Estate::sharedLock()->where('assess_id',$assets->assess_id)->value('code');
                    if($estate_code==131||$estate_code==132){
                        /*已评估完成*/
                        $assetss = Assets::where('id',$assets->id)->update(['code'=>132,'updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($assetss)){
                            throw new \Exception('数据错误', 404404);
                        }
                        $comassess = Assess::where('id',$assets->assess_id)->update(['estate'=>$total,'code'=>'132','updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($comassess)){
                            throw new \Exception('数据错误', 404404);
                        }
                    }else{
                        $comassess = Assess::where('id',$assets->assess_id)->update(['estate'=>$total,'code'=>'131','updated_at'=>date('Y-m-d H:i:s')]);
                        if(blank($comassess)){
                            throw new \Exception('数据错误', 404404);
                        }
                    }
                    /* ++++++++++ 查询评估师评估记录数据 ++++++++++ */
                    $comassessvaluers = Comassessvaluer::where('household_id',$household_id)->where('assets_id','<>','0')->where('item_id',$item_id)->where('company_id',$company_id)->forceDelete();
                    if(blank($comassessvaluers)){
                        throw new \Exception('数据错误', 404404);
                    }
                }
                /* ++++++++++ 添加评估师评估记录 ++++++++++ */
                $comassessvaluer = new Comassessvaluer();
                $comassessvaluer::insert($valuer_datas);
                if(blank($comassessvaluer)){
                    throw new \Exception('数据错误', 404404);
                }

                $code='success';
                $msg='修改成功';
                $sdata=null;
                $edata=null;
                $url=route('c_comassess',['item'=>$item_id]);

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=null;
                $url=null;
                DB::rollBack();
            }

            /******结果*****/
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }



    /* ++++++++++ 评估首页[公共附属物] ++++++++++ */
    public function publiclist(Request $request){
        $item_id = $this->item_id;
        $company_id = session('com_user.company_id');
        $infos['item_id'] = $item_id;
        $infos['company_id'] = $company_id;
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $householde_ids=Companyhousehold::where('item_id',$item_id)
                ->where('company_id',$company_id)
                ->sharedLock()
                ->pluck('household_id');
            if(blank($householde_ids)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $land_ids = Household::sharedLock()->whereIn('id',$householde_ids)->distinct()->pluck('land_id');
            if(blank($land_ids)){
                throw new \Exception('没有符合条件的数据',404404);
            }
           $remove_land_id = Compublicdetail::where('item_id',$item_id)->where('company_id',$company_id)->distinct()->pluck('land_id');
            if(filled($remove_land_id)){
                $diff = $land_ids->diff($remove_land_id);
            }else{
                $diff = $land_ids;
            }
            $land_infos = Itemland::sharedLock()->whereIn('id',$diff)->get();
            if(blank($land_infos)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$land_infos;
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
            return view('com.comassess.publiclist')->with($result);
        }
    }

    /* ++++++++++ 评估[公共附属物] ++++++++++ */
    public function publicadd(Request $request){
        $model=new Compublic();
        $item_id = $this->item_id;
        $company_id = session('com_user.company_id');
        if($request->isMethod('get')){
            $land_id = $request->input('land_id');
            if(blank($land_id)){
                return redirect()->back();
            }
            $sdata['itempublics'] = Itempublic::where('item_id',$item_id)
                ->whereIn('land_id',$land_id)
                ->get();
            $sdata['item_id'] = $item_id;
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>null,'url'=>null];
            return view('com.comassess.publicadd')->with($result);
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ++++++++++ 表单验证 ++++++++++ */
            $price = $request->input('price');
            $picture = $request->input('picture');
            $publicdetail_datas = [];
            $i = 0;
            foreach ($price as $k=>$v){
                if(blank($v)){
                    $result=['code'=>'error','message'=>'评估单价不能为空','sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
                $publicdetail_datas[$i]['item_id'] = $item_id;
                $publicdetail_datas[$i]['item_public_id'] = $k;
                $publicdetail_datas[$i]['company_id'] = $company_id;
                $publicdetail_datas[$i]['price'] = $v;
                $i++;
            }
            /* ++++++++++ 数据验证 ++++++++++ */
            $item_public_id = $request->input('item_public_id');
            if(count($price)!=count($item_public_id)){
                $result=['code'=>'error','message'=>'数据异常','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            $itempublic = Itempublic::whereIn('id',$item_public_id)->get();
            if(blank($itempublic)){
                $result=['code'=>'error','message'=>'数据异常','sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules = [
                'picture' => 'required'
            ];
            $messages = [
                'required' => ':attribute不能为空'
            ];
            $filed_msg = [
                'picture'=>'评估报告'
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $filed_msg);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 评估-公共附属物 ++++++++++ */
                $compublic = $model;
                $compublic->item_id = $item_id;
                $compublic->company_id = $company_id;
                $compublic->total = 0;
                $compublic->picture = $picture;
                $compublic->save();
                if (blank($compublic)) {
                    throw new \Exception('添加失败', 404404);
                }
                /* ++++++++++ 评估-公共附属物明细 ++++++++++ */
                $totals = 0;
                foreach ($itempublic as $k=>$v){
                    for ($j=0;$j<count($itempublic);$j++) {
                        if ($publicdetail_datas[$j]['item_public_id'] == $v->id) {
                            $amount = $publicdetail_datas[$j]['price'] * $v->number;
                            $publicdetail_datas[$j]['land_id'] = $v->land_id;
                            $publicdetail_datas[$j]['building_id'] = $v->building_id;
                            $publicdetail_datas[$j]['com_public_id'] = $compublic->id;
                            $publicdetail_datas[$j]['amount'] = $amount;
                            $publicdetail_datas[$j]['created_at'] = date('Y-m-d H:i:s');
                            $publicdetail_datas[$j]['updated_at'] = date('Y-m-d H:i:s');
                            $totals += $amount;
                        }
                    }
                }
                $compublicdetail = Compublicdetail::insert($publicdetail_datas);
                if(blank($compublicdetail)){
                    throw new \Exception('添加失败', 404404);
                }
                /* ++++++++++ 【修改总价】评估-公共附属物 ++++++++++ */
                $compublics_data =  Compublic::lockForUpdate()->find($compublic->id);
                $compublics_data->total = $totals;
                $compublics_data->save();
                if(blank($compublics_data)){
                    throw new \Exception('添加失败', 404404);
                }
                $code = 'success';
                $msg = '添加成功';
                $sdata = $compublic;
                $edata = null;
                $url = route('c_comassess_publiclist',['item'=>$item_id]);
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '网络错误';
                $sdata = null;
                $edata = null;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }


}