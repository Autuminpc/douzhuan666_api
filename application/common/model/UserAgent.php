<?php

namespace app\common\model;

use think\Model;

class UserAgent extends Model
{

    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];

}
