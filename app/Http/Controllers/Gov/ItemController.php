<?php
/*
|--------------------------------------------------------------------------
| 项目
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\Gov;

use App\Http\Model\Filecate;
use App\Http\Model\Filetable;
use App\Http\Model\Item;
use App\Http\Model\Itemuser;
use App\Http\Model\Menu;
use App\Http\Model\Role;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request,$next){
            $menus=Menu::sharedLock()
                ->where([
                    ['parent_id',41],
                    ['display',1]
                ])
                ->orderBy('sort','asc')
                ->get();

            $nav_menus=get_nav_li_list($menus,session('menu.cur_menu.id'),session('menu.cur_pids'),1,41);

            view()->share(['nav_menus'=>$nav_menus]);

            return $next($request);
        });
    }

    /* ========== 我的项目 ========== */
    public function index(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $total=Itemuser::where('user_id',session('gov_user.user_id'))
                ->sharedLock()
                ->count(DB::raw('DISTINCT `item_id`'));
            $per_page=15;
            $page=$request->input('page',1);
            $items=Itemuser::with(['item'=>function($query){
                $query->with(['itemadmins'=>function($query){
                    $query->select('name');
                },'state'=>function($query){
                    $query->select(['code','name']);
                },'schedule'=>function($query){
                    $query->select(['id','name']);
                },'process'=>function($query){
                    $query->select(['id','name']);
                }])->withCount('households');
            }])
                ->select(['item_id','user_id'])
                ->distinct()
                ->where('user_id',session('gov_user.user_id'))
                ->offset($per_page*($page-1))
                ->limit($per_page)
                ->orderBy('item_id','asc')
                ->get();

            $items=new LengthAwarePaginator($items,$total,$per_page,$page);
            $items->withPath(route('g_item'));

            if(blank($items)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$items;
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

        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view('gov.item.index')->with($result);
        }
    }


    /* ========== 所有项目 ========== */
    public function all(Request $request){
        /* ********** 查询 ********** */
        DB::beginTransaction();
        try{
            $items=Item::withCount('households')
                ->with(['itemadmins'=>function($query){
                    $query->select('name');
                },'state'=>function($query){
                    $query->select(['code','name']);
                },'schedule'=>function($query){
                    $query->select(['id','name']);
                },'process'=>function($query){
                    $query->select(['id','name']);
                }])
                ->sharedLock()
                ->paginate();

            if(blank($items)){
                throw new \Exception('没有符合条件的数据',404404);
            }
            $code='success';
            $msg='查询成功';
            $sdata=$items;
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

        /* ++++++++++ 结果 ++++++++++ */
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->ajax()){
            return response()->json($result);
        }else{
            return view('gov.item.all')->with($result);
        }
    }


    /* ========== 新建项目 ========== */
    public function add(Request $request){
        $model=new Item();

        if($request->isMethod('get')){
            /* ++++++++++ 必备附件分类 ++++++++++ */
            DB::beginTransaction();
            $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
            $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
            DB::commit();

            $result=['code'=>'success','message'=>'请求成功','sdata'=>['filecates'=>$file_cates],'edata'=>$model,'url'=>null];
            if($request->ajax()){
                return response()->json($result);
            }else{
                return view('gov.item.add')->with($result);
            }
        }
        /* ++++++++++ 保存 ++++++++++ */
        else{
            /* ++++++++++ 表单验证 ++++++++++ */
            $rules=[
                'name'=>'required|unique:item',
                'place'=>'required',
                'map'=>'required'
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'unique'=>':attribute 已存在',
            ];
            $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
            if($validator->fails()){
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }

            DB::beginTransaction();
            try{
                $file_table_id=Filetable::where('name','item')->sharedLock()->value('id');
                $file_cates=Filecate::where('file_table_id',$file_table_id)->sharedLock()->get();
                $rules=[];
                $messages=[];
                foreach ($file_cates as $file_cate){
                    $name='picture.'.$file_cate->filename;
                    $rules[$name]='required';
                    $messages[$name.'.required']='必须上传【'.$file_cate->name.'】';
                }
                $validator = Validator::make($request->all(),$rules,$messages);
                if($validator->fails()){
                    throw new \Exception($validator->errors()->first(),404404);
                }
                /* ++++++++++ 批量赋值 ++++++++++ */
                $item=$model;
                $item->fill($request->input());
                $item->addOther($request);
                $item->schedule_id=1;
                $item->process_id=1;
                $item->code=0;
                $item->save();
                if(blank($item)){
                    throw new \Exception('保存失败',404404);
                }

                $parent_role_id=Role::where('id',session('gov_user.role_id'))->sharedLock()->value('parent_id');

                $work_notice=new Worknotice();
                $work_notice->item_id=$item->id;
                $work_notice->schedule_id=1;
                $work_notice->process_id=1;
                $work_notice->menu_id=44;
                $work_notice->dept_id=session('gov_user.dept_id');
                $work_notice->parent_id=$parent_role_id;
                $work_notice->role_id=session('gov_user.role_id');
                $work_notice->user_id=session('gov_user.user_id');
                $work_notice->url=route('g_item_add');
                $work_notice->code=2;
                $work_notice->save();

                $code='success';
                $msg='保存成功';
                $sdata=$item;
                $edata=null;
                $url=route('g_item_all');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'保存失败';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }
            /* ++++++++++ 结果 ++++++++++ */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);

        }
    }
}