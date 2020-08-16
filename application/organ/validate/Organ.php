<?php

namespace app\organ\validate;

use think\Validate;

class Organ extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'account' => 'require|unique:organ',
        // 'nickname' => 'require',
        'password' => 'require|regex:\S{32}',
        // 'email'    => 'require|email|unique:organ,email',
    ];

    /**
     * 提示消息
     */
    protected $message = [
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['account', 'password'],
        'edit' => ['account', 'password'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'account' => __('Username'),
            // 'nickname' => __('Nickname'),
            'password' => __('Password'),
            // 'email'    => __('Email'),
        ];
        $this->message = array_merge($this->message, [
            'account.regex' => __('Please input correct username'),
            'password.regex' => __('Please input correct password')
        ]);
        parent::__construct($rules, $message, $field);
    }

}
