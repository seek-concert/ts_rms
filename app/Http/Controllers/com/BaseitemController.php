<?php
/*
|--------------------------------------------------------------------------
| 项目菜单
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;
use App\Http\Model\Item;
use App\Http\Model\Itemcompany;
use App\Http\Model\Menu;
use App\Http\Model\Process;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;

class BaseitemController extends BaseController
{
    public $item_id;
    public $item;
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request,$next){
            $item_id=$request->input('item');
            if(blank($item_id)){
                $result=['code'=>'error','message'=>'请指定征收项目','sdata'=>null,'edata'=>null,'url'=>null];
                if(request()->ajax()){
                    return response()->json($result);
                }else{
                    return redirect()->route('c_error')->with($result);
                }
            }
            $this->item_id=$item_id;

            $item_ids=Itemcompany::distinct()->where('company_id',session('com_user.company_id'))->pluck('item_id');
            if(!in_array($item_id,$item_ids->toArray())){
                $result=['code'=>'error','message'=>'暂无此征收项目','sdata'=>null,'edata'=>null,'url'=>null];
                if(request()->ajax()){
                    return response()->json($result);
                }else{
                    return redirect()->route('c_error')->with($result);
                }
            }
            $this->item=Item::sharedLock()->find($item_id);



            if(!$request->ajax()){
                /*===========资产评估机构菜单与房产评估机构菜单============*/
                if(session('com_user.type')==1){
                    $menus=Menu::with(['childs'=>function($query){
                        $query->where('display',1)->orderBy('sort','asc');
                    }])
                        ->withCount(['childs'=>function($query){
                            $query->where('display',1);
                        }])
                        ->sharedLock()
                        ->where([
                            ['parent_id',265],
                            ['id','<>',265],
                            ['id','<>',269],
                            ['display',1],
                        ])
                        ->orderBy('sort','asc')
                        ->get();
                }else{
                    $menus=Menu::with(['childs'=>function($query){
                        $query->where('display',1)->orderBy('sort','asc');
                    }])
                        ->withCount(['childs'=>function($query){
                            $query->where('display',1);
                        }])
                        ->sharedLock()
                        ->where([
                            ['parent_id',265],
                            ['id','<>',265],
                            ['display',1],
                        ])
                        ->orderBy('sort','asc')
                        ->get();
                }


                $nav_menus=$this->makeMenu2($menus,session('menu.cur_menu.id'),session('menu.cur_pids'),1,265,$item_id);

                view()->share(['nav_menus'=>$nav_menus,'item'=>$this->item]);
            }

            return $next($request);
        });
    }

    public function makeMenu2($menus,$cur_id,$pids,$level=1,$pid=0,$item_id){
        $str='';

        foreach($menus as $menu){
            if($level==1){
                $menu_name='<span class="menu-text">'.$menu->name.'</span>';
            }else{
                $menu_name=$menu->name;
            }
            /* 第二级菜单图标改为箭头 */
            if($level==2){
                $icon='<i class="menu-icon fa fa-caret-right"></i>';
            }else{
                $icon=$menu->icon;
            }
            /* li标签class */
            if(in_array($menu->id,$pids)){
                $li_class=' class="active open" ';
            }elseif($menu->id==$cur_id){
                $li_class=' class="active" ';
            }else{
                $li_class='';
            }

            if($menu->childs_count){
                $a_class=' class="dropdown-toggle" ';
                $b_in_a='<b class="arrow fa fa-angle-down"></b>';
                $str .= '<li '.$li_class.'><a href="'.$menu->url.'?item='.$item_id.'" '.$a_class.'>'.$icon.$menu_name.$b_in_a.'</a><b class="arrow"></b>';
                $str .=$this->makeMenu2($menu->childs,$cur_id,$pids,$level+1,$menu->id,$item_id);;
            }else{
                $a_class='';
                $b_in_a='';
                $str .= '<li '.$li_class.'><a href="'.$menu->url.'?item='.$item_id.'" '.$a_class.'>'.$icon.$menu_name.'</span>'.$b_in_a.'</a><b class="arrow"></b>';
            }
            $str.='</li>';
        }
        /* ul标签class */
        $ul_class=$level==1?'nav nav-list':'submenu';
        $str ='<ul class="'.$ul_class.'">'.$str.'</ul>';

        return $str;
    }
}
