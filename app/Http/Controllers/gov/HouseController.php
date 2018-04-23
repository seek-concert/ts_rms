<?php
/*
|--------------------------------------------------------------------------
| 房源
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;
use App\Http\Model\House;
use App\Http\Model\Housecommunity;
use App\Http\Model\Housecompany;
use App\Http\Model\Housemanagefee;
use App\Http\Model\Housemanageprice;
use App\Http\Model\Houseprice;
use App\Http\Model\Layout;
use App\Http\Model\Houselayoutimg;
use App\libs\phpexcels\PHPExcel;
use App\libs\phpexcels\PHPExcel\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HouseController extends BaseauthController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
    }

    /* ========== 首页 ========== */
    public function index(Request $request){
        $select=['id','company_id','community_id','layout_id','layout_img_id',
            'building','unit','floor','number','area','total_floor','lift',
            'is_real','is_buy','is_transit','is_public','code','delive_at','deleted_at'];

        /* ********** 查询条件 ********** */
        $where=[];
        /* ********** 社区 ********** */
        $community_id = $request->input('community_id');
        if(is_numeric($community_id)){
            $where[] = ['community_id',$community_id];
            $infos['community_id']=$community_id;
        }
        /* ********** 户型 ********** */
        $layout_id = $request->input('layout_id');
        if(is_numeric($layout_id)){
            $where[] = ['layout_id',$layout_id];
            $infos['layout_id']=$layout_id;
        }
        /* ********** 状态 ********** */
        $code=$request->input('code');
        if($code){
            $where[] = ['code',$code];
            $infos['code']=$code;
        }
        /* ********** 面积起 ********** */
        $area_start=$request->input('area_start');
        if($area_start){
            $where[] = ['area','>=',$area_start];
            $infos['area_start']=$area_start;
        }
        /* ********** 面积上止 ********** */
        $area_end=$request->input('area_end');
        if($area_end){
            $where[] = ['area','<=',$area_end];
            $infos['area_end']=$area_end;
        }
        /* ********** 排序 ********** */
        $ordername=$request->input('ordername');
        $ordername=$ordername?$ordername:'id';
        $infos['ordername']=$ordername;

        $orderby=$request->input('orderby');
        $orderby=$orderby?$orderby:'asc';
        $infos['orderby']=$orderby;
        /* ********** 每页条数 ********** */
        $displaynum=$request->input('displaynum');
        $displaynum=$displaynum?$displaynum:200;
        $infos['displaynum']=$displaynum;
        /* ********** 是否删除 ********** */
        $deleted=$request->input('deleted');

        $model=new House();
        if(is_numeric($deleted) && in_array($deleted,[0,1])){
            $infos['deleted']=$deleted;
            if($deleted==0){
                $model=$model->onlyTrashed();
            }
        }
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $houses=$model->select($select)
                ->with(['housecommunity'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                }, 'layout'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                }, 'housecompany'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                },'state'=> function ($query) {
                        $query->withTrashed()->select(['code','name']);
                    }])
                ->where($where)
                ->orderBy($ordername,$orderby)
                ->sharedLock()
                ->paginate($displaynum);
            if(blank($houses)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$houses;
            $edata=['conditions'=>$infos,'house_model'=>new House()];
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
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else {
            return view('gov.house.index')->with($result);
        }
    }

    /* ========== 添加 ========== */
    public function add(Request $request){
        $model = new House();
        if($request->isMethod('get')){
            $sdata['housecompany'] = Housecompany::withTrashed()->select(['id','name'])->get();
            $sdata['layout'] = Layout::withTrashed()->select(['id','name'])->get();
            $result=['code'=>'success','message'=>'请求成功','sdata'=>$sdata,'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.house.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else {
            /* ********** 保存 ********** */
            /* ++++++++++ 表单验证 ++++++++++ */
            /*----- 房源 -----*/
            $rules = [
                'company_id' => 'required',
                'community_id' => 'required',
                'layout_id' => 'required',
                'layout_img_id' => 'required',
                'building' => 'required',
                'unit' => 'required',
                'floor' => 'required',
                'number' => 'required',
                'area' => 'required',
                'total_floor' => 'required',
                'lift' => 'required',
                'is_real' => 'required',
                'is_buy' => 'required',
                'is_transit' => 'required',
                'is_public' => 'required',
                'delive_at'=>'required_if:is_buy,1',
            ];
            $messages = [
                'required' => ':attribute 为必须项',
                'delive_at.required_if' => '购置房源必须填写交付日期',
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /*----- 房源-评估单价 -----*/
            $houseprice_model = new Houseprice();
            $rules1 = [
                'start_at' => 'required',
                'end_at' => 'required',
                'market' => 'required',
                'price' => 'required',
            ];
            $messages1 = [
                'required' => ':attribute 为必须项'
            ];
            $validator1 = Validator::make($request->input('houseprice'), $rules1, $messages1, $houseprice_model->columns);
            if ($validator1->fails()) {
                $result=['code'=>'error','message'=>$validator1->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            if($request->input('is_buy')==1){
                /*----- 房源-购置管理费单价 -----*/
                $housemanageprice_model = new Housemanageprice();
                $rules2 = [
                    'start_at' => 'required',
                    'end_at' => 'required',
                    'manage_price' => 'required'
                ];
                $messages2 = [
                    'required' => ':attribute 为必须项'
                ];
                $validator2 = Validator::make($request->all(), $rules2, $messages2, $housemanageprice_model->columns);
                if ($validator2->fails()) {
                    $result=['code'=>'error','message'=>$validator2->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                    return response()->json($result);
                }
            }



            /* ++++++++++ 新增 ++++++++++ */
            DB::beginTransaction();
            try {
                /* ++++++++++ 批量赋值 ++++++++++ */
                /*----- 房源添加 -----*/
                $house = $model;
                $house->fill($request->input());
                $house->addOther($request);
                $house->code='150';
                $house_rs = $house->save();
                if (blank($house_rs)) {
                    throw  new \Exception('添加失败', 404404);
                }
                /*----- 房源-评估单价添加 -----*/
                $houseprice = $houseprice_model;
                $houseprice->fill($request->input('houseprice'));
                $houseprice->house_id = $house->id;
                $houseprice->save();
                if (blank($houseprice)) {
                    throw  new \Exception('添加失败', 404404);
                }

                if($request->input('is_buy')==1) {
                    /*----- 房源-购置管理费单价添加 -----*/
                    $housemanageprice = $housemanageprice_model;
                    $housemanageprice->fill($request->input());
                    $housemanageprice->house_id = $house->id;
                    $housemanageprice->save();
                    if (blank($housemanageprice)) {
                        throw  new \Exception('添加失败', 404404);
                    }
                }

                $code = 'success';
                $msg = '添加成功';
                $sdata = $house;
                $edata = null;
                $url = route('g_house');
                DB::commit();
            } catch (\Exception $exception) {
                $code = 'error';
                $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
                $sdata = null;
                $edata = $house;
                $url = null;
                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 详情 ========== */
    public function info(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }
        /* ********** 当前数据 ********** */
        DB::beginTransaction();
        $house=House::withTrashed()
            ->with(['housecommunity'=> function ($query) {
                $query->withTrashed()->select(['id','name']);
            },
                'layout'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                },
                'housecompany'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                },
                 'houselayoutimg'=> function ($query) {
                    $query->withTrashed()->select(['id', 'name','picture']);
              },'state'])
            ->sharedLock()
            ->find($id);
        $house['manage_price'] = Housemanageprice::withTrashed()->where('house_id',$id)->first();
        $house['price'] = Houseprice::withTrashed()->where('house_id',$id)->first();
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
            $edata=new House();
            $url=null;

            $view='gov.house.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /* ========== 修改 ========== */
    public function edit(Request $request){
        $id=$request->input('id');
        if(!$id){
            $result=['code'=>'error','message'=>'请先选择数据','sdata'=>null,'edata'=>null,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.error')->with($result);
            }
        }

        if ($request->isMethod('get')) {
            /* ********** 当前数据 ********** */
            DB::beginTransaction();
            $house=House::withTrashed()
                ->with(['housecommunity'=> function ($query) {
                    $query->withTrashed()->select(['id','name']);
                },
                    'housecompany'=> function ($query) {
                        $query->withTrashed()->select(['id','name']);
                    },
                    'houselayoutimg'=> function ($query) {
                        $query->withTrashed()->select(['id', 'name','picture']);
                    }])
                ->sharedLock()
                ->find($id);
            $house['layout'] = Layout::withTrashed()->select(['id','name'])->get();
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
                $edata=new House();
                $url=null;

                $view='gov.house.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }else{
            $model=new House();
            /* ********** 表单验证 ********** */
            $rules=[
                'layout_id'=>'required',
                'layout_img_id'=>'required',
                'building'=>'required',
                'unit'=>'required',
                'floor'=>'required',
                'number'=>'required',
                'area'=>'required',
                'total_floor'=>'required',
                'lift'=>'required',
                'is_real'=>'required',
                'is_buy'=>'required',
                'is_transit'=>'required',
                'is_public'=>'required',
                'delive_at'=>'required_if:is_buy,1',
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'delive_at.required_if' => '购置房源必须填写交付日期',
            ];
            $validator = Validator::make($request->all(), $rules, $messages, $model->columns);
            if ($validator->fails()) {
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $house=House::withTrashed()
                    ->lockForUpdate()
                    ->find($id);
                if(blank($house)){
                    throw new \Exception('指定数据项不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $house->fill($request->input());
                $house->editOther($request);
                $house->save();
                if(blank($house)){
                    throw new \Exception('修改失败',404404);
                }
                $code='success';
                $msg='修改成功';
                $sdata=$house;
                $edata=null;
                $url=route('g_house');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常';
                $sdata=null;
                $edata=$house;
                $url=null;
                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }

    /* ========== 删除 ========== */
    public function del(Request $request){
        $ids = $request->input('id');
        if(blank($ids)){
            $result=['code'=>'error','message'=>'请选择要删除的数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        /* ********** 删除数据 ********** */
        DB::beginTransaction();
        try{
            $house_state = House::where('id',$ids)->value('code');
            if($house_state!=150){
                throw new \Exception('该房源正在被使用,暂时不能被删除！',404404);
            }
            /*---------房源----------*/
            $house = House::where('id',$ids)->forceDelete();
            if(!$house){
                throw new \Exception('删除失败',404404);
            }
            /*---------房源购置管理费单价----------*/
            $housemanageprice = Housemanageprice::where('house_id',$ids)->pluck('id');
            if(filled($housemanageprice)){
                $housemanageprice = Housemanageprice::where('house_id',$ids)->forceDelete();
                if(!$housemanageprice){
                    throw new \Exception('删除失败',404404);
                }
            }
            /*---------房源评估单价----------*/
            $houseprice = Houseprice::where('house_id',$ids)->pluck('id');
            if(filled($houseprice)){
                $houseprice = Houseprice::where('house_id',$ids)->forceDelete();
                if(!$houseprice){
                    throw new \Exception('删除失败',404404);
                }
            }
            /*---------房源购置管理费----------*/
            $housemanagefee = Housemanagefee::where('house_id',$ids)->pluck('id');
            if(filled($housemanagefee)){
                $housemanagefee = Housemanagefee::where('house_id',$ids)->forceDelete();
                if(!$housemanagefee){
                    throw new \Exception('删除失败',404404);
                }
            }

            $code='success';
            $msg='删除成功';
            $sdata=$ids;
            $edata=$house;
            $url=null;
            DB::commit();
        }catch (\Exception $exception){
            $code='error';
            $msg=$exception->getCode()==404404?$exception->getMessage():'网络异常,请刷新后重试！';
            $sdata=$ids;
            $edata=null;
            $url=null;
            DB::rollBack();
        }
        /* ********** 结果 ********** */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        return response()->json($result);
    }

    /* ========== 导出房源 ========== */
    public function house_export(Request $request){
        $ids = $request->input('ids');
        if(!$ids){
            $result=['code'=>'error','message'=>'请选择要导出的房源！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
        $ids = explode(',',$ids);
        $house_infos =House::with([
            'housecompany'=>function($query){
                $query->select(['id','name']);
            },
            'housecommunity'=>function($query){
                $query->select(['id','name']);
            },
            'layout'=>function($query){
                $query->select(['id','name']);
            },
            'state'=>function($query){
                $query->select(['id','code','name']);
            }])
            ->whereIn('house.id',$ids)
            ->get();
        /*---------- 头部 -------------*/
        $new_title = [];
        $new_title[0][0] = '序号';
        $new_title[0][1] = '管理机构';
        $new_title[0][2] = '房源社区';
        $new_title[0][3] = '户型';
        $new_title[0][4] = '位置';
        $new_title[0][5] = '面积(㎡)';
        $new_title[0][6] = '总楼层';
        $new_title[0][7] = '是否电梯房';
        $new_title[0][8] = '类型';
        $new_title[0][9] = '交付日期';
        $new_title[0][10] = '状态';
        /*---------- 数据 -------------*/
        $new_data = [];
        foreach ($house_infos as $k=>$v){
            $building = $v->building?$v->building.'栋':'';
            $unit = $v->unit?$v->unit.'单元':'';
            $floor =  $v->floor?$v->floor.'楼':'';
            $number = $v->number?$v->number.'号':'';
            $new_data[$k][] = $k+1;
            $new_data[$k][] = $v->housecompany->name;
            $new_data[$k][] = $v->housecommunity->name;
            $new_data[$k][] = $v->layout->name;
            $new_data[$k][] = $building.$unit.$floor.$number;
            $new_data[$k][] = $v->area;
            $new_data[$k][] = $v->total_floor;
            $new_data[$k][] = $v->lift;
            $new_data[$k][] = $v->is_real.'|'.$v->is_buy.'|'.$v->is_transit.'|'.$v->is_public;
            $new_data[$k][] = $v->delive_at;
            $new_data[$k][] = $v->state->name;
        }
        $datas = array_merge($new_title,$new_data);
        if($house_infos){
            export_house_xls($datas,'房源'.date('Ymd'));
        }else{
            $result=['code'=>'error','message'=>'暂无对应数据！','sdata'=>null,'edata'=>null,'url'=>null];
            return response()->json($result);
        }
    }

    /* ========== 导出【导入房源格式】 ========== */
    public function house_import_demo(Request $request){
        $new_title = [];
        $new_title[0][0] = '管理机构';
        $new_title[0][1] = '房源社区';
        $new_title[0][2] = '户型';
        $new_title[0][3] = '楼栋';
        $new_title[0][4] = '单元';
        $new_title[0][5] = '楼层';
        $new_title[0][6] = '房号';
        $new_title[0][7] = '面积(㎡)';
        $new_title[0][8] = '总楼层';
        $new_title[0][9] = '交付时间(年月日)';
        $new_title[0][10] = '是否有电梯';
        $new_title[0][11] = '是否现房';
        $new_title[0][12] = '是否购置房';
        $new_title[0][13] = '是否可作临时周转';
        $new_title[0][14] = '是否可项目共享';
        $new_title[0][15] = '房源评估开始时间(年月日)';
        $new_title[0][16] = '房源评估结束时间(年月日)';
        $new_title[0][17] = '评估市场价';
        $new_title[0][18] = '安置优惠价';
        $new_title[0][19] = '购置管理费单价(元/月)';
        $new_title[0][20] = '购置管理费单价开始时间(年)';
        $new_title[0][21] = '购置管理费单价结束时间(年)';
        $excel_data = [];
        $excel_data[1][0] = '例如：管理机构名称';
        $excel_data[1][1] = '例如：幸福小区';
        $excel_data[1][2] = '例如：一室一厅';
        $excel_data[1][3] = '例如：1';
        $excel_data[1][4] = '例如：1';
        $excel_data[1][5] = '例如：1';
        $excel_data[1][6] = '例如：1';
        $excel_data[1][7] = '例如：100';
        $excel_data[1][8] = '例如：30';
        $excel_data[1][9] = '例如：2017-1-1';
        $excel_data[1][10] = '是 或者 否 例如：是';
        $excel_data[1][11] = '现房 或者 期房 例如：现房';
        $excel_data[1][12] = '是 或者 否 例如：是';
        $excel_data[1][13] = '是 或者 否 例如：是';
        $excel_data[1][14] = '是 或者 否 例如：是';
        $excel_data[1][15] = '例如：2017-4-17';
        $excel_data[1][16] = '例如：2018-4-17';
        $excel_data[1][17] = '例如：5000';
        $excel_data[1][18] = '例如：4500';
        $excel_data[1][19] = '未购置留空，例如：10';
        $excel_data[1][20] = '未购置留空，例如：2017';
        $excel_data[1][21] = '未购置留空，例如：2018';
        $new_data = array_merge($new_title,$excel_data);
        house_import_demo_xls($new_data,'导入数据格式');
    }

//    /* ========== 导入房源 ========== */
//    public function import_house(Request $request){
//        $files=$request->file();
//        $key=array_keys($files);
//        $file = $files[$key[0]];
//        if($file->isValid()){
//            $file_name = date('YmdHis').rand(100,999);
//            $info = $file->move( './storage/'.date('ymd'),$file_name.'.xls');
//            if($info){
//                $datas = './storage/'.date('ymd').'/'.$file_name.'.xls';
//                $excel_datas = import_house($datas);
//                $add_data_array = $excel_datas['add_datas'];
//               /*-----去掉已存在的房源-------*/
//                foreach ($add_data_array as $k=>$v){
//                    $house_rs = House::where('company_id',$v['company_id'])
//                        ->where('community_id',$v['community_id'])
//                        ->where('building',$v['building'])
//                        ->where('unit',$v['unit'])
//                        ->where('floor',$v['floor'])
//                        ->where('number',$v['number'])
//                        ->count();
//                    if($house_rs){
//                        unset($add_data_array[$k]);
//                    }
//                }
//
//                foreach ($add_data_array as $key=>$val){
//                    /* ++++++++++ 新增 ++++++++++ */
//                    DB::beginTransaction();
//                    try {
//                        $model = new House();
//                        /* ++++++++++ 批量赋值 ++++++++++ */
//                        /*----- 房源添加 -----*/
//                        $house = $model;
//                        $house->save($val);
//                        $house->addOther($request);
//                        $house->code='150';
//                        $house_rs = $house->save();
//                        if (blank($house_rs)) {
//                            throw  new \Exception('添加失败', 404404);
//                        }
//                        /*----- 房源-评估单价添加 -----*/
//                        $houseprice_model = new Houseprice();
//                        $houseprice = $houseprice_model;
//                        $houseprice->fill($request->input('houseprice'));
//                        $houseprice->house_id = $house->id;
//                        $houseprice->save();
//                        if (blank($houseprice)) {
//                            throw  new \Exception('添加失败', 404404);
//                        }
//
//                        if($request->input('is_buy')==1) {
//                            /*----- 房源-购置管理费单价添加 -----*/
//                            $housemanageprice = $housemanageprice_model;
//                            $housemanageprice->fill($request->input());
//                            $housemanageprice->house_id = $house->id;
//                            $housemanageprice->save();
//                            if (blank($housemanageprice)) {
//                                throw  new \Exception('添加失败', 404404);
//                            }
//                        }
//
//                        $code = 'success';
//                        $msg = '添加成功';
//                        $sdata = $house;
//                        $edata = null;
//                        $url = route('g_house');
//                        DB::commit();
//                    } catch (\Exception $exception) {
//                        $code = 'error';
//                        $msg = $exception->getCode() == 404404 ? $exception->getMessage() : '添加失败';
//                        $sdata = null;
//                        $edata = $house;
//                        $url = null;
//                        DB::rollBack();
//                    }
//
//
//                }die;
//
//                /* ++++++++++ 结果 ++++++++++ */
//                $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
//                return response()->json($result);
//
////                $rs = model('Houses')->saveAll($add_data_array);
////                if($rs){
////                    return view($this->theme.'/house/excel_info',[
////                        'data_count'=>$excel_datas['data_count'],
////                        'success_count'=>$excel_datas['success_count'],
////                        'error_count'=>$excel_datas['error_count'],
////                        'error_data_file'=>$datas,
////                        'unique_count'=>$excel_datas['success_count']-count($add_data_array),
////                        'add_count'=>count($add_data_array)
////                    ]);
////                }else{
////                    return view($this->theme.'/house/excel_info',[
////                        'data_count'=>$excel_datas['data_count'],
////                        'success_count'=>$excel_datas['success_count'],
////                        'error_count'=>$excel_datas['error_count'],
////                        'error_data_file'=>$datas,
////                        'unique_count'=>$excel_datas['success_count']-count($add_data_array),
////                        'add_count'=>count($add_data_array)
////                    ]);
////                }
//
//            }else{
//                $result=['code'=>'error','message'=>'文件导入失败','sdata'=>null,'edata'=>null,'url'=>null];
//                return response()->json($result);
//            }
//        }
//    }
}