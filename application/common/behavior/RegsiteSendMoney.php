<?php

namespace app\common\behavior;

use app\common\model\User as UserModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\model\Config as ConfigModel;

class RegsiteSendMoney {

    public function run($dbUserObj, $aParam) {
        //赠送金额
        $money = sprintf('%.2f', ConfigModel::where('name', 'regsite_send_amount')->value('value'));
        //钱太少了就不给了
        if($money < 0.1){
            return false;
        }
        file_put_contents('regsite_send_amount.log', "{$money}----{$dbUserObj->id}\r\n", 8);
        //加钱
        UserModel::where('id', $dbUserObj->id)->setInc('money', $money);

        //用户日志
        $user_money_log_data['type']    = 11;
        $user_money_log_data['user_id'] = $dbUserObj->id;
        $user_money_log_data['money']   = $money;
        $user_money_log_data['before']  = $dbUserObj->money;
        $user_money_log_data['after']   = ($dbUserObj->money + $money);
        $user_money_log_data['remark']  = '注册首送';
        UserMoneyLogModel::create($user_money_log_data);
    }
}