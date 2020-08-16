<?php

namespace app\common\model;

use think\Model;

class Meal extends Model
{


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];


    public function taskCategory(){
        return $this->hasOne('TaskCategory', 'id', 'task_category_id')->field('name');
    }

    public function userLevel(){
        return $this->hasOne('UserLevel', 'id', 'reward_user_level_id')->field('day_task_num');
    }

    public function getTypeStrAttr($value, $data){
        $str = in_array(8, explode(',', $data['task_user_level_id']))?'钻石': '普通';
        return $str;
    }

    public function getTaskCategoryNameAttr(){
        return $this->taskCategory['name'];
    }

    public function getDayTaskNumAttr(){
        return $this->userLevel['day_task_num'];
    }

    public function getMealArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, task_category_id, reward_user_level_id, name, sell_amount, task_reward_amount, task_apply_num')->order('sort desc, id asc')->page($page, $row)->select());


        if (count($data)) {
            $data->append(['task_category_name', 'day_task_num']);
        }


        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }
}
