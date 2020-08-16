<?php

namespace app\common\library;

use app\common\model\PayOrder as PayOrderModel;
use app\common\model\User as UserModel;
use app\common\model\UserLevel as UserLevelModel;
use app\common\model\Task as TaskModel;
use app\common\model\MealBuy as MealBuyModel;
use app\common\model\Meal as MealModel;
use app\common\model\Config as ConfigModel;
use think\Db;
use think\Exception;
class Meal
{

    public $_error = '';
    public static $deamon_error = '';


    /**
        添加报名
     */
    public function buyMeal($user_id, $meal)
    {
        
        //判断次数

        $pay_order = [
            'order_type' => 1,
            'user_id' => $user_id,
            'meal_id' => $meal['id'],
            'order_no' => $this->makeOrderNo(),
            'pay_amount' => $meal['sell_amount']

        ];

        $pay_order = PayOrderModel::create($pay_order);

        if (!$pay_order) {
            $this->_error = '创建订单失败';
            return false;
        }


        return $pay_order;

    }


    //生成订单号
    public function makeOrderNo(){
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn =
            $yCode[intval(date('Y')) - 2017] .
            strtoupper(dechex(date('m'))) .
            date('d') .
            substr(time(), -5) .
            substr(microtime(), 2, 5) .
            sprintf('%02d', rand(0, 99));
        return $orderSn;
    }


    //发布任务
    public function saveTask($user_id, $param){
        $meal_buy_id = $param['id'];
        Db::startTrans();
        try {

            $meal_buy = MealBuyModel::where([
                'id' => $meal_buy_id,
                'user_id' => $user_id,
                'is_delete' => 0,
            ])->field('id, meal_id, status')->find();
            if (!$meal_buy) {
                Db::rollback();
                $this->_error = '该套餐购买记录不存在';
                return false;
            }

            if ($meal_buy->status != 0 && $meal_buy->status != 2 ) {
                $this->_error = '该套餐已使用';
                return false;
            }

            $meal = MealModel::where('id', $meal_buy['meal_id'])->field('task_user_level_id, task_category_id, task_reward_amount, task_apply_num, reward_user_level_id')->find();


            //创建任务
            $task_data['type'] = in_array(8, explode(',', $meal['task_user_level_id']))?2:1;
            $task_data['info_type'] = $param['info_type_id'];
            $task_data['task_category_id'] = $meal['task_category_id'];
            $task_data['task_platform_id'] = $param['task_platform_id'];
            $task_data['user_level_id'] = $meal['task_user_level_id'];
            $task_data['is_user'] = 1;
            $task_data['user_id'] = $user_id;
            $task_data['name'] = $param['name'];
            $task_data['describe'] = $param['describe'];
            $task_data['cover'] = $param['cover'];
            $task_data['reward_amount'] = $meal['task_reward_amount'];
            $task_data['video_name'] = $param['video_name'];
            $task_data['video_url'] = $param['video_url'];
            $task_data['max_apply_num'] = $meal['task_apply_num'];
            $task_data['end_time'] = time() + 2952000;
            $task_data['is_complete'] = 0;
            $task_data['apply_num'] = 0;
            $task_data['status'] = 1;

            //CK修改 如果是流量套餐就不改
            $maxMealIds = ConfigModel::where('name', 'max_meal_ids')->value('value');
            if(!empty($maxMealIds)){
                $maxMealIds = explode(',', $maxMealIds);
                if(in_array($meal_buy['meal_id'], $maxMealIds)){
                    $task_data['apply_num'] = $meal['task_apply_num'];
                    $task_data['status'] = 1;
                }
            }
            

            $task = TaskModel::create($task_data);


            //如果是最高等级且被隐藏了，需要变回去
            $user = UserModel::where('id', $user_id)->field('user_level_hidden, user_level_hidden_id, user_level_id')->find();
            /*if ($meal['reward_user_level_id'] != $user['user_level_id']) {
                $my_level_weight = UserLevelModel::where('id', $user['user_level_id'])->value('weight');
                $reward_level_weight = UserLevelModel::where('id', $meal['reward_user_level_id'])->value('weight');
                //如果奖励等级大于目前等级
                if ($reward_level_weight >= $my_level_weight) {
                    UserModel::where('id', $user_id)->update(['user_level_id' => $meal['reward_user_level_id']]);
                }

            }*/
            //重新进行等级奖励
            if ($meal['reward_user_level_id']) {
                $user_level_ids = explode(',', $user['user_level_hidden']);
                //奖励等级是否已经存在
                if (!in_array($meal['reward_user_level_id'] ,$user_level_ids)) {

                    $user_update_data = [];
                    //取出最高等级的weight值
                    $weight = UserLevelModel::whereIn('id', $user_level_ids)->max('weight');
                    //查找赠送等级的weight值
                    $user_level = UserLevelModel::where('id', $meal['reward_user_level_id'])->field('id, weight')->find();
                    //赠送等级笔记高
                    if ($user_level['weight'] >= $weight) {
                        $user_update_data['user_level_hidden_id'] = $meal['reward_user_level_id'];
                        $user_update_data['user_level_id'] = $meal['reward_user_level_id'];
                    }
                    $user_level_hidden = $user['user_level_hidden'].','.$meal['reward_user_level_id'];

                    //取出普通会员
                    $user_level_hidden_arr = explode(',', $user_level_hidden);
                    foreach ($user_level_hidden_arr as $key=>$val) {
                        if ($val==1)
                            unset($user_level_hidden_arr[$key]);
                    }

                    $user_update_data['user_level_hidden'] = implode(',', $user_level_hidden_arr);
                    UserModel::where('id', $user_id)->update($user_update_data);

                }
            }


            //更新购买记录
            $meal_buy_data['task_id'] = $task['id'];
            $meal_buy_data['status'] = 1;
            $meal_buy_data['use_time'] = time();

            $update_success = MealBuyModel::where([
                'id' => $meal_buy_id,
                'is_delete' => 0
            ])->whereIn('status', [0, 2])->update($meal_buy_data);
            //更新更改才进行奖励
            if ($update_success) {
                Db::commit();
            } else {
                $this->_error = '更新失败';
                Db::rollback();
                return false;
            }

            //增加任务集合
            //(new Task())->updatePlatformTaskSet(1, $task['id']);

            return $task;
        } catch (Exception $e) {
            $this->_error = $e->getMessage();
            Db::rollback();
            return false;
        }


    }

    //套餐未发布冻结
    public static function mealBuyDj($meal_buy_id){
        $meal_buy = MealBuyModel::where('id', $meal_buy_id)->field('user_id, reward_user_level_id, status, create_time')->find();

        if ($meal_buy['create_time'] > time()-259200) {
            self::$deamon_error = '未到冻结时间';
            return false;
        }

        if ($meal_buy['status'] != 0) {
            self::$deamon_error = '已处理';
            return true;
        }





        //判断以前是否奖励过该等级，如果奖励过，则不用处理
        $pass_id = MealBuyModel::where([
            'user_id' => $meal_buy['user_id'],
            'reward_user_level_id' => $meal_buy['reward_user_level_id'],
            'status' => 1
        ])->value('id');
        if (!$pass_id) {

            $user = UserModel::where('id', $meal_buy['user_id'])->field('id, user_level_hidden, user_level_hidden_id, user_level_id')->find();



            $user_level_hidden_arr = explode(',', $user['user_level_hidden']);

            if(is_array($user_level_hidden_arr)){
                foreach($user_level_hidden_arr as $k=>$v){
                    if($v == $meal_buy['reward_user_level_id']){
                        unset($user_level_hidden_arr[$k]);
                    }
                }
            }

            if (!count($user_level_hidden_arr)) {
                $user_level_hidden_arr[0] = 1;
            }

            $user_level_hidden = implode(',', $user_level_hidden_arr);

            $user_update_data['user_level_hidden'] = $user_level_hidden;

            if ($meal_buy['reward_user_level_id'] == $user['user_level_id']) {

                $max_user_level_id = UserLevelModel::whereIn('id', $user_level_hidden_arr)->order('weight desc')->value('id');

                $user_update_data['user_level_id'] = $max_user_level_id;

            }

            UserModel::where('id', $meal_buy['user_id'])->update($user_update_data);

        }

        MealBuyModel::where('id', $meal_buy_id)->update(['status' => 2]);

        return true;
    }

}
