<?php
/*
|--------------------------------------------------------------------------
| 被征户--个人中心
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\household;
header('Access-Control-Allow-Origin:*');
use App\Http\Model\Household;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class   ItemhouseholdController extends BaseController
{


    /* ========== 个人信息 ========== */
    public function info(Request $request){
        /* ********** 获取数据 ********** */
        $select=['id','unit','floor','number','type','username','infos','created_at','updated_at','deleted_at','code','land_id','building_id'];
        DB::beginTransaction();
        $user=Household::with(['item'=>function($query){
                $query->select(['id','name']);
            },
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                },
                'state'=>function($query){
                    $query->select(['code','name']);
                }
                ])
            ->where('id',session('household_user.user_id'))
            ->select($select)
            ->sharedLock()
            ->first();
        DB::commit();
        /* ++++++++++ 数据不存在 ++++++++++ */
        if(blank($user)){
            $code='error';
            $msg='数据不存在';
            $sdata=null;
            $edata=null;
            $url=null;

            $view='household.error';
        }else{
            $code='success';
            $msg='查询成功';
            $sdata=$user;
            $edata=null;
            $url=null;

            $view='household.itemhousehold.info';
        }
        $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
        if($request->is('api/*') ||$request->ajax()){
            return response()->json($result);
        }else{
            return view($view)->with($result);
        }
    }

    /* ========== 修改 ========== */
    public function edit(Request $request){
        if($request->isMethod('get')){
            /* ********** 获取数据 ********** */
            $select=['id','unit','floor','number','type','username','infos','created_at','updated_at','deleted_at','land_id','building_id'];
            DB::beginTransaction();
            $user=Household::with(['item'=>function($query){
                $query->select(['id','name']);
            },
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->where('id',session('household_user.user_id'))
                ->select($select)
                ->sharedLock()
                ->first();
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($user)){
                $code='error';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='household.error';
            }else{
                $code='success';
                $msg='查询成功';
                $sdata=$user;
                $edata=null;
                $url=null;

                $view='household.itemhousehold.edit';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->is('api/*') ||$request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 保存 ********** */
        else{
            /* ********** 表单验证 ********** */
            $model=new Household();
            $rules=[
                'username'=>['required','alpha_num','between:4,20','unique:user,username,'.session('household_user.user_id').',id']
            ];
            $messages=[
                'required'=>':attribute 为必须项',
                'alpha_num'=>':attribute 须为字母或与数字组合',
                'between'=>':attribute 长度在 :min 到 :max 位之间',
                'unique'=>':attribute 已占用'
            ];

            $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
            if($validator->fails()){
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $user=Household::withTrashed()->where('id',session('household_user.user_id'))->lockForUpdate()->first();
                if(blank($user)){
                    throw new \Exception('数据不存在',404404);
                }
                /* ++++++++++ 处理其他数据 ++++++++++ */
                $user->fill($request->input());
                $user->save();
                if(blank($user)){
                    throw new \Exception('修改失败',404404);
                }

                $code='success';
                $msg='保存成功';
                $sdata=$user;
                $edata=null;
                $url=route('h_itemhousehold_info');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'保存失败';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }


    /* ========== 修改密码 ========== */
    public function password(Request $request){
        if($request->isMethod('get')){
            /* ********** 获取数据 ********** */
            $select=['id','unit','floor','number','type','username','infos','created_at','updated_at','deleted_at','land_id','building_id'];
            DB::beginTransaction();
            $user=Household::with(['item'=>function($query){
                $query->select(['id','name']);
            },
                'itemland'=>function($query){
                    $query->select(['id','address']);
                },
                'itembuilding'=>function($query){
                    $query->select(['id','building']);
                }])
                ->where('id',session('household_user.user_id'))
                ->select($select)
                ->sharedLock()
                ->first();
            DB::commit();
            /* ++++++++++ 数据不存在 ++++++++++ */
            if(blank($user)){
                $code='error';
                $msg='数据不存在';
                $sdata=null;
                $edata=null;
                $url=null;

                $view='household.error';
            }else{
                $code='success';
                $msg='查询成功';
                $sdata=$user;
                $edata=null;
                $url=null;

                $view='household.itemhousehold.password';
            }
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            if($request->is('api/*') ||$request->ajax()){
                return response()->json($result);
            }else{
                return view($view)->with($result);
            }
        }
        /* ********** 更新 ********** */
        else{
            /* ********** 表单验证 ********** */
            $model=new Household();
            $rules=[
                'oldpassword'=>'required',
                'password'=>'required|min:6|confirmed',
            ];
            $messages=[
                'oldpassword.required'=>'输入当前密码',
                'password.required'=>'输入新密码',
                'password.min'=>'新密码 长度至少 :min 位',
                'password.confirmed'=>'重复密码输入错误',
            ];

            $validator = Validator::make($request->all(),$rules,$messages,$model->columns);
            if($validator->fails()){
                $result=['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null];
                return response()->json($result);
            }
            /* ********** 更新 ********** */
            DB::beginTransaction();
            try{
                /* ++++++++++ 锁定数据模型 ++++++++++ */
                $user=Household::select(['id','password'])->where('id',session('household_user.user_id'))->lockForUpdate()->first();
                if(blank($user)){
                    throw new \Exception('数据不存在',404404);
                }
                if($request->input('oldpassword') != decrypt($user->password)){
                    throw new \Exception('当前密码输入错误',404404);
                }
                if($request->input('password') == decrypt($user->password)){
                    throw new \Exception('新密码与当前密码不能相同',404404);
                }

                /* ++++++++++ 处理其他数据 ++++++++++ */
                $user->password=encrypt($request->input('password'));
                $user->save();
                if(blank($user)){
                    throw new \Exception('修改失败',404404);
                }

                $code='success';
                $msg='保存成功';
                $sdata=$user;
                $edata=null;
                $url=route('h_index');

                $request->session()->forget('household_user');

                DB::commit();
            }catch (\Exception $exception){
                $code='error';
                $msg=$exception->getCode()==404404?$exception->getMessage():'保存失败';
                $sdata=null;
                $edata=null;
                $url=null;

                DB::rollBack();
            }
            /* ********** 结果 ********** */
            $result=['code'=>$code,'message'=>$msg,'sdata'=>$sdata,'edata'=>$edata,'url'=>$url];
            return response()->json($result);
        }
    }
}