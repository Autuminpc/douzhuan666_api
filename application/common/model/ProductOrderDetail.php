<?php

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 */
class ProductOrderDetail extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';




    //封面图
    public function getProductSpecCoverPathAttr($value, $data){

        return oosAbsolutePath($data['product_spec_cover']);

    }


}
