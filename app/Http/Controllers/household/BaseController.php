<?php
/*
|--------------------------------------------------------------------------
| 基础控制器
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Controllers\Controller;
use App\Http\Model\Household;
use App\Http\Model\Item;
use App\Http\Model\Itemuser;
use App\Http\Model\Menu;
use App\Http\Model\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseController extends Controller
{
    public $item_id;
    public $item;
    public $household_id;
    public $household;
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct(Request $request)
    {
        if($request->is('api/*')){
            $this->household_id=$request->input('household_id');
            $this->household=Household::find($this->household_id);
            $this->item_id=$this->household->item_id;
        }else{
            $this->middleware(function ($request,$next){
                $this->item_id=session('household_user.item_id');
                $this->household_id=session('household_user.user_id');
                $this->household=Household::find($this->household_id);
                $this->item=Item::find($this->item_id);
                if(blank($this->item)){
                    $result=['code'=>'error','message'=>'项目不存在','sdata'=>null,'edata'=>null,'url'=>null];
                    if(request()->ajax()){
                        return response()->json($result);
                    }else{
                        return back()->with($result);
                    }
                }

                $url=request()->getPathInfo();
                /* ++++++++++ 当前菜单 ++++++++++ */
                $current_menu=Menu::select('id','parent_id','name','infos','url')->where('url',$url)->sharedLock()->first();
                if(blank($current_menu)){
                    $result=['code'=>'error','message'=>'功能已禁用或不存在','sdata'=>null,'edata'=>null,'url'=>null];
                    if(request()->ajax()){
                        return response()->json($result);
                    }else{
                        return back()->with($result);
                    }
                }
                if(!request()->ajax()){
                    /* ++++++++++ 所有父级菜单 ++++++++++ */
                    $parents_menus=$this->get_parents_menus($current_menu);
                    if($parents_menus){
                        $parents_menus_ids=$parents_menus['parents_menus_ids'];
                        $parents_menus=$parents_menus['parents_menus'];
                        krsort($parents_menus);
                    }else{
                        $parents_menus_ids=[];
                        $parents_menus=[];
                    }

                    /* ++++++++++ 所有菜单 ++++++++++ */
                    $where=[
                        ['module',2],
                        ['login',1],
                        ['display',1],
                    ];
                    $menus=Menu::sharedLock()->where($where)->orderBy('sort','asc')->get();
                    /* ++++++++++ 导航菜单树 ++++++++++ */
                    $nav_menus=get_nav_li_list($menus,$current_menu->id,$parents_menus_ids);

                    view()->share(['nav'=>$nav_menus,'parents_menus'=>$parents_menus,'current_menu'=>$current_menu]);
                }

                return $next($request);
            });
        }

    }

    /* ========== 获取所有父级菜单 ========== */
    public function get_parents_menus($menu)
    {
        static $parents_menus=null;
        static $parents_menus_ids=null;
        if($menu->parent_id){
            $parent_menu=Menu::sharedLock()->find($menu->parent_id);
            if($parent_menu){
                $parents_menus[]=$parent_menu;
                $parents_menus_ids[]=$parent_menu->id;
                if($parent_menu->parent_id){
                    $this->get_parents_menus($parent_menu);
                }
            }
        }
        if($parents_menus){
            return ['parents_menus'=>$parents_menus,'parents_menus_ids'=>$parents_menus_ids];
        }else{
            return false;
        }
    }

    /* ========== 消息推送至征收管理端 ========== */
    public function send_work_notice($process_id,$url,$param){
        /* ++++++++++ 协议审查 可操作人员 ++++++++++ */
        $itemusers=Itemuser::with(['role'=>function($query){
            $query->select(['id','parent_id']);
        }])
            ->where([
                ['item_id',$this->item_id],
                ['process_id',$process_id],
            ])
            ->get();
        /* ++++++++++ 协议审查 工作提醒推送 ++++++++++ */
        $values=[];
        foreach ($itemusers as $user){
            $values[]=[
                'item_id'=>$user->item_id,
                'schedule_id'=>$user->schedule_id,
                'process_id'=>$user->process_id,
                'menu_id'=>$user->menu_id,
                'dept_id'=>$user->dept_id,
                'parent_id'=>$user->role->parent_id,
                'role_id'=>$user->role_id,
                'user_id'=>$user->user_id,
                'url'=>route($url,$param,false),
                'code'=>'20',
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ];
        }

        $field=['item_id','schedule_id','process_id','menu_id','dept_id','parent_id','role_id','user_id','url','code','created_at','updated_at'];
        $sqls=batch_update_or_insert_sql('item_work_notice',$field,$values,'updated_at');
        if(!$sqls){
            throw new \Exception('操作失败',404404);
        }
        foreach ($sqls as $sql){
            DB::statement($sql);
        }
    }
}
