<?php

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 */
class ProductCart extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function product(){
        return $this->hasOne('Product', 'id', 'product_id')->field('name,store_id');
    }

    public function productSpec(){
        return $this->hasOne('ProductSpec', 'id', 'product_spec_id')->field('name, cover, cover as cover_path, price');
    }



    //产品名称
    public function getProductNameAttr(){

        return $this->product['name'];

    }

    //产品名称
    public function getStoreIdAttr(){

        return $this->product['store_id'];

    }

    //产品名称
    public function getProductSpecNameAttr(){

        return $this->productSpec['name'];

    }

    //封面图
    public function getCoverPathAttr(){

        return $this->productSpec['cover_path'];

    }

    //产品价格
    public function getProductPriceAttr(){

        return $this->productSpec['price'];

    }




    public function getProductCartArrayAll($where = [])
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, product_id, product_spec_id, num')->order('id desc')->select());



        if (count($data)) {
            //'username', 'avatar_path',
            $data->visible(['id', 'product_id', 'num']) ->append(['cover_path', 'product_id', 'product_name', 'product_spec_name', 'product_price', 'store_id']);
        }



        return $data;
    }

}
