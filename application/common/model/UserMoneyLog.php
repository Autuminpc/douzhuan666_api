<?php

namespace app\common\model;

use think\Model;

/**
 * 会员模型
 */
class UserMoneyLog extends Model {

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    public function getTypeStrAttr($value, $data) {
        $param = [
            '1'  => '任务奖励',
            '2'  => '任务分佣',
            '3'  => '套餐提成',
            '4'  => '充值消费',
            '5'  => '后台充值',
            '6'  => '提现',
            '7'  => '提现驳回',
            '8'  => '商品购买',
            '9'  => '店铺收益',
            '10' => '其它',
            '11' => '注册赠送'
        ];
        return $param[$data['type']];
    }


    public function getCreateTimeStrAttr($value, $data) {
        return date('Y-m-d H:i', $data['create_time']);
    }

    public function getUserMoneyLogArray($where = [], $page = 1, $row = 10) {


        array_push($where, ['is_delete', '=', 0]);


        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, type, money, remark, create_time')->order('id desc')->page($page, $row)->select());


        if (count($data)) {
            $data->append(['type_str', 'create_time_str']);
        }


        $res['list']         = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }
}
