<?php

namespace app\common\library;

use app\common\model\PayOrder as PayOrderModel;
use app\common\model\User as UserModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\model\StoreMealOrder as StoreMealOrderModel;
use app\common\model\Store as StoreModel;
use think\Db;
use think\Exception;

class StoreMeal
{

    public $_error = '';



    public function successStoreMealOrder($pay_order_id, $pay_no, $payment_type = 'score'){

        Db::startTrans();
        try{
            $store_meal_order = StoreMealOrderModel::where('id', $pay_order_id)->find();
            if (!$store_meal_order){
                throw new Exception('该店铺套餐充值申请记录不存在');
            }

            if ($store_meal_order['status']==1) {
                Db::rollback();
                return true;
                //throw new Exception('该店铺套餐充值申请记录已处理');

            }

            //变更金额(店铺套餐金额)
            $order_amount = $store_meal_order['sell_amount'];
            //用户id
            $user_id = $store_meal_order['user_id'];
            $pay_time = time();

            //更新支付订单状态
            $store_meal_order_res = StoreMealOrderModel::where([
                'id' => $pay_order_id,
                'status' => 0,
                'is_delete' => 0
            ])->update([
                'payment_type' => $payment_type,
                'pay_sn' => $pay_no,
                'pay_time' => $pay_time,
                'status' => 1
            ]);


            //如果为余额支付
            if ($payment_type == 'score') {


                //获取当前余额
                $now_money = UserModel::where('id', $user_id)->value('money');
                //判断余额是否足够
                if ($now_money < $order_amount) {
                    throw new Exception('余额不足');
                }
                //减少余额
                UserModel::where('id', $user_id)->setDec('money', $order_amount);

                $after_money = UserModel::where('id', $user_id)->value('money');
                //增加流水记录
                $user_money_log_data = [
                    'type' => 10,
                    'user_id' => $user_id,
                    'money' => -$order_amount,  //变动金额
                    'before' => $after_money + $order_amount, //当前金额 - 变动金额 = 变动前金额
                    'after' => $after_money,                   //变动后金额
                    'remark' => '店铺开通购买',
                    'no' => $store_meal_order['sn']
                ];
                UserMoneyLogModel::create($user_money_log_data);

            } else {

                //获取当前余额
                $now_money = UserModel::lock(true)->where('id', $user_id)->value('money');
                //增加充值记录（因为为支付充值）
                $user_money_log_data[] = [
                    'type' => 4,
                    'user_id' => $user_id,
                    'money' => $order_amount,  //变动金额
                    'before' => $now_money,
                    'after' => $now_money + $order_amount,//变动后金额
                    'remark' => '订单充值入账',
                    'no' => $store_meal_order['sn']
                ];
                //增加订单消费支出记录
                $user_money_log_data[] = [
                    'type' => 10,
                    'user_id' => $user_id,
                    'money' => -$order_amount,  //变动金额
                    'before' => $now_money + $order_amount,
                    'after' => $now_money,//变动后金额
                    'remark' => '店铺开通购买',
                    'no' => $store_meal_order['sn']
                ];
                (new UserMoneyLogModel())->saveAll($user_money_log_data);



                //现金支付需要增加记录
                $pay_order_data =[
                    'order_type' => 3,
                    'user_id' => $user_id,
                    'meal_id' => $store_meal_order['store_meal_id'],
                    'order_no' => $store_meal_order['sn'],
                    'pay_no' => $pay_no,
                    'payment_type' => $payment_type,
                    'pay_amount' => $order_amount,
                    'status' => 1,
                    'pay_time' => $pay_time
                ];
                PayOrderModel::create($pay_order_data);

            }
            //查看该用户是否存在店铺
            $have_store = StoreModel::where([
                'user_id' => $user_id,
                'is_delete' => 0
            ])->find();

            if ($have_store) {
                throw new Exception('已开通过店铺');
            }

            //拼接店铺创建数据
            $store_add_data['user_id'] = $user_id;
            $store_add_data['store_meal_hidden'] = $store_meal_order['store_meal_id'];
            $store_add_data['store_meal_id'] = $store_meal_order['store_meal_id'];
            $store_add_data['name'] = $store_meal_order['store_name'];
            $store_add_data['store_brief'] = $store_meal_order['store_brief'];
            $store_add_data['avatar'] = $store_meal_order['store_avatar'];
            $store_add_data['store_image'] = $store_meal_order['store_image'];
            $store_add_data['service_image'] = $store_meal_order['service_image'];
            //创建店铺
            StoreModel::create($store_add_data);

            //只有支付订单更新成功才进行处理，防止接口方频繁通知
            if ($store_meal_order_res) {
                Db::commit();
                return $pay_order_id;
            } else {

                throw new Exception('订单变更异常');
            }


        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }
    }



}
