<?php
/*
|--------------------------------------------------------------------------
| 工具
|--------------------------------------------------------------------------
*/
namespace App\Http\Controllers\com;

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

    /* ========== 错误提示页 ========== */
    public function error(Request $request){

        return view('com.error')->with(['code'=>session('code'),'message'=>session('message')]);
    }

}