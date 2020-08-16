<?php

namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Store as StoreModel;
use app\common\model\Product as ProductModel;
use app\common\model\ProductOrder as ProductOrderModel;
use app\common\model\ProductCart as ProductCartModel;
use app\common\model\UserAddress as UserAddressModel;
use app\common\library\ProductOrder as ProductOrderLib;
use app\common\library\Pay as PayLib;
use app\common\library\Lock as LockLib;
use think\Validate;

/**
 * 首页接口
 */
class ProductOrder extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }


    //购买预览，在商品详情页立即购买type为product，购物车为cart
    public function preBuy(){
        $user = $this->auth->getUser();

        $user_id = $user['id'];

        //类型
        $type = $this->request->post('type')?:'product';

        //在商品详情页立即购买
        if ($type == 'product') {
            $product_id = $this->request->post('id');//上商品id
            $product_spec_id = $this->request->post('product_spec_id');//商品规格
            $num = $this->request->post('num')?:1;//商品数量

            if (!($product_id && $product_spec_id && $num)) {
                $this->error('缺少参数');
            }

            //实例化订单类
            $ProductOrderLib = new ProductOrderLib();
            //判断商品数量是否足够
            $res = $ProductOrderLib->canBuy($product_id, $product_spec_id, $num);


        } else {//在购物车界面购买
            //购物车id数组
            $product_cart_ids = $this->request->post('product_cart_ids/a')?:[8,5,7];

            if (!($product_cart_ids)) {
                $this->error('缺少参数');
            } else {
                //$product_cart_ids = [2,3,4];
                if (!is_array($product_cart_ids)) {
                    $this->error('参数有误');
                }
            }

            //实例化订单类
            $ProductOrderLib = new ProductOrderLib();
            //判断购物车是否存在且数量是否足够
            $res = $ProductOrderLib->validCart($product_cart_ids, $user_id);
        }

        if (!$res) {
            $this->error($ProductOrderLib->_error);
        }


        //获取店铺商品列表
        $store_product_arr = $ProductOrderLib->productByStore();
        //总数量跟价格
        $total_num = 0;
        $total_price = 0;

        foreach ($store_product_arr as $key=>$store_product) {
            //查询店铺信息
            $store_product_arr[$key]['store'] = StoreModel::where('id', $key)->field('id, avatar, avatar as avatar_path, name')->find();

            //总数量跟价格
            $total_num += $store_product['total_num'];
            $total_price += $store_product['total_price'];
        }

        //获取默认地址，不存在返回空
        $user_address = UserAddressModel::where([
            'user_id' => $user_id,
            'is_default' => 1,
            'is_delete' => 0
        ])->find();

        if ($user_address) {
            $user_address->visible(['id', 'name', 'mobile', 'detail'])->append(['short_address'])->toArray();
        } else {
            $user_address = [];
        }



        $data['product'] = $store_product_arr;
        $data['total_num'] = $total_num;
        $data['total_price'] = $total_price;
        $data['user_address'] = $user_address;

        $this->success('请求成功', $data);

    }



    //生成订单
    public function createOrder(){

        $user_id = $this->auth->getUser()['id'];

        //类型
        $type = $this->request->post('type');

        //在商品详情页立即购买
        if ($type == 'product') {
            $product_id = $this->request->post('id');//上商品id
            $product_spec_id = $this->request->post('product_spec_id');//商品规格
            $num = $this->request->post('num')?:1;//商品数量


            if (!($product_id && $product_spec_id)) {
                $this->error('缺少参数');
            }

            if (!(Validate::is($num, 'number') && $num>0)) {
                $this->error('参数有误');
            }

            //实例化订单类
            $ProductOrderLib = new ProductOrderLib();
            //判断商品数量是否足够
            $res = $ProductOrderLib->canBuy($product_id, $product_spec_id, $num);


        } elseif ($type == 'cart') {//在购物车界面购买
            //购物车id数组
            $product_cart_ids = $this->request->post('product_cart_ids/a')?:[8,7];

            //拼接参数数组
            $params['product_cart_ids'] = $product_cart_ids;

            if (!($product_cart_ids)) {
                $this->error('缺少参数');
            } else {

                if (!is_array($product_cart_ids)) {
                    $this->error('参数有误');
                }
            }

            //实例化订单类
            $ProductOrderLib = new ProductOrderLib();
            //判断购物车是否存在且数量是否足够
            $res = $ProductOrderLib->validCart($product_cart_ids, $user_id);
            //$this->error('11', $ProductOrderLib->buy_product_info);
            if (!$res) {
                $this->error($ProductOrderLib->_error);
            }

        } else {
            $this->error('参数有误');
        }


        $user_address_id =$this->request->post('user_address_id');
        $remark =$this->request->post('remark');

        if (!($user_address_id)) {
            $this->error('请选择地址');
        }



        $params['type'] = $type;//如果type是cart，是从购物车进行下单，需要把购物车的状态改变
        $params['user_id'] = $user_id;
        $params['user_address_id'] = $user_address_id;
        $params['remark'] = $remark;


        //加锁
        if (LockLib::redisLock('u_'.$user_id, 10)) {

            $res = $ProductOrderLib->createOrder($params);
        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('u_'.$user_id);

        if ($res) {
            $this->success('下单成功', $res);
        } else {
            $this->error('下单失败', $ProductOrderLib->_error);
        }
    }


    //支付订单
    public function payOrder(){

        $user = $this->auth->getUser();
        $user_id = $user['id'];

        $order_ids = $this->request->post('ids/a')?:[23, 24, 25];
        $pay_type = $this->request->post('pay_type')?:2;


        if (!($order_ids && $pay_type)) {
            $this->error('缺少参数');
        }

        //订单总价
        $total_price = 0;

        //查询订单
        foreach ($order_ids as $key => $order_id) {
            $product_order = ProductOrderModel::where([
                'id' => $order_id,
                'user_id' => $user_id,
                'is_delete' => 0
            ])->field('id, total_price, status, sn, create_time')->find();

            if (!$product_order) {
                $this->error('订单不存在', '', 10100);
            }


            if ($product_order->status == 4) {
                $this->error('订单已取消');
            }

            if ($product_order->status != 0) {
                $this->error('订单已支付');
            }
/*
            //订单定时器手动触发
            if ($product_order->status == 0 && $product_order->create_time < time()-3600) {

                ProductOrderLib::cancelOrder($order_id, 1, $user_id);

                $this->error('订单已取消');
            }*/
            $total_price += $product_order['total_price'];
        }

        //判断是一个订单还是多个订单
        if ( count($order_ids) == 1 ) {
            $sn = $product_order['sn'];
        } else {
            $sn = implode('p', $order_ids);
        }

        if ($pay_type == 'score') {
            //先判断余额是否足够再进入订单
            if ($user['money'] < $total_price) {
                $this->error('余额不足', $user['money'] .'-'. $total_price);
            }

            $ProductOrderLib = (new ProductOrderLib());
            $res = $ProductOrderLib->successOrder(implode(',', $order_ids), 'score'.time(), $pay_type);

            if ($res) {
                $this->success('购买成功');
            } else {
                $this->error($ProductOrderLib->_error);
            }

        } else {

            //进行支付操作
            $params['sn'] = $sn;
            $params['type'] = $pay_type;
            $params['subject'] = '商品购买';
            $params['price'] = $total_price;
            $params['notifyurl'] = absolutePath(url('api/pay/payyxNotifyOrder'));; //根据实际情况修改;
            $params['extends_param'] = implode(',', $order_ids);

            //进行支付操作
            $PayLib = new PayLib();
            $pay_url = $PayLib->pay($params, $pay_type);

            if ($pay_url) {
                //$this->tongzhi($pay_order['id'], $pay_type);
                $this->success('下单成功', $pay_url);
            } else {
                $this->error('支付异常', $PayLib->_error);
            }
        }
    }

    //查询订单是否支付成功
    public function payStatus(){
        $order_id = $this->request->post('id');

        $status = ProductOrderModel::where('id', $order_id)->value('status');

        if ($status == 1) {
            $this->success('支付成功', 1);
        } else {
            $this->success('支付失败', 0);
        }
    }


    //取消订单
    public function cancelOrder(){
        $user_id = $this->auth->getUser()['id'];

        $product_order_id =$this->request->post('id');
        if (!$product_order_id) {
            $this->error('缺少参数');
        }

        //第一个参数是订单id，第二个参数表示需要验证为用户本人取消订单，第三个是用户id
        $res = ProductOrderLib::cancelOrder($product_order_id, 1, $user_id);

        if ($res) {
            $this->success('取消成功');
        } else {
            $this->error('订单异常', ProductOrderLib::$static_error);
        }

    }

    //确定收货，完成
    public function confirmOrder(){
        $user_id = $this->auth->getUser()['id'];

        $product_order_id =$this->request->post('id');
        if (!$product_order_id) {
            $this->error('缺少参数');
        }

        //第一个参数是订单id，第二个参数表示需要验证为用户本人取消订单，第三个是用户id
        $res = ProductOrderLib::confirmOrder($product_order_id, 1, $user_id);

        if ($res) {
            $this->success('确认成功');
        } else {
            $this->error('订单异常', ProductOrderLib::$static_error);
        }

    }

    /**
     * 产品订单列表
     *
     */
    public function orderList()
    {

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');


        //产品列表
        $product_order_where = [
            ['user_id', '=',  $user_id],
        ];
        //是否有订单状态条件
        if (isset($status) && $status !== '' && $status != '-1') {
            array_push($product_order_where, ['status', '=', $status]);
        }

        $product_list = (new ProductOrderModel())->getProductOrderArray($product_order_where, $page, $row);

        $this->success('请求成功', $product_list);
    }

    //订单详情
    public function order(){


        $user_id = $this->auth->getUser()['id'];


        $order_id = $this->request->post('id');
        if (!$order_id ) {
            $this->error('缺少参数');
        }

        //查询订单
        $product_order = ProductOrderModel::where([
            'id' => $order_id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->find();

        if (!$product_order) {
            $this->error('订单不存在');
        }
/*
        //订单定时器手动触发
        if ($product_order->status == 2 && $product_order->create_time < time()-864000) {
            $product_order->status = 3;
            $product_order->save();
        }
*/
        $product_order->append(['store', 'order_detail', 'create_time_str', 'delivery_time_str', 'complete_time_str', 'status_str', 'express_company_name', 'pay_type_str'])->toArray();

        $this->success('请求成功', $product_order);


    }


    //获取用户中心订单
    public function userCenterLight(){
        $user_id = $this->auth->getUser()['id'];
        //查看是否有代支付订单
        $user['have_wait_pay'] = ProductOrderModel::where([
            'user_id' => $user_id,
            'status' => 0,
            'is_delete' => 0
        ])->value('id')?1:0;
        //查看是否有代发货订单
        $user['have_wait_delivery'] = ProductOrderModel::where([
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' => 0
        ])->value('id')?1:0;
        //查看是否有代收货订单
        $user['have_wait_complete'] = ProductOrderModel::where([
            'user_id' => $user_id,
            'status' => 2,
            'is_delete' => 0
        ])->value('id')?1:0;

        $this->success('请求成功', $user);
    }
    
}
