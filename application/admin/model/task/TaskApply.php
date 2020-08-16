<?php

namespace app\admin\model\task;

use think\Model;
use traits\model\SoftDelete;

class TaskApply extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'task_apply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [
        'status_text',
        'submit_time_text',
        'verify_time_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSubmitTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['submit_time']) ? $data['submit_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getVerifyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['verify_time']) ? $data['verify_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSubmitTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setVerifyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function task()
    {
        return $this->belongsTo('app\admin\model\task\Task', 'task_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
