<?php

namespace app\admin\model\user;

use think\Model;
use traits\model\SoftDelete;

class Meal extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'meal';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function rewarduserlevel()
    {
        return $this->belongsTo('app\admin\model\user\UserLevel', 'reward_user_level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function tasklevel()
    {
        return $this->belongsTo('app\admin\model\user\UserLevel', 'task_user_level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function taskcategory()
    {
        return $this->belongsTo('app\admin\model\task\TaskCategory', 'task_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
