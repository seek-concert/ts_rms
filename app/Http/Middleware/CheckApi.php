<?php
/*
|--------------------------------------------------------------------------
| 检查是否指定项目
|--------------------------------------------------------------------------
*/
namespace App\Http\Middleware;
use App\Http\Model\Household;
use Closure;

class CheckApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $secret=$request->input('secret');
        $household_id=$request->input('household_id');
        if(blank($secret) || blank($household_id)){
            $result=['code'=>'error','message'=>'参数错误，请求失败！','sdata'=>null,'edata'=>null,'url'=>null];
            if(request()->ajax() || $request->is('api/*')){
                return response()->json($result);
            }else{
                return redirect()->route('h_error')->with($result);
            }
        }

        $household=Household::find($household_id);
        if($secret!=$household->secret){
            $result=['code'=>'error','message'=>'参数错误，请求失败！','sdata'=>null,'edata'=>null,'url'=>null];
            if(request()->ajax() || $request->is('api/*')){
                return response()->json($result);
            }else{
                return redirect()->route('h_error')->with($result);
            }
        }

        return $next($request);
    }
}
