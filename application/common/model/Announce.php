<?php

namespace app\common\model;

use think\Model;
/**
 * 会员模型
 */
class Announce extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';




    public function getContentAttr($value, $data){

        return fullContent($data['content']);

    }

    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d H:i:s', $data['create_time']);
    }



    


    public function getAnnounceArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, title as name, content, create_time, create_time as create_time_str')->order('sort desc, id desc')->page($page, $row)->select());


        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }
}
