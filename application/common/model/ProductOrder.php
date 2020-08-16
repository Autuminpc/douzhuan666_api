<?php

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 */
class ProductOrder extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function user(){
        return $this->hasOne('User', 'id', 'user_id')->field('id, avatar, avatar as avatar_path, username, nickname');
    }
    public function store(){
        return $this->hasOne('Store', 'id', 'store_id')->field('id, avatar, avatar as avatar_path, name, service_image, service_image as service_image_path');
    }

    public function productOrderDetail(){
        return $this->hasMany('ProductOrderDetail', 'product_order_id')->field('product_id, product_name, product_spec_cover, product_spec_cover as product_spec_cover_path, product_spec_name, price, num');
    }

    //店铺信息
    public function getStoreAttr(){
        return $this->store;
    }

    //子订单
    public function getOrderDetailAttr(){
        return $this->productOrderDetail;
    }

    public function expressCompany(){
        return $this->hasOne('ExpressCompany', 'id', 'express_company_id')->field('name');
    }

    //创建时间
    public function getCreateTimeStrAttr($value, $data){
        return $data['create_time']?date('Y-m-d H:i:s', $data['create_time']):'';
    }

    //发货时间
    public function getDeliveryTimeStrAttr($value, $data){
        return $data['delivery_time']?date('Y-m-d H:i:s', $data['delivery_time']):'';
    }

    //完成时间
    public function getCompleteTimeStrAttr($value, $data){
        return $data['complete_time']?date('Y-m-d H:i:s', $data['complete_time']):'';
    }

    //支付方式
    public function getPayTypeStrAttr($value, $data){
        if (!$data['pay_type']) {
            return '';
        }

        $arr = ['alipay'=>'支付宝', 'weixin'=>'微信', 'score'=>'余额支付'];

        return $arr[$data['pay_type']];
    }

    //订单状态
    public function getStatusStrAttr($value, $data){
        $arr = ['未支付', '待发货', '待收货', '已完成', '已取消'];

        return $arr[$data['status']];
    }

    //快递名称
    public function getExpressCompanyNameAttr($value, $data){
        return $this->expressCompany['name']?:'';
    }

    public function getProductOrderArray($where = [], $page = 1, $row = 10, $append=['store', 'productOrderDetail', 'create_time_str', 'status_str'])
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->page($page, $row)->order('id desc')->select());



        if (count($data)) {
            //'username', 'avatar_path',
            $data->visible(['id', 'store_id', 'sn', 'price', 'num', 'total_price', 'status']) ->append($append);
        }
        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }
}
