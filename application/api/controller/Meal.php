<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Meal as MealModel;
use app\common\model\PayOrder as PayOrderModel;
use app\common\model\Config as ConfigModel;
use app\common\library\Pay as PayLib;
use app\common\library\Meal as MealLib;
use app\common\library\Lock as LockLib;
use app\common\library\MealDeal as MealDealLib;
use app\common\library\Predis;
use think\Validate;

/**
 * 会员接口
 */
class Meal extends Api
{
    protected $noNeedLogin = ['tongzhi', 'dj_tongzhi', 'xx'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }



    //套餐列表
    public function mealList(){



        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');

        $meal_list_ad = ConfigModel::where('id', 29)->value('value');

        //产品列表
        $meal_where = [
            ['status', '=',  1],
        ];
        //是否有订单状态条件(0待处理，1审核通过等待打款，2审核通过打款失败，3已完成，4审核不通过)
        if (isset($status) && $status !== '' && $status != '-1') {
            if ($status == 0) {
                array_push($withdraw_where, ['status', 'in', [0, 1, 2]]);
            } else {
                array_push($withdraw_where, ['status', '=', $status]);
            }
        }


        $meal_list = (new MealModel())->getMealArray($meal_where, $page, $row);

        $meal_list['meal_list_ad'] = oosAbsolutePath($meal_list_ad);

        $this->success('获取成功', $meal_list);
    }


    //购买套餐
    public function buyMeal(){

       
       
        //$this->error('系统维护中');
       
        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        if (!$id) {
            $this->error('参数有误');
        }

        $meal = MealModel::where([
            'id' => $id,
            'status' => 1,
            'is_delete' =>0
        ])->find();

        if (!$meal) {
            $this->error('套餐不存在', '', 10100);
        }



        //加锁
        if (LockLib::redisLock('buymeal'.$user_id, 10)) {

            $MealLib = new MealLib();
            $res = $MealLib->buyMeal($user_id, $meal);
        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('buymeal'.$user_id);

        if ($res) {
            $this->success('提交成功', $res);
        } else {
            $this->error($MealLib->_error);
        }

    }


    //查询订单是否支付成功
    public function payState(){


        $order_id = $this->request->post('id');

     //   $Mealdeallib = (new Mealdeallib());
     //   $res = $Mealdeallib->completeMeal($order_id, '', 'alipay');

        $status = PayOrderModel::where('id', $order_id)->value('status');

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

        $pay_order = PayOrderModel::where([
            'id' => $order_id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->field('id, pay_amount, status')->find();

        if (!$pay_order) {
            $this->error('订单状态异常');
        }

        if ($pay_order->status == 1) {
            $this->success('订单已支付', '', 11111);
        }

        $list = [
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
        $data['pay_amount'] = $pay_order->pay_amount;
        $data['pay_type_list'] = $list;

        $this->success('获取成功', $data);

    }


    //支付订单
    public function payOrder(){

        $user_id = $this->auth->getUser()['id'];



        $order_id = $this->request->post('id');
        $amount = $this->request->post('amount');
        $pay_type = $this->request->post('pay_type')?:1;
        if (!($order_id && $pay_type)) {
            $this->error('缺少参数');
        }

        //查询订单
        $pay_order = PayOrderModel::where([
            'id' => $order_id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->find();

        if (!$pay_order) {
            $this->error('订单不存在', '', 10100);
        }

        //订单定时器手动触发
        if ($pay_order->status != 0) {
            $this->error('订单状态异常');
        }

        //判断同个套餐购买时间是否超过30秒
        $have = Predis::getInstance()->get('meal'.$pay_order['meal_id'].'_'.$pay_type.'_'.$user_id);
        if ($have) {
            $this->error('距离上次购买该参数不足30秒，30秒后可重新购买');
        }


        $params['sn'] = $pay_order['order_no'];
        $params['type'] = $pay_type;
        $params['subject'] = '套餐购买';
      //  $params['price'] = $pay_order['pay_amount'];
        $params['price'] = $amount;
        $params['notifyurl'] = absolutePath(url('api/pay/payyongxinNotify'));; //根据实际情况修改;
        $params['extends_param'] = $pay_order['id'];

//	return $this->success('下单成功', "https://time.is");

        //进行支付操作
        $PayLib = new PayLib();
        $pay_url = $PayLib->juhePay($params, $pay_type);

		$pay_url = true;
        if ($pay_url) {
        	dump("sss");
            //TODO CJ 注释 
            // return $this->success('下单成功', $pay_url);

            //调试用自动回调
            $res = $this->_payOk($order_id);
            if($res){
                $this->success('下单成功', $pay_url);
            }else{
                $this->error('蛋蛋是傻批');
            }
            
        } else {
        	
            $this->error($PayLib->_error);
        }
    }
    
    private function _payOk($oid) {
        /*
        {
            "appid":"yxea12521ca9ec6778",
            "channel":"1","extends_param":
            "1537905","price":"996.00",
            "id":"5140291937198862335",
            "orderid":"D302560818619320",
            "timestamp":"1583157922",
            "sign":"c355a6480f22bb0e88805516514010a3"
        }
        */
        $user_id = $this->auth->getUser()['id'];
        //查询订单
        $pay_order = PayOrderModel::where([
            'id' => $oid,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->find();
        $appid = ConfigModel::where('name', 'pay_appid')->value('value');
        $params_arr = [
            "appid" => $appid,
            "channel"=> rand(0,1),
            "extends_param" => $oid,
            "price" => $pay_order["pay_amount"],
            "id" => date('YmdHis').rand(100000,999999),//"5140291937198862335",
            "orderid" => $pay_order["order_no"],
            "timestamp"=> time(),
        ];

        $private_key = ConfigModel::where('name', 'pay_private_key')->value('value');

        $md5_str = $appid.$params_arr['channel'].$params_arr['extends_param'].
            $params_arr['price'].$params_arr['id'].
            $params_arr['orderid'].$params_arr['timestamp'].$private_key;

        $md5_str = md5($md5_str);
        $params_arr['sign'] = $md5_str;

        $PayLib = new PayLib();
        return $PayLib->notifyMeal($params_arr);

    }

    public function tongzhi(){
        $pay_order_id =input('pay_order_id');
        $pay_no = input('pay_no');
        $payment_type = input('payment_type');
        $Mealdeallib = (new Mealdeallib());
        $res = $Mealdeallib->completeMeal($pay_order_id, $pay_no, $payment_type);
        //$this->error('处理失败', $pay_order_id);
        if ($res) {
            $this->success('处理成功');
        } else {
            $this->error('处理失败', $pay_order_id);
        }
    }
    /*

    public function dj_tongzhi(){

        $res = MealLib::mealBuyDj(input('id'));
        var_dump(MealLib::$deamon_error);
        var_dump($res);
        exit;
    }
    */


}
