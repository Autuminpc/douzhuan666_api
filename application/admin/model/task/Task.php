<?php

namespace app\admin\model\task;

use think\Model;

class Task extends Model
{


    

    // 表名
    protected $name = 'task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [
        'is_user_text',
        'end_time_text',
        'status_text'
    ];
    

    
    public function getIsUserList()
    {
        return ['0' => __('Is_user 0'), '1' => __('Is_user 1')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }


    public function getIsUserTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_user']) ? $data['is_user'] : '');
        $list = $this->getIsUserList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setEndTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function taskcategory()
    {
        return $this->belongsTo('app\admin\model\task\TaskCategory', 'task_category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function taskplatform()
    {
        return $this->belongsTo('app\admin\model\task\TaskPlatform', 'task_platform_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function userlevel()
    {
        return $this->belongsTo('app\admin\model\user\UserLevel', 'user_level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
