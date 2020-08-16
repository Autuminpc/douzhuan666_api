<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Store as StoreModel;
use app\common\model\StoreMeal as StoreMealModel;
use app\common\model\StoreMealOrder as StoreMealOrderModel;
use app\common\library\Pay as PayLib;
use app\common\library\Lock as LockLib;
use app\common\library\StoreMeal as StoreMealLib;
use app\common\library\Predis;

/**
 * 会员接口
 */
class StoreMeal extends Api
{
    protected $noNeedLogin = ['tongzhi'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }



    public function getStoreMealPrice(){
        //购买套餐，暂且固定为id是1
        $sell_amount = StoreMealModel::where([
            'id' => 1,
            'status' => 1,
            'is_delete' =>0
        ])->value('sell_amount');
        $this->success('获取成功',$sell_amount);
    }


    //购买套餐
    public function buyStoreMeal(){
        $this->error('请联系客服开通店铺');
        $user_id = $this->auth->getUser()['id'];
        $name = $this->request->post("name");
        $store_brief = $this->request->post("store_brief");
        $avatar = $this->request->post("avatar");
        $store_image = $this->request->post("store_image");
        $service_image = $this->request->post("service_image");
        if (!($name&&$store_brief&&$avatar&&$store_image&&$service_image)) {
            $this->error('缺少参数');
        }

        //查看用户是否已经是店家
        $store_id = StoreModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->value('id');
        if ($store_id) {
            $this->error('你已经开通店铺');
        }

        //购买套餐，暂且固定为id是1
        $store_meal = StoreMealModel::where([
            'id' => 1,
            'status' => 1,
            'is_delete' =>0
        ])->find();

        if (!$store_meal) {
            $this->error('商城套餐不存在');
        }



        //加锁
        if (LockLib::redisLock('buystoremeal'.$user_id, 10)) {

            $store_meal_order_data = [
                'user_id' => $user_id,
                'sn' => makeOrderNo(),
                'store_meal_id' => $store_meal['id'],
                'sell_amount' => $store_meal['sell_amount'],
                'product_public_num' => $store_meal['product_public_num'],
                'store_name' => $name,
                'store_brief' => $store_brief,
                'store_avatar' => $avatar,
                'store_image' => $store_image,
                'service_image' => $service_image,

            ];

            $store_meal_order = StoreMealOrderModel::create($store_meal_order_data);


        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('buystoremeal'.$user_id);

        if ($store_meal_order) {
            $this->success('提交成功', $store_meal_order);
        } else {
            $this->error('提交失败');
        }

    }


    //查询订单是否支付成功
    public function payState(){
        $order_id = $this->request->post('id');

        $status = StoreMealOrderModel::where('id', $order_id)->value('status');

        if ($status == 1) {
            $this->success('支付成功', 1);
        } else {
            $this->success('支付失败', 0);
        }
    }


    //获取订单
    public function getOrder(){
        $user_id = $this->auth->getUser()['id'];
        $order_id = $this->request->post('id');

        $pay_order = StoreMealOrderModel::where([
            'id' => $order_id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->field('id, sell_amount, status')->find();

        if (!$pay_order) {
            $this->error('订单状态异常');
        }

        if ($pay_order->status == 1) {
            $this->success('订单已支付', '');
        }

        $list = [
            [
                'type' => 'score',
                'text' => '余额支付',
                'image' => absolutePath('/assets/img/yue.jpg')
            ],
            [
                'type' => 1,
                'text' => '支付宝',
                'image' => absolutePath('/assets/img/alipay.jpg')
            ],
            [
                'type' => 2,
                'text' => '微信支付',
                'image' => absolutePath('/assets/img/wxpay.jpg')
            ]
        ];

        $data['id'] = $pay_order->id;
        $data['pay_amount'] = $pay_order->sell_amount;
        $data['pay_type_list'] = $list;

        $this->success('获取成功', $data);

    }


    //支付订单
    public function payOrder(){

        $user_id = $this->auth->getUser()['id'];



        $order_id = $this->request->post('id');
        $pay_type = $this->request->post('pay_type')?:'score';
        if (!($order_id && $pay_type)) {
            $this->error('缺少参数');
        }

        //查询订单
        $store_meal_order = StoreMealOrderModel::where([
            'id' => $order_id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->find();

        if (!$store_meal_order) {
            $this->error('订单不存在', '', 10100);
        }

        //订单定时器手动触发
        if ($store_meal_order->status != 0) {
            $this->error('订单状态异常');
        }

        //判断同个套餐购买时间是否超过30秒
        //$have = Predis::getInstance(0)->get('meal'.$pay_order['meal_id'].'_'.$pay_type.'_'.$user_id);
       // if ($have) {
        //    $this->error('距离上次购买该参数不足30秒，30秒后可重新购买');
        //}


        if ($pay_type == 'score') {


            $StoreMealLib = (new StoreMealLib());
            $res = $StoreMealLib->successStoreMealOrder($order_id, 'score'.time(), $pay_type);

            if ($res) {
                $this->success('开通成功');
            } else {
                $this->error('开通异常', $StoreMealLib->_error);
            }

        } else {
            $params['sn'] = $store_meal_order['sn'];
            $params['type'] = $pay_type;
            $params['subject'] = '店铺购买';
            $params['price'] = $store_meal_order['sell_amount'];
            $params['notifyurl'] = absolutePath(url('api/pay/payyxNotifyStore')); //根据实际情况修改;
            $params['extends_param'] = $store_meal_order['id'];


            //进行支付操作
            $PayLib = new PayLib();
            $pay_url = $PayLib->pay($params, $pay_type);


            if ($pay_url) {

                $this->success('下单成功', $pay_url);
            } else {
                $this->error($PayLib->_error);
            }
        }



    }

    public function tongzhi(){
        $pay_order_id =input('pay_order_id');
        $pay_no = input('pay_no');
        $payment_type = input('payment_type');
        $StoreMealLib = (new StoreMealLib());
        $res = $StoreMealLib->successStoreMealOrder($pay_order_id, $pay_no, $payment_type);
        //$this->error('处理失败', $pay_order_id);
        if ($res) {
            $this->success('处理成功');
        } else {
            $this->error('处理失败', $pay_order_id);
        }
    }



}
