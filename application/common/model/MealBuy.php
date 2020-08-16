<?php

namespace app\common\model;

use think\Model;

class MealBuy extends Model
{


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];

    public function meal(){
        return $this->hasOne('Meal', 'id', 'meal_id')->field('name, task_category_id, task_category_id as task_category_name, reward_user_level_id, reward_user_level_id as day_task_num, task_apply_num, task_reward_amount');
    }


    public function getNameAttr(){
        return $this->meal['name'];
    }
    public function getTaskCategoryNameAttr(){
        return $this->meal['task_category_name'];
    }

    public function getDayTaskNumAttr(){
        return $this->meal['day_task_num'];
    }
    public function getTaskApplyNumAttr(){
        return $this->meal['task_apply_num'];
    }
    public function getTaskRewardAmountAttr(){
        return $this->meal['task_reward_amount'];
    }



    public function getStatusStrAttr($value, $data){
        $param = ['0' => '未使用', '1' => '已使用', '2' => '未使用'];
        return $param[$data['status']];
    }

    public function getUseTimeStrAttr($value, $data){
        if  ($data['use_time']) {
            return date('Y-m-d H:i', $data['use_time']);
        }
        return '';
    }

    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d H:i', $data['create_time']);
    }

    public function getMealBuyArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, meal_id, status, create_time, use_time')->order('id desc')->page($page, $row)->select());


        if (count($data)) {
            $data->append(['name', 'task_category_name', 'task_apply_num', 'task_reward_amount', 'day_task_num', 'status_str', 'create_time_str', 'use_time_str']);
        }


        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }


}
