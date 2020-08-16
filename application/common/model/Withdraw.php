<?php

namespace app\common\model;

use think\Model;

class Withdraw extends Model
{


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];

    //创建时间
    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d H:i:s', $data['create_time']);
    }

    //订单状态
    public function getStatusStrAttr($value, $data){
        $arr = ['待审核', '待发货', '待收货', '审核通过', '审核不通过'];

        return $arr[$data['status']];
    }

    public function getWithdrawArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, amount, create_time, status, verify_mark')->page($page, $row)->order('id desc')->select());




        if (count($data)) {
            //'username', 'avatar_path',
            $data->append(['create_time_str', 'status_str'])->hidden(['create_time', 'status']);
        }
        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }

}
