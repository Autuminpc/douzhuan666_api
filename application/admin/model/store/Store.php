<?php

namespace app\admin\model\store;

use think\Model;
use traits\model\SoftDelete;

class Store extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'store';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [

    ];
    

    







    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function storemeal()
    {
        return $this->belongsTo('app\admin\model\store\StoreMeal', 'store_meal_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
