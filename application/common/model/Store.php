<?php

namespace app\common\model;

use think\Model;

/**
 * 会员模型
 */
class Store extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function user(){
        return $this->hasOne('User', 'id', 'user_id')->field('name');
    }


    //店铺头像
    public function getAvatarPathAttr($value, $data){

        return oosAbsolutePath($data['avatar']);

    }
    //店铺图
    public function getStoreImagePathAttr($value, $data){

        return oosAbsolutePath($data['store_image']);

    }
    //客服图
    public function getServiceImagePathAttr($value, $data){

        return oosAbsolutePath($data['service_image']);

    }

    //已下架商品数量
    public function getProductDownNumAttr($value, $data){
        return $data['product_num'] - $data['product_up_num'];
    }


    //创建时间
    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d', $data['create_time']);
    }


    public function getStoreArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);


        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, store_image, avatar, name, product_num, sales_num')->order('sort desc, id desc')->page($page, $row)->select());

        if (count($data)) {

            $data->append(['avatar_path', 'store_image_path']);
        }

        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }

}
