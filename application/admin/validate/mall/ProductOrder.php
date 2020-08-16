<?php

namespace app\admin\validate\mall;

use think\Validate;

class ProductOrder extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'consignee_name' => 'require,status=0',
        'consignee_mobile' => 'require|number',
        'consignee_address'    => 'require|chsAlphaNum',
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
        'edit_address' => [
            'consignee_name',
            'consignee_mobile',
            'consignee_address',
        ],
    ];
    
}
