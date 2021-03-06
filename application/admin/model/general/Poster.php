<?php

namespace app\admin\model\general;

use think\Model;
use traits\model\SoftDelete;

class Poster extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'poster';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [

    ];
    

    







}
