<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config as ConfigModel;
use app\common\model\Withdraw as WithdrawModel;
use app\common\library\Lock as LockLib;
use app\common\library\Withdraw as WithdrawLib;
use think\Validate;

/**
 * 会员接口
 */
class Withdraw extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }



    //提现界面
    public function withdraw(){
        $user = $this->auth->getUser();

        $data['bank_name'] = $user['bank_name'];
        $data['subbranch_name'] = $user['subbranch_name'];
        $data['bank_user'] = $user['bank_user'];
        $data['bank_number'] = $user['bank_number'];
        $data['money'] = $user['money'];


        $this->success('获取成功', $data);
    }


    //保存提现
    public function saveWithdraw(){

        $user = $this->auth->getUser();
        $money = $this->request->post("money");

        if (!($money && Validate::is($money, 'number') && $money>0)) {
            $this->error('参数有误');
        }

        if ($money - floor($money) >0) {
            $this->error('提现金额必须为整数');

        }


        //开始时间
        $draw_start_time = ConfigModel::where(['name' => 'draw_start_time'])->value('value');
        $draw_start_time = intval($draw_start_time);
        //结束时间
        $draw_end_time = ConfigModel::where(['name' => 'draw_end_time'])->value('value');
        $draw_end_time = intval($draw_end_time);
        //提现手续费
        $draw_rate = ConfigModel::where(['name' => 'draw_rate'])->value('value');
        //每天提现次数
        $draw_num = ConfigModel::where(['name' => 'draw_num'])->value('value');
        $draw_num = intval($draw_num);
        //提现金额倍数
        $draw_mult_money = ConfigModel::where(['name' => 'draw_mult_money'])->value('value');
        $draw_mult_money = intval($draw_mult_money);

        if (!(Validate::is($draw_rate, 'number') && $draw_rate<100)) {
            $this->error('参数有误');
        }

        $hour = (int)date('H', time());

        if (!($draw_start_time<=$hour && $draw_end_time>$hour)) {
            $this->error('提现时间为'.$draw_start_time.'~'.$draw_end_time.'点');
        }

        if ($draw_mult_money && $money%$draw_mult_money!=0) {
            $this->error('提现金额为'.$draw_mult_money.'的倍数');
        }

        if (!($user->bank_name && $user->subbranch_name && $user->bank_user &&$user->bank_number)) {
            $this->error('请补全银行卡信息');
        }

        //加锁
        if (LockLib::redisLock('withdraw'.$user['id'], 10)) {

            $WithdrawLib = new WithdrawLib();
            $res = $WithdrawLib->withdraw($user, $money, $draw_rate, $draw_num);
        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('withdraw'.$user['id']);

        if ($res) {
            $this->success('提交成功');
        } else {
            $this->error($WithdrawLib->_error);
        }

    }

    /**
     * 提现记录列表
     *
     */
    public function withdrawList()
    {

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');


        //产品列表
        $withdraw_where = [
            ['user_id', '=',  $user_id],
        ];
        //是否有订单状态条件(0待处理，1审核通过等待打款，2审核通过打款失败，3已完成，4审核不通过)
        if (isset($status) && $status !== '' && $status != '-1') {
            if ($status == 0) {
                array_push($withdraw_where, ['status', 'in', [0, 1, 2]]);
            } else {
                array_push($withdraw_where, ['status', '=', $status]);
            }
        }


        $withdraw_list = (new WithdrawModel())->getWithdrawArray($withdraw_where, $page, $row);



        $this->success('请求成功', $withdraw_list);
    }



}
