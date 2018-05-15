<?php
/*
|--------------------------------------------------------------------------
| 项目-被征收户详情 模型
|--------------------------------------------------------------------------
*/
namespace App\Http\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Householddetail extends Model
{
    use SoftDeletes;
    protected $table='item_household_detail';
    protected $primaryKey='id';
    protected $fillable=['status','register','reg_inner','reg_outer','balcony','picture','def_use','real_use',
        'has_assets','business','agree','repay_way', 'house_price','house_area','house_num','house_addr','more_price','layout_id','opinion',
        'receive_man','receive_tel','receive_addr','sign'];
    protected $dates=['created_at','updated_at','deleted_at'];
    protected $casts = [
        'picture'=>'array',
    ];
    /* ++++++++++ 数据字段注释 ++++++++++ */
    public $columns=[
        'item_id'=>'项目',
        'household_id'=>'被征收户',
        'land_id'=>'地块',
        'building_id'=>'楼栋',
        'status'=>'房屋状况',
        'register'=>'房屋产权证号',
        'reg_inner'=>'套内面积',
        'reg_outer'=>'建筑面积',
        'balcony'=>'阳台面积',
        'dispute'=>'产权争议',
        'area_dispute'=>'面积争议',
        'picture'=>'房屋证件',
        'def_use'=>'批准用途',
        'real_use'=>'实际用途',
        'has_assets'=>'是否有固定资产',
        'business'=>'经营项目',
        'agree'=>'征收意见',
        'repay_way'=>'补偿方式',
        'house_price'=>'房源单价',
        'house_area'=>'房源面积',
        'house_num'=>'房源数量',
        'house_addr'=>'房源地址',
        'more_price'=>'增加面积单价',
        'layout_id'=>'房源户型',
        'opinion'=>'其他意见',
        'receive_man'=>'收件人',
        'receive_tel'=>'收件电话',
        'receive_addr'=>'收件地址',
        'sign'=>'被征收人签名'
    ];

    /* ++++++++++ 设置添加数据 ++++++++++ */
    public function addOther($request){
        $this->attributes['household_id'] = $request->input('household_id');
        $this->attributes['land_id'] = $request->input('land_id');
        $this->attributes['building_id'] = $request->input('building_id');
        $this->attributes['dispute'] = $request->input('dispute');
        $this->attributes['area_dispute'] = $request->input('area_dispute');
    }
    /* ++++++++++ 设置修改数据 ++++++++++ */
    public function editOther($request){
        $this->attributes['dispute'] = $request->input('dispute');
        $this->attributes['area_dispute'] = $request->input('area_dispute');
    }

    /* ++++++++++ 获取房屋状况 ++++++++++ */
    public function getStatusAttribute($key=null)
    {
        $array=[0=>'正常',1=>'存在新建',2=>'存在改建',3=>'存在扩建'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 获取产权争议状态 ++++++++++ */
    public function getDisputeAttribute($key=null)
    {
        $array=[0=>'无争议',1=>'存在争议',2=>'产权明确'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 面积争议 ++++++++++ */
    public function getAreaDisputeAttribute($key=null)
    {
        $array=[0=>'无争议',1=>'待测绘',2=>'已测绘',3=>'面积明确',4=>'存在争议',5=>'再测绘'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 获取资产评估状态 ++++++++++ */
    public function getHasAssetsAttribute($key=null)
    {
        $array=[0=>'否',1=>'是'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 获取征收意见 ++++++++++ */
    public function getAgreeAttribute($key=null)
    {
        $array=[0=>'拒绝',1=>'同意'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 获取补偿方式 ++++++++++ */
    public function getRepayWayAttribute($key=null)
    {
        $array=[0=>'货币补偿',1=>'房屋产权调换'];
        if(is_numeric($key)){
            return $array[$key];
        }else{
            return $array;
        }
    }

    /* ++++++++++ 关联项目 ++++++++++ */
    public function item(){
        return $this->belongsTo('App\Http\Model\Item','item_id','id')->withDefault();
    }
    /* ++++++++++ 关联地块 ++++++++++ */
    public function itemland(){
        return $this->belongsTo('App\Http\Model\Itemland','land_id','id')->withDefault();
    }
    /* ++++++++++ 关联被征收户账号 ++++++++++ */
    public function household(){
        return $this->belongsTo('App\Http\Model\Household','household_id','id')->withDefault();
    }
    /* ++++++++++ 关联楼栋 ++++++++++ */
    public function itembuilding(){
        return $this->belongsTo('App\Http\Model\Itembuilding','building_id','id')->withDefault();
    }
    /* ++++++++++ 批准用途关联建筑用途 ++++++++++ */
    public function defbuildinguse(){
        return $this->belongsTo('App\Http\Model\Buildinguse','def_use','id')->withDefault();
    }
    /* ++++++++++ 实际用途关联建筑用途 ++++++++++ */
    public function realbuildinguse(){
        return $this->belongsTo('App\Http\Model\Buildinguse','real_use','id')->withDefault();
    }
    /* ++++++++++ 关联户型 ++++++++++ */
    public function layout(){
        return $this->belongsTo('App\Http\Model\Layout','layout_id','id')->withDefault();
    }
    /* ++++++++++ 关联被征户房屋建筑 ++++++++++ */
    public function householdbuildings(){
        return $this->hasMany('App\Http\Model\Householdbuilding','household_id','household_id');
    }
    /* ++++++++++ 关联被征户资产 ++++++++++ */
    public function householdassetss(){
        return $this->hasMany('App\Http\Model\Householdassets','household_id','household_id');
    }

    /* ++++++++++ 关联评估房屋建筑 ++++++++++ */
    public function estatebuildings(){
        return $this->hasMany('App\Http\Model\Estatebuilding','household_id','household_id');
    }
}