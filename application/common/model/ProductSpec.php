<?php

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 */
class ProductSpec extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';



    //封面图
    public function getCoverPathAttr($value, $data){

        return oosAbsolutePath($data['cover']);

    }


    public function getStatusStrAttr($value, $data){
        $params = ['禁用', '开启'];
        return $params[$data['status']];
    }


}
