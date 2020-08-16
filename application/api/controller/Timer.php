<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Predis;
use app\common\model\Task as TaskModel;
use app\common\model\TaskApply as TaskApplyModel;
use app\common\model\MealBuy as MealBuyModel;

/**
 * 首页接口
 */
class Timer extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    //数据出错要执行该函数回复redis缓存
    public function huifuRedis(){
        $this->getPlatformTaskSet();
        $this->getWaitTaskSubmitList();
        $this->getWaitMealBuyDjZset();
        $this->success('返回成功');
    }


    //获取平台未结束任务id
    public function getPlatformTaskSet(){

        //不存在，已过期
        if (Predis::getInstance()->setNx('platform_task_set_lock', '1')){

            Predis::getInstance()->expire('platform_task_set_lock', 1);

            $keys = Predis::getInstance()->keys('platform_task_set_*');
            foreach( $keys as $key => $val){
                Predis::getInstance()->del($val);
            }

            //未完成id
            $tasks = TaskModel::where([
                'status' => 1,
                'is_complete' => 0,
                'is_delete' => 0
            ])->field('id, type, task_platform_id')->select();
            //$this->success('不要频繁请求',$tasks);

            $task_ids=[];
            for ($i=0; $i<count($tasks); $i++) {

                $task = $tasks[$i];

                $key = 'platform_task_set_t_p';
                if (!array_key_exists($key, $task_ids)) {
                    $task_ids[$key] = [];
                }
                $key1 = 'platform_task_set_t'.$task['type'].'_p';
                if (!array_key_exists($key1, $task_ids)) {
                    $task_ids[$key1] = [];
                }
                $key2 = 'platform_task_set_t_p'.$task['task_platform_id'];
                if (!array_key_exists($key2, $task_ids)) {
                    $task_ids[$key2] = [];
                }
                $key3 = 'platform_task_set_t'.$task['type'].'_p'.$task['task_platform_id'];
                if (!array_key_exists($key3, $task_ids)) {
                    $task_ids[$key3] = [];
                }

                array_push($task_ids[$key], $task['id']);
                array_push($task_ids[$key1], $task['id']);
                array_push($task_ids[$key2], $task['id']);
                array_push($task_ids[$key3], $task['id']);

            }

            foreach( $task_ids as $key => $val){
                Predis::getInstance()->sAdds($key, ...$val);
            }


        } else {
            $this->success('不要频繁请求');
        }


        //$this->success('返回成功');
        Predis::getInstance()->del('platform_task_set_lock');
        //$this->success('返回成功', $task_ids);
    }



    //获取待处理的任务申请
    public function getWaitTaskSubmitList(){
        Predis::getInstance()->del('task_apply_list');
        $task_apply_ids = TaskApplyModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->order('id desc')->column('id');

        Predis::getInstance()->lPushs('task_apply_list', ...$task_apply_ids);

        //$this->success('返回成功', count($task_apply_ids));
    }

    //获取套餐记录未发布且未冻结的id
    public function getWaitMealBuyDjZset(){
        Predis::getInstance()->del('meal_buy_dj_zset');
        $meal_buys = MealBuyModel::where([
            'status' => 0,
            'is_delete' => 0
        ])->field('id, create_time')->select();

        for ($i=0; $i<count($meal_buys); $i++) {
            $meal_buy = $meal_buys[$i];

            Predis::getInstance()->zAdd('meal_buy_dj_zset', $meal_buy['create_time'], $meal_buy['id']);

        }
    }

    public function qu(){
        $user_ids = TaskApplyModel::where('id','>', 17024347)->distinct('user_id');
        $this->success('返回成功', TaskApplyModel::getLastSql());
    }
}
