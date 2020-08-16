<?php

namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Store as StoreModel;
use app\common\model\Product as ProductModel;
use app\common\model\ProductOrder as ProductOrderModel;
use app\common\model\ProductSpec as ProductSpecModel;
use app\common\library\Product as ProductLib;
use think\Validate;

/**
 * 首页接口
 */
class Store extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }


    //获取店铺资料
    public function store(){
        $store = $this->getStore();

        $store = $store->visible(['id', 'name', 'store_brief', 'product_num', 'product_up_num', 'sales_money', 'sales_num', 'today_sales_money', 'today_sales_num', 'last_sales_update'])->append(['avatar_path','store_image_path', 'service_image_path', 'product_down_num'])->toArray();
        //判断今日数据是否更新
        $today = strtotime(date('Y-m-d'));
        if ($store['last_sales_update']<$today) {
            StoreModel::where([
                'id' => $store['id'],
                'last_sales_update' => $today
            ])->update([
                'today_sales_money' => 0,
                'today_sales_num' => 0,
                'last_sales_update' => $today
            ]);

            $store['today_sales_money'] = 0;
            $store['today_sales_num'] = 0;
        }

        //查看是否有代支付订单
        $store['have_wait_pay'] = ProductOrderModel::where([
            'store_id' => $store['id'],
            'status' => 0,
            'is_delete' => 0
        ])->value('id')?1:0;
        //查看是否有代发货订单
        $store['have_wait_delivery'] = ProductOrderModel::where([
            'store_id' => $store['id'],
            'status' => 1,
            'is_delete' => 0
        ])->value('id')?1:0;
        //查看是否有代收货订单
        $store['have_wait_complete'] = ProductOrderModel::where([
            'store_id' => $store['id'],
            'status' => 2,
            'is_delete' => 0
        ])->value('id')?1:0;

        $this->success('请求成功', $store);
    }


    //获取店铺资料
    public function editStore(){
        $store = $this->getStore();

        $store = $store->visible(['id', 'name', 'store_brief', 'avatar','store_image', 'service_image'])->append(['avatar_path','store_image_path', 'service_image_path', 'product_down_num'])->toArray();

        $this->success('请求成功', $store);
    }


    //修改店铺资料
    public function saveStore(){
        $store_id = $this->getStore('id')['id'];


        $name = $this->request->post('name');//店铺名称
        $avatar = $this->request->post('avatar');//店铺简介
        $store_brief = $this->request->post('store_brief');//店铺简介
        $store_image = $this->request->post('store_image');//店铺图片
        $service_image = $this->request->post('service_image');//客服二维码图
        if (!($name&&$avatar&&$store_brief&&$store_image&&$service_image)) {
            $this->error('缺少参数');
        }

        $res = StoreModel::where('id', $store_id)->update([
            'name' => $name,
            'avatar' => $avatar,
            'store_brief' => $store_brief,
            'store_image' => $store_image,
            'service_image' => $service_image,
            'update_time' => time()
        ]);

        if ($res) {
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }


    /**
     * 我的产品列表
     *
     */
    public function productList()
    {


        $store_id = $this->getStore('id')['id'];

        $category_id = $this->request->post('category_id');
        $status = $this->request->post('status');//状态
        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $product_where = [];
        //店铺id
        array_push($product_where, ['store_id', '=', $store_id]);

        //是否有分类id
        if ($category_id) {
            array_push($product_where, ['category_id', '=', $category_id]);
        }

        if (isset($status) && $status !== '' && $status != '-1') {
            array_push($product_where, ['status', '=', $status]);
        }
        //店铺列表
        $product_list = (new ProductModel())->getProductArray($product_where, $page, $row);

        $this->success('请求成功', $product_list);
    }


    //我的产品编辑
    public function editProduct(){
        $product_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];
        if (!$product_id ) {
            $this->error('缺少参数');
        }

        //查询产品
        $product = ProductModel::where([
            'id' => $product_id,
            'store_id' => $store_id,
            'is_delete' => 0
        ])->field('id, category_id, image, name, subhead, price, content, cover, category_id')->find();

        //是否存在产品
        if ($product) {
                $product->append(['image_arr', 'image_relative_arr', 'cover_path', 'spec']);
            $this->success('请求成功', $product);
        }else{
            $this->error('该产品不存在');
        }

    }

    //保存我的产品
    public function saveProduct(){
        $product_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];

        $category_id = $this->request->post('category_id');
        $image = $this->request->post('image');
        $cover = $this->request->post('cover');
        $name = $this->request->post('name');
        $subhead = $this->request->post('subhead');
        $content = $this->request->post('content');
        //$status = $this->request->post('status');
        $product_data['id'] = $product_id;
        $product_data['store_id'] = $store_id;
        $product_data['category_id'] = $category_id;
        $product_data['image'] = $image;
        $product_data['cover'] = $cover;
        $product_data['name'] = $name;
        if ($subhead) {
            $product_data['subhead'] = $subhead;
        }
        if ($content) {
            $product_data['content'] = $content;
        }

        $ProductLib = new ProductLib();
        //检测参数是否合法
        $res = $ProductLib->checkProductParams($product_data);
        if (!$res) {
            $this->error($ProductLib->_error);
        }
        //是否为更新
        if ($product_id) {
            $res = (new ProductModel())->save($product_data, ['id' => $product_id]);
        } else {
            //新增
            $product_data['status'] = 0;//默认下架
            $res = $ProductLib->createProduct($product_data);
        }


        if ($res) {
            $product_id = $product_id?:$res['id'];
            $this->success('保存成功', $product_id);
        } else {
            $this->error($ProductLib->_error);
        }

    }

    //上架商品
    public function changeProductStatus(){
        $store_id = $this->getStore('id')['id'];
        $product_id = $this->request->post('id');
        $status = $this->request->post('status')?:0;
        if (!($product_id&&($status == 0 || $status == 1))) {
            $this->error('缺少参数');
        }

        //上级商品需要判断是否有上架中的规格
        if ($status == 1) {
            $have = ProductSpecModel::where([
                'product_id' => $product_id,
                'status' => 1,
                'is_delete' => 0
            ])->value('id');
            //不存在规格，则商品变为下架
            if (!$have) {
                $this->error('请先添加规格才能上架商品');
            }
        }

        $ProductLib = new ProductLib();
        $res = $ProductLib->changeProductStatus($product_id, $store_id, $status);

        if ($res) {
            if ($status == 0) {
                $this->success('下架成功');
            } else {
                $this->success('上架成功');
            }
        } else {
            $this->error('操作失败', $ProductLib->_error);
        }




    }

    //删除商品
    public function delProduct(){
        $product_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];

        if (!$product_id) {
            $this->error('缺少参数');
        }

        $ProductLib = new ProductLib();
        $res = $ProductLib->delProduct($product_id, $store_id);

        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败', $ProductLib->_error);
        }
    }

    //规格列表
    public function productSpecList(){

        $product_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];
        //判断该产品是否存在该店铺种
        $product_id = ProductModel::where([
            'id' => $product_id,
            'store_id' => $store_id,
            'is_delete' => 0
        ])->value('id');
        if ($product_id) {
            //查找规格列表
            $product_spec_list = ProductSpecModel::where([
                'product_id' => $product_id,
                'is_delete' => 0
            ])->field('id, cover, cover as cover_path, name, price, stock, status, status as status_str')->select();

            $this->success('请求成功', $product_spec_list);
        } else {
            $this->error('该产品不存在');
        }
    }


    //编辑规格
    public function editProductSpec(){
        $product_sepc_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];
        //查找产品id
        $product_sepc = ProductSpecModel::where([
            'id' => $product_sepc_id,
            'is_delete' => 0
        ])->field('id, product_id')->find();
        if (!$product_sepc) {
            $this->error('该规格不存在');
        }

        //判断该产品是否存在该店铺种
        $product_id = ProductModel::where([
            'id' => $product_sepc['product_id'],
            'store_id' => $store_id,
            'is_delete' => 0
        ])->value('id');
        if ($product_id) {
            //查看规格
            $product_spec = ProductSpecModel::where([
                'id' => $product_sepc_id,
                'is_delete' => 0
            ])->field('id, cover, cover as cover_path, name, price, stock, status')->find();

            $this->success('获取成功', $product_spec);

        } else {
            $this->error('该商品不存在');
        }
    }

    //保存规格
    public function saveProductSpec(){
        $store_id = $this->getStore('id')['id'];
        $product_sepc_id = $this->request->post('id');
        $product_id = $this->request->post('product_id');
        $cover = $this->request->post('cover');
        $name = $this->request->post('name');
        $price = $this->request->post('price');
        $stock = $this->request->post('stock');
        $status = $this->request->post('status');

        if (!($cover&&$name)) {
            $this->error('缺少参数');
        }

        if (!(Validate::is($price, 'number') && $price>0 && Validate::is($stock, 'number') && $stock>0 && Validate::is($status, 'number') && in_array($status, [0,1]))) {
            $this->error('参数有误');
        }

        $product_sepc_data['cover'] = $cover;
        $product_sepc_data['name'] = $name;
        $product_sepc_data['price'] = $price;
        $product_sepc_data['stock'] = $stock;
        $product_sepc_data['status'] = $status;


        $ProductLib = new ProductLib();
        $res = $ProductLib->saveProductSpec($product_id, $product_sepc_id, $store_id, $product_sepc_data);

       if ($res) {

            $this->success('保存成功');
        } else {
            $this->error('保存失败', $ProductLib->_error);
        }


    }

    //规格删除
    public function delProductSpec(){
        $product_sepc_id = $this->request->post('id');
        $store_id = $this->getStore('id')['id'];
        //查找产品id
        $product_sepc = ProductSpecModel::where([
            'id' => $product_sepc_id,
            'is_delete' => 0
        ])->field('id, product_id, price')->find();
        if (!$product_sepc) {
            $this->error('该规格不存在');
        }

        //判断该产品是否存在该店铺种
        $product = ProductModel::where([
            'id' => $product_sepc['product_id'],
            'store_id' => $store_id,
            'is_delete' => 0
        ])->field('id, price')->find();
        if ($product) {

            $ProductLib = new ProductLib();
            $res = $ProductLib->delProductSpec($product, $product_sepc, $store_id);

            if ($res) {

                $this->success('删除成功');
            } else {
                $this->error('删除失败', $ProductLib->_error);
            }

        } else {
            $this->error('该商品不存在');
        }
    }


    /**
     * 产品订单列表
     *
     */
    public function orderList()
    {
        $store_id = $this->getStore('id')['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');


        //产品列表
        $product_order_where = [
            ['store_id', '=',  $store_id],
        ];
        //是否有订单状态条件
        if (isset($status) && $status !== '' && $status != '-1') {
            array_push($product_order_where, ['status', '=', $status]);
        }
        $append = ['user', 'productOrderDetail', 'create_time_str', 'status_str'];
        $product_list = (new ProductOrderModel())->getProductOrderArray($product_order_where, $page, $row, $append);

        $this->success('请求成功', $product_list);
    }

    //订单详情
    public function order(){

        $store_id = $this->getStore('id')['id'];

        $order_id = $this->request->post('id');
        if (!$order_id ) {
            $this->error('缺少参数');
        }

        //查询订单
        $product_order = ProductOrderModel::where([
            'id' => $order_id,
            'store_id' => $store_id,
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
        }*/

        $product_order->append(['user', 'order_detail', 'create_time_str', 'delivery_time_str', 'complete_time_str', 'status_str', 'express_company_name', 'pay_type_str'])->toArray();

        $this->success('请求成功', $product_order);


    }


    /**
     * 订单发货
     *
     */
    public function orderDeliver()
    {
        $store_id = $this->getStore('id')['id'];

        $order_id = $this->request->post('id');
        if (!$order_id ) {
            $this->error('缺少参数');
        }

        //查询订单
        $product_order = ProductOrderModel::where([
            'id' => $order_id,
            'store_id' => $store_id,
            'is_delete' => 0
        ])->count();

        if (!$product_order) {
            $this->error('订单不存在');
        }

        $res = ProductOrderModel::where([
            'id' => $order_id,
            'store_id' => $store_id,
            'status' => 1,
            'is_delete' => 0
        ])->update(['status' => 2, 'delivery_time' => time()]);

        if ($res) {
            $this->success('发货成功');
        } else {
            $this->error('发货失败');
        }

    }



    
}
