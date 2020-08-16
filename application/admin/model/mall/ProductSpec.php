<?php

namespace app\admin\model\mall;

use think\Model;

class ProductSpec extends Model
{

    

    // 表名
    protected $name = 'product_spec';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [

    ];
    

    







    public function product()
    {
        return $this->belongsTo('app\admin\model\mall\Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
