<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22 0022
 * Time: 12:04
 */

namespace app\common\library;


use app\common\model\Product as ProductModel;
use app\common\model\ProductOrderDetail;
use app\common\model\ProductSpec as ProductSpecModel;
use app\common\model\ProductCart as ProductCartModel;
use app\common\model\ProductOrder as ProductOrderModel;
use app\common\model\ProductOrderDetail as ProductOrderDetailModel;
use app\common\model\User as UserModel;
use app\common\model\Store as StoreModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\model\PayOrder as PayOrderModel;
use app\common\model\UserAddress as UserAddressModel;
use think\Db;
use think\Exception;


class ProductOrder {

    public $_error = '';
    public static $static_error = '';

    //要购买的商品信息数组
    public $buy_product_info = [];

    public function createOrder($params)
    {


        //判断地址是否存在
        $user_address = UserAddressModel::where([
            'id' => $params['user_address_id'],
            'user_id' => $params['user_id'],
            'is_delete' => 0
        ])->find();
        if (!$user_address) {
            $this->_error = '地址不存在';
            return false;
        }
        $user_address->append(['address']);


        Db::startTrans();
        try{
            //商品总数量和总价格
            //$total_num = 0;
            //$total_price = 0;

            //店铺订单数组
            $store_product_arr = $this->productByStore();

            //扣除商品库存
            foreach ($this->buy_product_info as $key => $product) {
                ProductSpecModel::where([
                    'id' => $product['product_spec_id'],
                    'status' => 1,
                    'is_delete' => 0
                ])->setDec('stock', $product['num']);

                /*
                //计算总数量跟总价格
                $store_id = $product['store_id'];
                //对同个店铺的订单进行分类，计算总数量跟总价格
                if (array_key_exists($store_id, $store_product_arr)) {//已存在
                    $store_product_arr[$store_id]['total_num'] += $product['num'];
                    $store_product_arr[$store_id]['total_price'] += $product['price'] * $product['num'];
                    array_push($store_product_arr[$store_id]['product_list'], $product);
                } else {
                    $store_product_arr[$store_id] = [];
                    $store_product_arr[$store_id]['product_list'] = [];
                    $store_product_arr[$store_id]['total_num'] = $product['num'];
                    $store_product_arr[$store_id]['total_price'] = $product['price'] * $product['num'];
                    array_push($store_product_arr[$store_id]['product_list'], $product);

                }*/

            }

            //如果是从购物车进行购买，需要变更购物车的状态
            if ($params['type'] == 'cart') {
                ProductCartModel::whereIn('id', $params['product_cart_ids'])->update([
                    'status' => 2
                ]);
            }

            //生成的多订单id数组
            $order_ids = [];

            //订单详情数组
            $product_order_detail_list = [];

            //循环创建多个店铺订单
            foreach ($store_product_arr as $key => $store_product) {
                //$store_product = $store_product_arr[$i];
                $sn = makeOrderNo();
                $order = new ProductOrderModel();
                $order->store_id = $key;
                $order->user_id = $params['user_id'];
                $order->num = $store_product['total_num'];
                $order->total_price = $store_product['total_price'];
                $order->consignee_name = $user_address['name'];
                $order->consignee_mobile = $user_address->mobile;
                $order->consignee_address = $user_address->address;
                $order->sn = $sn;
                $order->status = 0;
                //$order->remark = $params['remark'];
                $order->save();

                //保存多个订单的id
                array_push($order_ids, $order['id']);

                //生成订单详情
                foreach ($store_product['product_list'] as $key => $product) {
                    $product_order_detail_data = [
                        'product_order_id' => $order->id,
                        'product_id' => $product['product_id'],
                        'product_cover' => $product['product_cover'],
                        'product_name' => $product['product_name'],
                        'product_spec_id' => $product['product_spec_id'],
                        'product_spec_cover' => $product['product_spec_cover'],
                        'product_spec_name' => $product['product_spec_name'],
                        'price' => $product['price'],
                        'num' => $product['num'],
                        'total_price' => $product['price'] * $product['num'],
                    ];
                    array_push($product_order_detail_list, $product_order_detail_data);
                }
            }

            //生成订单详情
            (new ProductOrderDetailModel())->saveAll($product_order_detail_list);



            Db::commit();
            return $order_ids;
        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }

    }

    //根据店铺id对商品进行分类
    public function productByStore(){
        //店铺订单数组
        $store_product_arr = [];

        //扣除商品库存
        foreach ($this->buy_product_info as $key => $product) {

            //计算总数量跟总价格
            //$total_num += $product['num'];
            //$total_price += $product['price'] * $product['num'];
            $store_id = $product['store_id'];
            //对同个店铺的订单进行分类，计算总数量跟总价格
            if (array_key_exists($store_id, $store_product_arr)) {//已存在
                $store_product_arr[$store_id]['total_num'] += $product['num'];
                $store_product_arr[$store_id]['total_price'] += $product['price'] * $product['num'];
                array_push($store_product_arr[$store_id]['product_list'], $product);
            } else {
                $store_product_arr[$store_id] = [];
                $store_product_arr[$store_id]['product_list'] = [];
                $store_product_arr[$store_id]['total_num'] = $product['num'];
                $store_product_arr[$store_id]['total_price'] = $product['price'] * $product['num'];
                array_push($store_product_arr[$store_id]['product_list'], $product);

            }

        }

        return $store_product_arr;
    }

    //支付成功回调
    public function successOrder($pay_order_id_str, $pay_no, $payment_type = 'score'){


        Db::startTrans();
        try{
            $pay_time = time();

            $pay_order_ids = explode(',', $pay_order_id_str);
            //订单数组
            $product_order_arr = [];
            //全部订单总金额
            $total_price = 0;
            foreach ($pay_order_ids as $key => $pay_order_id) {

                //查询订单
                $product_order = ProductOrderModel::where([
                    'id' => $pay_order_id,
                    'is_delete' => 0
                ])->field('id, store_id, user_id, sn, total_price, num, status')->find();



                if (!$product_order) {
                    //throw new Exception('订单:ID'.$pay_order_id.'不存在');
                    throw new Exception('订单不存在');
                }

                if ($product_order->status != 0 && $product_order->status != 4) {
                    //throw new Exception('订单:ID'.$pay_order_id.'已支付');
                    throw new Exception('订单已支付');
                }

                //更新订单状态
                $res = ProductOrderModel::where([
                    'id' => $pay_order_id,
                    'is_delete' => 0
                ])->whereIn('status', [0,4])->update([
                    'pay_sn' => $pay_no,
                    'pay_time' => $pay_time,
                    'pay_type' => $payment_type,
                    'status' => 1
                ]);
                //更新失败则抛出异常
                if (!$res) {
                    //throw new Exception('订单:ID'.$pay_order_id.'状态更新异常');
                    throw new Exception('订单状态更新异常');
                }

                array_push($product_order_arr, $product_order);
                $total_price += $product_order['total_price'];
            }

            //用户id
            $user_id = $product_order['user_id'];

            //如果为余额支付
            if ($payment_type == 'score') {
                //获取当前余额
                $now_money = UserModel::where('id', $user_id)->value('money');

                //判断余额是否足够
                if ($now_money < $total_price) {
                    throw new Exception('余额不足');
                }
                //减少余额
                UserModel::where('id', $user_id)->setDec('money', $total_price);

                $after_money = UserModel::where('id', $user_id)->value('money');
                //增加订单消费支出记录
                $user_money_log_data = [
                    'type' => 8,
                    'user_id' => $user_id,
                    'money' => -$total_price,  //变动金额
                    'before' => $after_money + $total_price, //当前金额 - 变动金额 = 变动前金额
                    'after' => $after_money,                   //变动后金额
                    'remark' => '商品购买',
                    'no' => $pay_order_id_str
                ];
                UserMoneyLogModel::create($user_money_log_data);


            } else {
                //现金支付需要增加记录
                $pay_order_data =[
                    'order_type' => 2,
                    'user_id' => $user_id,
                    'meal_id' => '',
                    'order_no' => $pay_order_id_str,
                    'pay_no' => $pay_no,
                    'payment_type' => $payment_type,
                    'pay_amount' => $total_price,
                    'status' => 1,
                    'pay_time' => $pay_time
                ];
                $pay_order = PayOrderModel::create($pay_order_data);

                //获取当前余额
                $now_money = UserModel::where('id', $user_id)->value('money');
                //增加充值记录（因为为支付充值）
                $user_money_log_data[] = [
                    'type' => 4,
                    'user_id' => $user_id,
                    'money' => $total_price,  //变动金额
                    'before' => $now_money,
                    'after' => $now_money + $total_price,//变动后金额
                    'remark' => '订单充值入账',
                    'no' => $pay_order['id']
                ];
                //增加订单消费支出记录
                $user_money_log_data[] = [
                    'type' => 8,
                    'user_id' => $user_id,
                    'money' => -$total_price,  //变动金额
                    'before' => $now_money + $total_price,
                    'after' => $now_money,//变动后金额
                    'remark' => '商品购买',
                    'no' => $pay_order_id_str
                ];
                (new UserMoneyLogModel())->saveAll($user_money_log_data);

            }

            //更新店铺资料
            foreach ($product_order_arr as $key => $product_order) {


                //店铺加钱，自营店不需要
                //if ($product_order['store_id'] != 1) {

                //}

                //店铺销售额跟销售量
                StoreModel::where('id', $product_order['store_id'])->inc('sales_money', $product_order['total_price'])->inc('sales_num', $product_order['num'])->update();
                //判断今日销售数据是否已更新
                $store = StoreModel::where('id', $product_order['store_id'])->field('user_id, today_sales_money, today_sales_num, last_sales_update')->find();
                //已更新，直接累加
                if (date('Y-m-d', $store['last_sales_update']) >= date('Y-m-d', $pay_time)) {
                    StoreModel::where('id', $product_order['store_id'])->inc('today_sales_money', $product_order['total_price'])->inc('today_sales_num', $product_order['num'])->inc('freeze_money', $product_order['total_price'])->update();
                } else {
                    //今日首次更新，直接赋值，并且把最后一次更新时间改为今天
                    StoreModel::where('id', $product_order['store_id'])->inc('freeze_money', $product_order['total_price'])->update(['today_sales_money' => $product_order['total_price'], 'today_sales_num' => $product_order['num'], 'last_sales_update' => strtotime(date('Y-m-d', $pay_time))]);
                }
                /*
                //增加商家余额
                UserModel::where('id', $store['user_id'])->setInc('money', $product_order['total_price']);

                $after_money = UserModel::where('id', $store['user_id'])->value('money');
                //增加店铺收入流水记录
                $user_money_log_data = [
                    'type' => 9,
                    'user_id' => $store['user_id'],
                    'money' => $product_order['total_price'],  //变动金额
                    'before' => $after_money - $product_order['total_price'], //当前金额 - 变动金额 = 变动前金额
                    'after' => $after_money,                   //变动后金额
                    'remark' => '出售商品',
                    'no' => $product_order['id']
                ];
                UserMoneyLogModel::create($user_money_log_data);*/
            }


            Db::commit();

            return true;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }

    }

    //确认收获
    public static function confirmOrder($product_order_id, $type=0, $user_id = 0){

        $product_order_where = [
            'id' => $product_order_id,
            'status' => 2,
            'is_delete' => 0
        ];

        //如果是前端用户取消，需要判断是否是取消者本人
        if ($type) {
            $product_order_where['user_id'] = $user_id;
        }

        $product_order= ProductOrderModel::where($product_order_where)->field('id, store_id, total_price,  pay_time')->find();
        //该订单已处理
        if (!$product_order) {
            return true;
        }

        Db::startTrans();
        try{


            //更新订单状态
            $res = ProductOrderModel::where([
                'id' => $product_order_id,
                'status' => 2,
                'is_delete' => 0
            ])->update(['status' => 3, 'complete_time' => time()]);

            //只有订单状态更新成功才进行提交
            if ($res) {
                if ($product_order['pay_time']>1582113692) {


                    //减少冻结金额
                    StoreModel::where('id', $product_order['store_id'])->setDec('freeze_money', $product_order['total_price']);
                    $store_user_id = StoreModel::where('id', $product_order['store_id'])->value('user_id');

                    //增加用户余额
                    UserModel::where('id', $store_user_id)->setInc('money', $product_order['total_price']);

                    $after_money = UserModel::where('id', $store_user_id)->value('money');
                    //增加店铺收入流水记录
                    $user_money_log_data = [
                        'type' => 9,
                        'user_id' => $store_user_id,
                        'money' => $product_order['total_price'],  //变动金额
                        'before' => $after_money - $product_order['total_price'], //当前金额 - 变动金额 = 变动前金额
                        'after' => $after_money,                   //变动后金额
                        'remark' => '出售商品',
                        'no' => $product_order['id']
                    ];
                    UserMoneyLogModel::create($user_money_log_data);
                }

                Db::commit();
                return true;
            }else {
                Db::rollback();
                return false;
            }


        }catch (\Exception $e){
            self::$static_error = $e->getMessage();
            Db::rollback();
            return false;

        }
    }

    //取消订单，需要还回库存
    public static function cancelOrder($product_order_id, $type=0, $user_id = 0){

        $product_order_where = [
            'id' => $product_order_id,
            'status' => 0,
            'is_delete' => 0
        ];

        //如果是前端用户取消，需要判断是否是取消者本人
        if ($type) {
            $product_order_where['user_id'] = $user_id;
        }

        $product_order= ProductOrderModel::where($product_order_where)->value('id');
        //该订单已处理
        if (!$product_order) {
            return true;
        }

        Db::startTrans();
        try{
            //查找所有子订单
            $product_order_details = ProductOrderDetailModel::where('product_order_id', $product_order_id)->field('product_spec_id, num')->select();
            //循环加回库存
            foreach ($product_order_details as $key => $product_order_detail) {
                ProductSpecModel::where('id', $product_order_detail['product_spec_id'])->setInc('stock', $product_order_detail['num']);
            }

            //更新订单状态
            $res = ProductOrderModel::where([
                'id' => $product_order_id,
                'status' => 0,
                'is_delete' => 0
            ])->update(['status' => 4]);

            //只有订单状态更新成功才进行提交
            if ($res) {
                Db::commit();
                return true;
            }else {
                Db::rollback();
                return false;
            }


        }catch (\Exception $e){
            self::$static_error = $e->getMessage();
            Db::rollback();
            return false;

        }
    }

    //加入购物车
    public function addCart($user_id, $product_id, $product_spec_id, $num){

        //判断是否已经加入过该商品在购物车
        $product_cart = ProductCartModel::where([
            'user_id' => $user_id,
            'product_id' => $product_id,
            'product_spec_id' => $product_spec_id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, num')->find();

        //计算加购物车总数量，先判断是否足够，如果没有增加过购物车，无需增加购物车已有数量
        if ($product_cart) {
            $num = $product_cart['num'] + $num;
        } else {
            //需要判断购物车是否超过20个商品
            $now_cart_product_num = ProductCartModel::where([
                'user_id' => $user_id,
                'status' => 1,
                'is_delete' => 0
            ])->count();
            if ($now_cart_product_num >= 20) {
                $this->_error = '购物车最多只能添加20种商品';
                return false;
            }
        }

        $res = $this->canBuy($product_id, $product_spec_id, $num);
        if (!$res) {
            return false;
        }

        //加入购物车
        //已存在，直接更改数量
        if ($product_cart) {
            $res = ProductCartModel::where('id', $product_cart['id'])->update([
                'num' => $num
            ]);
        } else {
            //店铺id，
            //$store_id = ProductModel::where('id', $product_id)->value('store_id');
            //创建购物车记录
            $res = ProductCartModel::create([
                'user_id' => $user_id,
                //'store_id' => $store_id,
                'product_id' => $product_id,
                'product_spec_id' => $product_spec_id,
                'num' => $num,
            ]);
        }

        if ($res) {
            return true;
        } else {
            $this->_error = '添加失败';
            return false;
        }

    }


    //加入购物车
    public function changeCart($product_cart, $num){


        $res = $this->canBuy($product_cart['product_id'], $product_cart['product_spec_id'], $num);
        if (!$res) {
            return false;
        }

        //加入购物车
        $res = ProductCartModel::where('id', $product_cart['id'])->update([
            'num' => $num
        ]);

        if ($res) {
            return true;
        } else {
            $this->_error = '更新失败';
            return false;
        }

    }


    //判断购物车是否有效
    public function validCart($product_cart_ids, $user_id){
        for ($i=0; $i<count($product_cart_ids); $i++) {
            $product_cart_id = $product_cart_ids[$i];
            $product_cart = ProductCartModel::where([
                'id' => $product_cart_id,
                'user_id' => $user_id,
                'status' => 1,
                'is_delete' => 0
            ])->field('product_id, product_spec_id, num')->find();
            if ($product_cart) {

                $res = $this->canBuy($product_cart['product_id'], $product_cart['product_spec_id'], $product_cart['num']);

                if (!$res) {

                    return false;
                }

            } else {
                $this->_error = '购物车异常';
                return false;
            }
        }

        return true;
    }


    //判断商品和规格是否存在，库存是否足够
    public function canBuy($product_id, $product_spec_id, $num){

        $product = ProductModel::where([
            'id' => $product_id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, store_id, name, cover, cover as cover_path')->find();

        if (!$product) {
            $this->_error = '该产品不存在';
            return false;
        }

        $product_spec = ProductSpecModel::where([
            'id' => $product_spec_id,
            'product_id' => $product_id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, name, cover, cover as cover_path, price, stock')->find();

        if (!$product_spec) {
            $this->_error = '该规格不存在';
            return false;
        } else {
            if ($product_spec['stock'] < $num) {
                $this->_error = $product['name'].'('.$product_spec['name'].')仅剩'.$product_spec['stock'];
                return false;
            }
        }


        //将商品的购买信息存入类的变量，以便重复利用
        $product_data = [
            'store_id' => $product['store_id'],
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'product_cover' => $product['cover'],
            'product_cover_path' => $product['cover_path'],
            'product_spec_id' => $product_spec_id,
            'product_spec_name' => $product_spec['name'],
            'product_spec_cover' => $product_spec['cover'],
            'product_spec_cover_path' => $product_spec['cover_path'],
            'price' => $product_spec['price'],
            'num' => $num,
        ];

        array_push($this->buy_product_info, $product_data);

        return true;
    }






}