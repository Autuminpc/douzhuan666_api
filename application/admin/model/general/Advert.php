<?php

namespace app\admin\model\general;

use think\Model;
// use traits\model\SoftDelete;

class Advert extends Model
{

    // use SoftDelete;

    

    // 表名
    protected $name = 'advert';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 追加属性
    protected $append = [
        "port_text",
        "status_text",
    ];

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getPortList()
    {
        return ['1' => __('Port 1'), '2' => __('Port 2')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPortTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['port']) ? $data['port'] : '');
        $list = $this->getPortList();
        return isset($list[$value]) ? $list[$value] : '';
    }





}
