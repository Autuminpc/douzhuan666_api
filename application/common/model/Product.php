<?php

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 */
class Product extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function store(){
        return $this->hasOne('Store', 'id', 'store_id')->field('id, avatar, avatar as avatar_path, service_image, service_image as service_image_path, name,  product_num, sales_num');
    }

    public function productSpec()
    {
        return $this->hasMany('ProductSpec', 'product_id')->where([
            'status' => 1,
            'is_delete' => 0
        ])->field('id, cover, cover as cover_path, name, price, stock');
    }

    public function getRewardUserLevelAttr($value, $data){
        return UserLevel::where('id', $data['reward_user_level_id'])->value('name');
    }

    public function getStoreAttr(){
        return $this->store;
    }

    public function getSpecAttr(){
        return $this->productSpec;
    }

    //封面图
    public function getCoverPathAttr($value, $data){

        return oosAbsolutePath($data['cover']);

    }

    //产品详情
    public function getContentAttr($value){

        return fullContent($value);

    }


    public function getImageRelativeArrAttr($value, $data){

        $image_arr = explode(',', $data['image']);


        return $image_arr;
    }
    public function getImageArrAttr($value, $data){

        $image_arr = explode(',', $data['image']);
        for ($i=0; $i<count($image_arr); $i++) {
            $image_arr[$i] = oosAbsolutePath($image_arr[$i]);
        }

        return $image_arr;
    }



    public function getProductArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->page($page, $row)->order('sort desc, id desc')->select());



        if (count($data)) {
            //'username', 'avatar_path',
            $data->visible(['id', 'name', 'subhead', 'price', 'status']) ->append(['cover_path']);
        }
        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }

}
