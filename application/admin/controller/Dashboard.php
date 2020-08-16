<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;

use app\admin\model\User;
use app\admin\model\task\Task;
use app\admin\model\task\TaskApply;
use app\admin\model\task\TaskSale;
use app\admin\model\finance\PayOrder;
use app\admin\model\finance\Withdraw;


/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {   
        $daytime = \fast\Date::unixtime('day');
        $seventtime = \fast\Date::unixtime('day', -7);
        $thirtytime = \fast\Date::unixtime('day', -30);
        $paylist = $createlist = [];
        for ($i = 0; $i < 30; $i++)
        {
            $day = date("Y-m-d", $thirtytime + ($i * 86400));
            $day_timestamp = strtotime($day);
            //提现
            // $createlist[$day] = PayOrder::where('create_time','BETWEEN',[$day_timestamp,$day_timestamp+86400])->where('is_delete','0')->where('status','1')->count();
            $createlist[$day] = Withdraw::where('is_delete','0')->where('status','3')->where('create_time','BETWEEN',[$day_timestamp,$day_timestamp+86400])->sum('amount');
            // $createlist[$day] = mt_rand(20, 200);
            //金额
            $paylist[$day] = PayOrder::where('create_time','BETWEEN',[$day_timestamp,$day_timestamp+86400])->where('is_delete','0')->where('status','1')->sum('pay_amount');

        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
        $this->view->assign([
            // 顶部
            'unverifyapply'    => TaskApply::where('is_delete','0')->where('status','1')->count(),
            'unverifywithdraw' => Withdraw::where('is_delete','0')->where('status','0')->count(),
            'failedwithdraw' => Withdraw::where('is_delete','0')->where('status','2')->count(),
            //平台总会员数
            'totaluser'        => User::where('is_delete','0')->count(),
            //今日注册数量
            'todayusersignup'  => User::where('is_delete','0')->where('create_time','egt',$daytime)->count(),
            //今日登陆数量
            'todayuserlogin'  => User::where('is_delete','0')->where('logintime','egt',$daytime)->count(),
            //平台充值总金额
            'totalpayorder'    => PayOrder::where('is_delete','0')->where('status','1')->sum('pay_amount'),
            //今日充值总金额
            'todaypayorder'    => PayOrder::where('is_delete','0')->where('status','1')->where('pay_time','egt',$daytime)->sum('pay_amount'),
            //七日充值总金额
            'sevenpayorder'    => PayOrder::where('is_delete','0')->where('status','1')->where('pay_time','egt',$seventtime)->sum('pay_amount'),
            //三十日充值总金额
            'mounthpayorder'    => PayOrder::where('is_delete','0')->where('status','1')->where('pay_time','egt',$thirtytime)->sum('pay_amount'),
            //任务相关
            'todaytask'        => Task::where('is_delete',0)->where('create_time','egt',$daytime)->count(),
            'seventask'        => Task::where('is_delete',0)->where('create_time','egt',$seventtime)->count(),
            'mounthtask'       => Task::where('is_delete',0)->where('create_time','egt',$thirtytime)->count(),
            'totaltask'        => Task::where('is_delete',0)->count(),
            //任务完成相关
            'todayapplytask'   => TaskApply::where('is_delete',0)->where('create_time','egt',$daytime)->count(),
            'sevenapplytask'   => TaskApply::where('is_delete',0)->where('create_time','egt',$seventtime)->count(),
            'mounthapplytask'  => TaskApply::where('is_delete',0)->where('create_time','egt',$thirtytime)->count(),
            'totalapplytask'   => TaskApply::where('is_delete','0')->count(),
            //提现总金额
            'todaywithdraw'    => Withdraw::where('is_delete','0')->where('create_time','egt',$daytime)->where('status','3')->sum('amount'),
            'totalwithdraw'    => Withdraw::where('is_delete','0')->where('status','3')->sum('amount'),
            //未提现总金额
            'usermoney'        => User::where('is_delete','0')->sum('money'),

            // 中部数据
            // 'totalsale'        => TaskSale::where('is_delete','0')->sum('price'),
            
            
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'     => $addonVersion,
            'uploadmode'       => $uploadmode
        ]);

        //定时刷新状态
        $reload = $this->request->param('reload','0');
        $this->assign('reload',$reload);
        return $this->view->fetch();
    }

}
