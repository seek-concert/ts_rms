<?php
/*
|--------------------------------------------------------------------------
| 工具
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\gov;

use App\Http\Model\Houselayoutimg;
use App\Http\Model\Worknotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ToolsController extends BaseController
{
    public function __construct()
    {

    }

    /* ========== 上传文件 ========== */
    public function upl(Request $request){
        $files=$request->file();
        $keys=array_keys($files);
        $file=$files[$keys[0]];
        if($file->isValid()){
            $path=$file->store(date('ymd'),'public');
            $result=['code'=>'success','message'=>'上传成功','sdata'=>['path'=>'/storage/'.$path],'edata'=>null,'url'=>null];
        }else{
            $result=['code'=>'error','message'=>'文件无效','sdata'=>null,'edata'=>null,'url'=>null];
        }

        return response()->json($result);
    }

    /* ========== 高拍仪上传文件 ========== */
    public function gaopaiyi_upl(Request $request){
        try{
            $files=$request->file();
            if(!$files){
                throw new \Exception('error',404404);
            }
            $keys=array_keys($files);
            if(!$keys){
                throw new \Exception('error',404404);
            }
            $file=$files[$keys[0]];
            if(!$file){
                throw new \Exception('error',404404);
            }
            $uploads = $file->isValid();
            if(!$uploads){
                throw new \Exception('error',404404);
            }
            $path=$file->storeAs('public/'.date('ymd'),date('YmdHis'.rand(10,99)).'.jpg');
            return 'fieldname = /storage/'.$path;
        }catch(\Exception $e){
            if($e->getCode() == 404404){
                return false;
            }
        }
    }

    /* ========== 错误提示页 ========== */
    public function error(Request $request){

        return view('gov.error')->with(['code'=>session('code'),'message'=>session('message')]);
    }

    /* ========== 工作提醒数量 ========== */
    public function noticenum(Request $request){
        $num=Worknotice::where('user_id',session('gov_user.user_id'))
            ->whereIn('code',['0','20'])
            ->count();

        $result=['code'=>'success','message'=>'获取成功','sdata'=>$num,'edata'=>null,'url'=>null];
        return response()->json($result);
    }
}