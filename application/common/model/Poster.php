<?php

namespace app\common\model;

use think\Model;

class Poster extends Model
{

    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    //简章内通图
    public function getBgImgPathAttr($value, $data){

        return oosAbsolutePath($data['bg_img']);

    }

}
