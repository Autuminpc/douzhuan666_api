<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22 0022
 * Time: 12:04
 */

namespace app\common\library;


use app\common\model\Product as ProductModel;
use app\common\model\ProductSpec as ProductSpecModel;
use app\common\model\Category as CategoryModel;
use app\common\model\Store as StoreModel;
use app\common\model\StoreMeal as StoreMeal;
use think\Db;
use think\Exception;


class Product {

    public $_error = '';



    //判断参数
    public function checkProductParams($product_data){
        //是否为编辑
        if ($product_data['id']) {
            $product_id = ProductModel::where([
                'id' => $product_data['id'],
                'store_id' => $product_data['store_id'],
                'is_delete' => 0
            ])->value('id');

            if (!$product_id) {
                $this->_error ='商品不存在';
                return false;
            }

        }

        if (!($product_data['category_id'] && $product_data['image'] && $product_data['cover'] && $product_data['name'])) {
            $this->_error = '缺少参数';
            return false;
        }

        //分类是否存在
        $category_id = CategoryModel::where('id', $product_data['category_id'])->value('id');
        if (!$category_id) {
            $this->_error ='分类不存在';
            return false;
        }
/*
        //状态是否正常
        if (!in_array($product_data['status'], [0, 1])) {
            $this->_error ='参数异常';
            return false;
        }
*/
        return true;
    }


    //创建产品
    public function createProduct($product_data)
    {


        //查询能发布多少个产品
        $store_meal_id = StoreModel::where('id', $product_data['store_id'])->value('store_meal_id');
        $product_public_num = StoreMeal::where('id', $store_meal_id)->value('product_public_num');

        //现在已发布数量
        $now_product_public_num = ProductModel::where([
            'store_id' => $product_data['store_id'],
            'is_delete' => 0
        ])->count();

        //判断是否超出
        if ($now_product_public_num >= $product_public_num) {
            $this->_error = '您最多发布 '.$product_public_num.' 个产品';
            return false;
        }

        try{
            $product = ProductModel::create($product_data);//创建商品
            if ($product) {
                StoreModel::where('id', $product_data['store_id'])->setInc('product_num');//增加总发布数
            }
            Db::commit();
            return $product;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = '网络异常';
            return false;
        }

    }


    public function delProduct($product_id, $store_id){
        $product = ProductModel::where([
            'id' => $product_id,
            'store_id' => $store_id,
            'is_delete' => 0
        ])->field('id, status')->find();

        if (!$product) {
            $this->_error = '产品不存在';
            return false;
        }

        try{

            $res = ProductModel::where([
                'id' => $product_id,
                'store_id' => $store_id,
                'status' => $product['status'],
                'is_delete' => 0
            ])->update(['is_delete' => 1, 'delete_time' => time()]);
            //只有更新更改才处理
            if ($res) {
                $store = StoreModel::where('id', $store_id)->dec('product_num');
                //如果是上架商品，需要减少上架商品数量
                if ($product['status']) {
                    $store->dec('product_up_num');
                }
                $store->update();

            }
            Db::commit();
            return true;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }

    }


    public function changeProductStatus($product_id, $store_id, $status){
        $product = ProductModel::where([
            'id' => $product_id,
            'store_id' => $store_id,
            'is_delete' => 0
        ])->field('id, status')->find();

        if (!$product) {
            $this->_error = '产品不存在';
            return false;
        }

        try{

            //上下架产品
            $res = ProductModel::where([
                'id' => $product_id,
                'store_id' => $store_id,
                'is_delete' => 0
            ])->update(['status' => $status]);


            //只有更新更改才处理
            if ($res) {

                //如果是上架商品，需要增加上架商品数量
                if ($status) {
                    StoreModel::where('id', $store_id)->setInc('product_up_num');
                } else {
                    StoreModel::where('id', $store_id)->setDec('product_up_num');
                }

            }
            Db::commit();
            return true;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }

    }


    public function delProductSpec($product, $product_sepc, $store_id){

        try{

            $res = ProductSpecModel::where([
                'id' => $product_sepc['id'],
                'is_delete' => 0
            ])->update(['is_delete' => 1, 'delete_time' => time()]);

            //删除成功方可处理
            if ($res) {
                //判断该规格是否是该商品的最低价
                if ( $product['price'] == $product_sepc['price']) {
                    $min_price = ProductSpecModel::where([
                        'product_id' => $product['id'],
                        'status' => 1,
                        'is_delete' => 0
                    ])->min('price')?:0;

                    ProductModel::where([
                        'id' => $product['id'],
                    ])->update(['price' => $min_price]);
                }

                //商品是否还有上架规格
                $have = ProductSpecModel::where([
                    'product_id' => $product['id'],
                    'status' => 1,
                    'is_delete' => 0
                ])->value('id');
                //不存在规格
                if (!$have) {
                    //商品变为下架
                    $res = ProductModel::where([
                        'id' => $product['id'],
                    ])->update(['status' => 0]);
                    //下架成功需要修改上架总数
                    if ($res) {
                        StoreModel::where('id', $store_id)->setDec('product_up_num');
                    }
                }
            }

            Db::commit();
            return true;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }

    }


    public function saveProductSpec($product_id, $product_sepc_id, $store_id, $product_sepc_data){
        try{

            //是否为更新
            if ($product_sepc_id) {

                //查找产品id
                $product_sepc = ProductSpecModel::where([
                    'id' => $product_sepc_id,
                    'is_delete' => 0
                ])->field('id, product_id')->find();
                if (!$product_sepc) {
                    throw new Exception('该规格不存在');
                }

                $product_id = $product_sepc['product_id'];

                //判断该产品是否存在该店铺
                $product = ProductModel::where([
                    'id' => $product_sepc['product_id'],
                    'store_id' => $store_id,
                    'is_delete' => 0
                ])->field('id, price')->find();
                if ($product) {
                    $res = (new ProductSpecModel())->save($product_sepc_data, ['id' => $product_sepc_id]);
                } else {
                    throw new Exception('该商品不存在');
                }

            } else {
                //新增产品id必穿
                if (!$product_id) {
                    throw new Exception('缺少参数');
                }

                //判断该产品是否存在该店铺种
                $product = ProductModel::where([
                    'id' => $product_id,
                    'store_id' => $store_id,
                    'is_delete' => 0
                ])->field('id, price')->find();
                if ($product) {
                    //新增需要保存产品id到规格
                    $product_sepc_data['product_id'] = $product_id;
                    $res = $product_sepc = ProductSpecModel::create($product_sepc_data);
                } else {
                    throw new Exception('该商品不存在');
                }
            }

            if ($res) {
                //判断是否存在规格，没有规格下架产品
                $have = ProductSpecModel::where(['product_id' => $product_id, 'status' => 1, 'is_delete' => 0])->value('id');
                if (!$have) {
                    $res = ProductModel::where([
                        'id' => $product_id,
                        'store_id' => $store_id,
                        'is_delete' => 0
                    ])->update(['status' => 0]);
                    //下架上需要减少已发布数
                    if ($res) {
                        StoreModel::where('id', $store_id)->setDec('product_up_num');
                    }
                } else {
                    //更新最低价格
                    $min_price = ProductSpecModel::where([
                        'product_id' => $product_id,
                        'status' => 1,
                        'is_delete' => 0
                    ])->min('price');
                    ProductModel::where([
                        'id' => $product_id,
                    ])->update(['price' => $min_price]);
                }
                /*
                //如果是下架规格，需要判断商品是否还有上架的规格，没有需要把商品下架
                if ($product_sepc_data['status'] == 0) {
                    //商品是否还有上架规格
                    $have = ProductSpecModel::where([
                        'product_id' => $product_id,
                        'status' => 1,
                        'is_delete' => 0
                    ])->value('id');
                    //不存在规格，则商品变为下架
                    if (!$have) {
                        $res = ProductModel::where([
                            'id' => $product_id,
                            'store_id' => $store_id,
                            'status' => 1,
                            'is_delete' => 0
                        ])->update(['status' => 0]);
                        //下架成功，需要更新状态
                        if ($res) {
                            StoreModel::where('id', $store_id)->setDec('product_up_num');
                        }
                    }

                    //判断该规格是否是该商品的最低价
                    if ( $product['price'] == $product_sepc_data['price']) {
                        $min_price = ProductSpecModel::where([
                            'product_id' => $product_id,
                            'status' => 1,
                            'is_delete' => 0
                        ])->min('price')?:0;

                        ProductModel::where([
                            'id' => $product_id,
                        ])->update(['price' => $min_price]);
                    }
                }

                //更新产品的最低价格
                if ($product_sepc_data['status'] == 1 && ($product['price'] == 0 || $product['price']>=$product_sepc_data['price'])) {
                    ProductModel::where([
                        'id' => $product_id,
                        'store_id' => $store_id,
                        'is_delete' => 0
                    ])->update(['price' => $product_sepc_data['price']]);
                }
                */
            }

            Db::commit();
            return true;

        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }
    }

}