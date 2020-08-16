<?php

namespace app\admin\model\mall;

use think\Model;

class ProductOrderDetail extends Model
{


    

    // 表名
    protected $name = 'product_order_detail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [

    ];
    

    







    public function productorder()
    {
        return $this->belongsTo('app\admin\model\mall\ProductOrder', 'product_order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function product()
    {
        return $this->belongsTo('app\admin\model\mall\Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function productspec()
    {
        return $this->belongsTo('app\admin\model\mall\ProductSpec', 'product_spec_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
