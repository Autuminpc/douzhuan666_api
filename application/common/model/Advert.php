<?php

namespace app\common\model;

use think\Model;
/**
 * 会员模型
 */
class Advert extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';




    //简章内通图
    public function getBannerImgPathAttr($value, $data){

        return oosAbsolutePath($data['banner_img']);

    }


    //文章详情
    public function getContentAttr($value){

        return fullContent($value);

    }

//创建时间
    public function getCreateTimeStrAttr($value, $data){

        return date('Y-m-d H:i:s', $data['create_time']);

    }



    public function getAdvertArray($where = [], $limit = 4)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, banner_img, type, content, url')->order('sort desc, id desc')->limit($limit)->select());



        if (count($data)) {
            //'username', 'avatar_path',
            $data->append(['banner_img_path']);
        }
        $data->toArray();


        return $data;
    }
}
