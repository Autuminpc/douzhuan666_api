<?php

namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\Store as StoreModel;
use app\common\model\Product as ProductModel;
use app\common\model\ProductCart as ProductCartModel;
use app\common\model\Category as CategoryModel;
use app\common\library\ProductOrder as ProductOrderLib;

/**
 * 首页接口
 */
class Product extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }
public function test(){
        $xx=new \think\db\Expression("`num`+1");
        ProductCartModel::where('id', 8)->where('`product_id` > `product_spec_id`')->update(['num'=>$xx]);
        $this->success(ProductCartModel::getLastSql());
}

    //店铺列表
    public function storeList()
    {


        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;

        //产品列表
        $store_where = [
            ['status', '=',  1],
            ['name', 'neq', ''],
            ['service_image', 'neq', '']
        ];



        $product_list = (new StoreModel())->getStoreArray($store_where, $page, $row);

        $this->success('请求成功', $product_list);
    }


    //获取积分列表
    public function categoryList(){
        $categorys = CategoryModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->field('id, name')->order('sort desc, id desc')->select();
        $this->success('请求成功', $categorys);

    }

    //获取店铺信息
    public function store(){
        $store_id = $this->request->post('store_id');
        $store = StoreModel::where('id', $store_id)->field('id, store_brief, store_image, avatar, service_image, name, product_num, sales_num, service_image')->find();

        if ($store) {

            $store->append(['avatar_path', 'store_image_path', 'service_image_path']);
            $this->success('获取成功', $store);
        } else {
            $this->error('店铺不存在');
        }
    }

    /**
     * 产品列表
     *
     */
    public function productList()
    {


        $category_id = $this->request->post('category_id');
        $store_id = $this->request->post('store_id');
        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
           
        //产品列表
        $product_where = [
            ['status', '=',  1],
        ];

        //是否有分类id
        if ($category_id) {
            array_push($product_where, ['category_id', '=', $category_id]);
        }

        //是否有店铺id
        if ($store_id) {
            array_push($product_where, ['store_id', '=', $store_id]);
        }

        $product_list = (new ProductModel())->getProductArray($product_where, $page, $row);

        $this->success('请求成功', $product_list);
    }

    //产品详情
    public function product(){

        $product_id = $this->request->post('id');
        if (!$product_id ) {
            $this->error('缺少参数');
        }

        //查询产品
        $product = ProductModel::where([
            'id' => $product_id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, store_id, image, name, subhead, price, content, cover')->find();

        //是否存在产品
        if ($product) {
            $product->append(['image_arr', 'cover_path', 'store', 'spec']);
            $this->success('请求成功', $product);
        }else{
            $this->error('该产品不存在');
        }

    }


    //购物车列表
    public function cartList(){
        $user_id = $this->auth->getUser()['id'];

        //$page = $this->request->post('page')?:1;
        //$row = $this->request->post('row')?:10;



        //产品列表
        $product_cart_where = [
            ['user_id', '=', $user_id],
            ['status', '=',  1],
        ];



        $product_cart_list = (new ProductCartModel())->getProductCartArrayAll($product_cart_where);
        $data = [];

        if ($product_cart_list) {
            $store_list = [];
            foreach ($product_cart_list as $key => $product_cart) {
                $store_id = $product_cart['store_id'];
                if (array_key_exists($store_id, $store_list)) {//已存在
                    array_push($store_list[$store_id]['product_list'], $product_cart);
                } else {//不存在
                    $store_list[$store_id] = [];
                    //店铺信息
                    $store_list[$store_id]['store'] = StoreModel::where('id', $product_cart['store_id'])->field('id, avatar, avatar as avatar_path, name')->find();
                    //店铺商品列表
                    $store_list[$store_id]['product_list'] = [];
                    array_push($store_list[$store_id]['product_list'], $product_cart);
                }
            }

            //转化为数组
            foreach ($store_list as $key => $val) {
                array_push($data, $val);
            }
        }

        $this->success('请求成功', $data);
    }

    //加入购物车
    public function addCart(){

        $user_id = $this->auth->getUser()['id'];

        $product_id = $this->request->post('id');
        $product_spec_id = $this->request->post('product_spec_id');
        $num = $this->request->post('num')?:1;
        if (!($product_id&&$product_spec_id&&$num) ) {
            $this->error('缺少参数');
        }

        $ProductOrderLib = new ProductOrderLib();
        //加入购物车
        $res = $ProductOrderLib->addCart($user_id, $product_id, $product_spec_id, $num);
        if ($res) {
            $this->success('加入购物车成功');
        } else {
            $this->error('加入购物车失败', $ProductOrderLib->_error);
        }

    }

    //变更购物车
    public function changeCart(){
        $user_id = $this->auth->getUser()['id'];

        $product_cart_id = $this->request->post('id');
        $num = $this->request->post('num');
        if (!($product_cart_id&&$num) ) {
            $this->error('缺少参数');
        }

        //查看购物车记录是否存在
        $product_cart = ProductCartModel::where([
            'id' => $product_cart_id,
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, product_id, product_spec_id')->find();
        if (!$product_cart) {
            $this->error('购物车记录不存在');
        }

        $ProductOrderLib = new ProductOrderLib();
        //加入购物车
        $res = $ProductOrderLib->changeCart($product_cart, $num);
        if ($res) {
            $this->success('修改购物车成功');
        } else {
            $this->error('修改购物车失败', $ProductOrderLib->_error);
        }
    }

    //删除购物车
    public function delCart(){

        $user_id = $this->auth->getUser()['id'];

        $cart_ids = $this->request->post('ids/a');

        $ProductCartModel = ProductCartModel::where([
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' => 0
        ]);
        //没有购物车id表示清空所有
        if ($cart_ids) {
            $ProductCartModel = $ProductCartModel->whereIn('id', $cart_ids);
        }

        $ProductCartModel->update([
            'is_delete' => 1,
            'delete_time' => time()
        ]);

        $this->success('删除成功');

    }

    //购物车总数量
    public function getAllCartNum(){
        $user_id = $this->auth->getUser()['id'];
        $num = ProductCartModel::where([
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' => 0
        ])->sum('num')?:0;
        $this->success('获取成功', $num);
    }


    
}
