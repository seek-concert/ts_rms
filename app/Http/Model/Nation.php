<?php
/*
|--------------------------------------------------------------------------
| 民族模型
|--------------------------------------------------------------------------
*/
namespace App\Http\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nation extends Model
{
    use SoftDeletes;

    protected $table='nation';
    protected $primaryKey='id';
    protected $guarded=[];
    protected $dates=['created_at','updated_at','deleted_at'];
    protected $casts = [

    ];
    /* ++++++++++ 数据字段注释 ++++++++++ */
    public $columns=[
        'name'=>'民族名称',
        'infos'=>'描述'
    ];
    /* ++++++++++ 名称去空 ++++++++++ */
    public function setNameAttribute($value)
    {
        $this->attributes['name']=trim($value);
    }
    /* ++++++++++ 设置其他数据 ++++++++++ */
    public function setOther($request){

    }
}