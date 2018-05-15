<?php
/*
|--------------------------------------------------------------------------
| 被征户--登录入口
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Model\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    /* ========== 初始化 ========== */
    public function __construct()
    {

    }

    /* ========== 登录页 ========== */
    public function index(Request $request){
        return view('household.login')->with(session('code'),session('message'));
    }

    /* ========== 登录 ========== */
    public function login(Request $request){
        /* ********** 数据验证 ********** */
        $rules=[
            'username'=>'required',
            'password'=>'required',
        ];
        $messages=[
            'required'=>'请输入 :attribute',
        ];
        $names=[
            'username'=>'用户名',
            'password'=>'密码',
        ];
        $validator = Validator::make($request->all(),$rules,$messages,$names);
        if($validator->fails()){
            return response()->json(['code'=>'error','message'=>$validator->errors()->first(),'sdata'=>null,'edata'=>null,'url'=>null]);
        }


        /* ********** 查询用户 ********** */
        DB::beginTransaction();
        $user=Household::select(['id','username','password','land_id','building_id','item_id'])
            ->where('username',$request->input('username'))
            ->sharedLock()
            ->first();
        DB::commit();
        if(blank($user)){
            return response()->json(['code'=>'error','message'=>'用户不存在或已被禁用','sdata'=>null,'edata'=>null,'url'=>null]);
        }

        /* ********** 验证密码 ********** */
        if($request->input('password') != decrypt($user->password)){
            return response()->json(['code'=>'error','message'=>'密码错误','sdata'=>null,'edata'=>null,'url'=>null]);
        }

        /* ********** 生成session ********** */
        session(['household_user'=>[
            'user_id'=>$user->id,
            'user_name'=>$user->username,
            'secret'=>$user->secret,
            'item_id'=>$user->item_id,
            'building_id'=>$user->building_id,
            'land_id'=>$user->land_id
        ]]);

        return response()->json(['code'=>'success','message'=>'登录成功','sdata'=>session('household_user'),'edata'=>null,'url'=>route('h_home')]);
    }

    /* ========== 退出登录 ========== */
    public function logout(Request $request){
        $request->session()->forget('household_user');

        return redirect()->route('h_index');
    }

}