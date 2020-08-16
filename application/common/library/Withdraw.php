<?php

namespace app\common\library;

use app\common\library\alipay\AlipayTransfer;
use app\common\model\Config as ConfigModel;
use app\common\model\Withdraw as WithdrawModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\model\User as UserModel;
use think\Db;
use think\Exception;

class Withdraw
{
    
    public $_error = '';


    /**
        添加提现
     */
    public function withdraw($user, $money, $draw_rate, $draw_num)
    {


        //判断余额是否足够
        if ($user['money'] < $money) {
            $this->_error = '余额不足';
            return false;
        }

        //判断次数
        $today = strtotime(date('Y-m-d'));
        $withdraw_num = WithdrawModel::where([
            'user_id' => $user->id,
            'is_delete' => 0
        ])->where('status!=4')->where('create_time', '>', $today)->count();

        if ($withdraw_num>=$draw_num) {
            $this->_error = '一天最多提现'.$withdraw_num.'次';
            return false;
        }



        //提现记录
        $withdraw_data['user_id'] = $user->id;
        $withdraw_data['amount'] = $money;
        $withdraw_data['service_amount'] = $money*$draw_rate/100;
        $withdraw_data['arrival_amount'] = $money - $withdraw_data['service_amount'];
        $withdraw_data['bank_name'] = $user->bank_name;
        $withdraw_data['subbranch_name'] = $user->subbranch_name;
        $withdraw_data['bank_user'] = $user->bank_user;
        $withdraw_data['bank_number'] = $user->bank_number;

        //$user->money -= $money;
        //增加提现统计
        //$user->totol_withdraw += $money;


        Db::startTrans();
        try {

            UserModel::where('id', $user->id)->setDec('money', $money);
            UserModel::where('id', $user->id)->setInc('totol_withdraw', $money);
            $now_money = UserModel::where('id', $user->id)->value('money');
            //$user->save();
            //用户日志
            $user_money_log_data['type'] = 6;
            $user_money_log_data['user_id'] = $user->id;
            $user_money_log_data['money'] = -$money;
            $user_money_log_data['before'] = $now_money + $money;
            $user_money_log_data['after'] = $now_money;
            UserMoneyLogModel::create($user_money_log_data);
            $mWithdraw = WithdrawModel::create($withdraw_data);

            //TODO CK增加 小金额自动审核
            $payTypeCode = ConfigModel::where('name', 'withdraw_type_code')->value('value');
            $autoWithdrawMaxAmount = ConfigModel::where('name', 'withdraw_auto_max_amount')->value('value');
            //提现通道必须打开，符合小额范围，提现金额小于等于最大阈值
            if($payTypeCode == 'alipay' && ($autoWithdrawMaxAmount >= 1 && $autoWithdrawMaxAmount <= 100) && $money <= $autoWithdrawMaxAmount){
                $params['order_no'] = str_pad($mWithdraw->id.time(),15,'0',STR_PAD_RIGHT).rand(1000,9999);
                //支付宝下发渠道
                $daifu_res = (new AlipayTransfer())->doPay($mWithdraw->id, $params['order_no']);
                $daifu_res = $daifu_res['alipay_fund_trans_toaccount_transfer_response'];
                if($daifu_res['code'] && $daifu_res['code']!='10000'){
                    $date = date('ymd');
                    dlog(json_encode($daifu_res), 'log/withdraw_auto/error_'.$date.'.log');
                    if($daifu_res['code'] == 40004){
                        throw new Exception($daifu_res['sub_msg'].',请联系客服修改');
                    }
                    throw new Exception('提现渠道暂时维护,请联系客服处理');
                }

                //记录代付结果
                $params['daifu_res'] = json_encode($daifu_res);
                $params['status'] = 3;
                $params['verify_time'] = time();
                $params['arrival_time'] = time();
                $params['verify_mark'] = '支付宝快速提现';
                $mWithdraw->save($params);
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            Db::rollback();
            return false;
        }

    }








}
