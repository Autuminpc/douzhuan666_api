<?php

//配置文件
return [
    'url_common_param'       => true,
    'url_html_suffix'        => '',
    'controller_auto_search' => true,

    'auth' => [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'user_group', // 用户组数据表名
        'auth_group_access' => 'user_group_access', // 用户-用户组关系表
        'auth_rule'         => 'user_rule', // 权限规则表
        'auth_user'         => 'user', // 用户信息表
    ],
    
    //FastAdmin配置
    'fastorgan'              => [
        //是否开启前台会员中心
        'usercenter'          => true,
        //登录验证码
        'login_captcha'       => true,
        //登录失败超过10次则1天后重试
        'login_failure_retry' => true,
        //是否同一账号同一时间只能在一个地方登录
        'login_unique'        => false,
        //登录页默认背景图
        'login_background'    => "/assets/img/loginbg.jpg",
        //是否启用多级菜单导航
        'multiplenav'         => false,
        //自动检测更新
        'checkupdate'         => false,
        //版本号
        'version'             => '1.0.0.20190930_beta',
        //API接口地址
        'api_url'             => 'https://api.fastadmin.net',
    ],
];
