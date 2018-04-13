<?php
/*
|--------------------------------------------------------------------------
| 授权菜单
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;

use App\Http\Model\Menu;
use Illuminate\Http\Request;

class BaseauthController extends BaseController
{
    /* ++++++++++ 初始化 ++++++++++ */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request,$next){

            if(!$request->ajax()){
                $cur_pids=session('menu.cur_pids');
                $top_id=array_last($cur_pids);

                if(session('com_user.isAdmin')==1){
                    $menus=Menu::with(['childs'=>function($query){
                        $query->where('display',1)->orderBy('sort','asc');
                    }])
                        ->withCount(['childs'=>function($query){
                            $query->where('display',1);
                        }])
                        ->sharedLock()
                        ->where([
                            ['parent_id',$top_id],
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
                            ['parent_id',$top_id],
                            ['id','<>',332],
                            ['id','<>',333],
                            ['display',1],
                        ])
                        ->orderBy('sort','asc')
                        ->get();
                }
                $nav_menus=$this->makeMenu($menus,session('menu.cur_menu.id'),$cur_pids,1,$top_id);

                view()->share(['nav_menus'=>$nav_menus]);
            }

            return $next($request);
        });
    }
    public function makeMenu($menus,$cur_id,$pids,$level=1,$pid=0){
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
                $str .= '<li '.$li_class.'><a href="'.$menu->url.'" '.$a_class.'>'.$icon.$menu_name.$b_in_a.'</a><b class="arrow"></b>';
                $str .=$this->makeMenu($menu->childs,$cur_id,$pids,$level+1,$menu->id);;
            }else{
                $a_class='';
                $b_in_a='';
                $str .= '<li '.$li_class.'><a href="'.$menu->url.'" '.$a_class.'>'.$icon.$menu_name.'</span>'.$b_in_a.'</a><b class="arrow"></b>';
            }
            $str.='</li>';
        }
        /* ul标签class */
        $ul_class=$level==1?'nav nav-list':'submenu';
        $str ='<ul class="'.$ul_class.'">'.$str.'</ul>';

        return $str;
    }
}
