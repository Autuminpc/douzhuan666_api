<?php

namespace app\common\library;


use app\common\model\Task as TaskModel;
use app\common\model\TaskApply as TaskApplyModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\model\UserLevel as UserLevelModel;
use app\common\model\Config as ConfigModel;
use think\Db;
use think\Exception;

class Task
{
    
    public $_error = '';

    public $open_redis_cache;//是否开启缓存

    public function __construct()
    {
        $this->open_redis_cache =0;
    }

    //获取平台未完成任务列表 method0不获取。1获取一定数量，2全部获取
    public function getPlatformTaskSet($type = '', $platform = '', $method=0, $num = 10){
        $key = 'platform_task_set_t'.$type.'_p'.$platform;
        //是否开启缓存
        //不存在，已过期
        if (!$this->open_redis_cache) {
            $where = [
                'status' => 1,
                'is_complete' => 0
            ];

            if ($type) {
                $where['type'] = $type;
            }
            if ($platform) {
                $where['task_platform_id'] = $platform;
            }
            //获取平台任务
            $task_ids = TaskModel::where($where)->column('id');

            return $task_ids;
        } else {
            if (!Predis::getInstance()->exists($key)) {
                $where = [
                    'status' => 1,
                    'is_complete' => 0
                ];

                if ($type) {
                    $where['type'] = $type;
                }
                if ($platform) {
                    $where['task_platform_id'] = $platform;
                }

                //已接任务id
                $task_ids = TaskModel::where($where)->column('id');


                Predis::getInstance()->sadds($key, ...$task_ids);
            }

            if ($method == 1) {
                return Predis::getInstance()->sRandMember($key, $num);
            }

            if ($method == 2) {
                return Predis::getInstance()->sMembers($key);
            }

        }

        return '';
    }

    //更新获取平台未完成任务列表,type1是增加，0是删除
    public function updatePlatformTaskSet($type = '1', $task_id = '')
    {

        if ($this->open_redis_cache && $task_id) {


            //$res = 0;

            //查看任务类型
            $task = TaskModel::where([
                'id' => $task_id,
                'is_delete' => 0
            ])->field('type, task_platform_id')->find();

            $key = 'platform_task_set_t_p';
            $key1 = 'platform_task_set_t'.$task['type'].'_p';
            $key2 = 'platform_task_set_t_p'.$task['task_platform_id'];
            $key3 = 'platform_task_set_t'.$task['type'].'_p'.$task['task_platform_id'];
            $this->getPlatformTaskSet();
            $this->getPlatformTaskSet('', $task['task_platform_id']);
            $this->getPlatformTaskSet($task['type']);
            $this->getPlatformTaskSet($task['type'], $task['task_platform_id']);
            //不存在，已过期
            //if (Predis::getInstance()->setNx('platform_task_set_lock', '1')) {
            //    Predis::getInstance()->expire('platform_task_set_lock', 100);
            if ($type) {
                Predis::getInstance()->sadd($key, $task_id);
                Predis::getInstance()->sadd($key1, $task_id);
                Predis::getInstance()->sadd($key2, $task_id);
                Predis::getInstance()->sadd($key3, $task_id);

            } else {
                Predis::getInstance()->sRem($key, $task_id);
                Predis::getInstance()->sRem($key1, $task_id);
                Predis::getInstance()->sRem($key2, $task_id);
                Predis::getInstance()->sRem($key3, $task_id);

            }
            //}


            //Predis::getInstance()->del('platform_task_set_lock');

            //if ($res) {

            return true;
            //}

            //return false;
        }


    }


    //获取个人已完成订单数量
    public function getMyTaskSet($user_id, $type=0){
        //是否开启缓存
        //不存在，已过期
        if (!$this->open_redis_cache) {
            //已接任务id
            $task_ids = TaskApplyModel::where([
                'user_id' => $user_id,
                'is_delete' => 0
            ])->column('task_id');

            return $task_ids;
        } else {
            if (!Predis::getInstance()->exists('task'.$user_id.'_set')) {
                //已接任务id
                $task_ids = TaskApplyModel::where([
                    'user_id' => $user_id,
                    'is_delete' => 0
                ])->column('task_id');


                Predis::getInstance()->sadds('task'.$user_id.'_set', ...$task_ids);
            }

            if ($type) {
                return Predis::getInstance()->sMembers('task'.$user_id.'_set');
            }

        }

        return '';
    }

    //更新我的任务列表
    public function updateMyTaskSet($user_id, $task_id = '', $type = 1){

        if ($this->open_redis_cache) {
            $this->getMyTaskSet($user_id);
            //不存在，已过期
            if ($type) {
                Predis::getInstance()->sadd('task'.$user_id.'_set', $task_id);

            } else {
                Predis::getInstance()->sRem('task'.$user_id.'_set', $task_id);

            }
        }
        return true;
    }

    public function clearCache(){
        Predis::getInstance()->flushDB();
    }

    //获取可接任务数量
    public function getCanApplyTaskIds($user_id){

        //如果开启缓存
        if ($this->open_redis_cache) {
            $this->getMyTaskSet($user_id);
            $this->getPlatformTaskSet();

            $wait_task_ids = Predis::getInstance()->sDiff('platform_task_set', 'task' . $user_id . '_set');
        } else { //未开启缓存

            $my_task_set = $this->getMyTaskSet($user_id);
            $platform_task_set = $this->getPlatformTaskSet();

            $wait_task_ids = array_diff($platform_task_set, $my_task_set);
        }

        return $wait_task_ids;
    }



    public function getTodayTaskDataByMysql1($user_id){
        $today_time = strtotime(date('Y-m-d'));
        $today_task_pt = 0;//普通任务书
        $today_task_jx = 0;//钻石任务数
        /*
        //今日新接任务id
        $today_apply_task_ids = TaskApplyModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->where('create_time', '>=', $today_time)->column('task_id');*/
        //今天提交才完成提交的过去任务id或者至今为提交的任务
        $pass_apply_task_ids = TaskApplyModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->where('(status = 0) or (submit_time >=' .$today_time. ')')->column('task_id');

        //$apply_task_ids = array_merge($today_apply_task_ids, $pass_apply_task_ids);
        $task_types = TaskModel::whereIn('id', $pass_apply_task_ids)->column('type');
        for ($i=0 ;$i<count($task_types); $i++) {
            $task_type = $task_types[$i];
            if ($task_type == 1) {
                $today_task_pt++;
            } else {
                $today_task_jx++;
            }
        }

        
        //今日奖励金额
        $income = UserMoneyLogModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->where('create_time', '>=', $today_time)->whereIn('type', [1, 2, 3])->sum('money');

        $task_user_data['today_task_pt'] = $today_task_pt;
        $task_user_data['today_task_jx'] = $today_task_jx;
        $task_user_data['today_income'] = $income;
        $task_user_data['update_time'] = date('Ymd');
		

        return $task_user_data;
    }






    //获取今日收益，任务数量
    public function getTodayTaskData($user_id, $field = '', $type=0){
        //没有开启缓存
        if (!($this->open_redis_cache)) {
        	// xxxxxxxxxxxxx 
        	$result = $this->getTodayTaskDataByMysql1($user_id);
        	if ($field){
        		return $result[$field];
        	}
        	return $result;
        	// xxxxxxxxxxxxxx
        	
            return $this->getTodayTaskDataByMysql1($user_id);
        }

        $today_time = date('Ymd');
        $redis = Predis::getInstance();

        //判断缓存时间是否已经刷新
        $update_time = $redis->hGet('taskdata_'.$user_id, 'update_time');
        //不存在或者未刷新
        if ( !($update_time && $update_time >= $today_time) ) {
            $update_data = [];
            //任务回调，不需要必须把未登录状态改变
            if ($type) {
                $update_data['login'] = 0;
            } else {
                //用户自己访问，需要改为已登陆
                $update_data['login'] = 1;
                //需要获取当天的任务数量
                $task_data = $this->getTodayTaskDataByMysql($user_id);

                $update_data['today_task_pt'] = $task_data['today_task_pt'];
                $update_data['today_task_jx'] = $task_data['today_task_jx'];

            }
            $update_data['today_income'] = 0;
            $update_data['update_time'] = $today_time;

            //更新缓存
            $redis->hMSet('taskdata_'.$user_id, $update_data);

        } else {
            $update_data = [];
            //如果缓存时间已刷新，且不是任务回调，可能是任务分佣奖励，只更新的收入，需要判断登陆状态
            if (!$type) {
                $login = $redis->hGet('taskdata_'.$user_id, 'login');
                //未登录，需要更新为已登陆，并且刷新任务数量
                if (!$login) {
                    //改为已登陆
                    $update_data['login'] = 1;
                    //需要获取当天的任务数量
                    $task_data = $this->getTodayTaskDataByMysql($user_id);

                    $update_data['today_task_pt'] = $task_data['today_task_pt'];
                    $update_data['today_task_jx'] = $task_data['today_task_jx'];

                }
                //更新缓存
                $redis->hMSet('taskdata_'.$user_id, $update_data);
            }
        }

        //获取相应字段
        if ($field) {
            $task_user_data = $redis->hGet('taskdata_'.$user_id, $field);
        } else {
            $task_user_data = $redis->hGetAll('taskdata_'.$user_id);
        }


        return $task_user_data;
    }

    public function getTodayTaskDataByMysql($user_id){
        $today_time = strtotime(date('Y-m-d'));
        $today_task_pt = 0;//普通任务书
        $today_task_jx = 0;//钻石任务数

        //今天提交才完成提交的过去任务id或者至今为提交的任务
        $pass_apply_task_ids = TaskApplyModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->where('(status = 0) or (submit_time >=' .$today_time. ')')->column('task_id');
        //$apply_task_ids = array_merge($today_apply_task_ids, $pass_apply_task_ids);
        $task_types = TaskModel::whereIn('id', $pass_apply_task_ids)->column('type');
        for ($i=0 ;$i<count($task_types); $i++) {
            $task_type = $task_types[$i];
            if ($task_type == 1) {
                $today_task_pt++;
            } else {
                $today_task_jx++;
            }
        }


        return ['today_task_pt' => $today_task_pt, 'today_task_jx' => $today_task_jx];
    }

    //更新今日收益，任务数量   type为1为任务回调，有些数据缓存不必更新，等用户自己登陆再更新
    public function updateTodayTaskData($user_id, $data, $type = 0){
        if ($this->open_redis_cache) {
            $redis = Predis::getInstance();

            //开启事物
            //$redis->multi();
            if (isset($data['money'])) {
                $field = 'today_income';
                $change = $data['money'];
            } elseif (isset($data['is_pt_task'])) {
                $field = 'today_task_pt';
                $change = 1;
            } elseif(isset($data['is_jx_task'])) {
                $field = 'today_task_jx';
                $change = 1;
            }
            //获取今日收益
            $value = $this->getTodayTaskData($user_id, $field, $type);

            //今日收益
            $value += $change;

            $redis->hSet('taskdata_' . $user_id, $field, $value);

            //$redis->exec();

        }
        return true;
    }


    public function applyTask($user, $task_id){

        Db::startTrans();
        try{
            $is_complete = 0;
            //判断任务是否已领取完
            $task = TaskModel::where([
                'id' => $task_id,
                'status' => 1,
                'is_delete' => 0
            ])->find();

            //检测是否能申请该任务
            $can_apply =$this->canApply($user, $task);
            if (!$can_apply) {
                Db::rollback();
                return false;
            }

            //判断是否超接
            $task_data['apply_num'] = $task->apply_num + 1;
            if ($task_data['apply_num'] >= $task->max_apply_num ) {
                $task_data['is_complete'] = 1;
                //是否要移除任务
                $is_complete = 1;
            }

            $res[] = TaskModel::where([
                'id' => $task_id,
                'apply_num' => $task->apply_num,
                'status' => 1,
                'is_delete' => 0
            ])->update($task_data);


            $task_apply_data['task_id'] = $task['id'];
            $task_apply_data['user_id'] = $user['id'];
            $task_apply_data['reward_amount'] = $task['reward_amount'];
            $res[] = $task_apply = TaskApplyModel::Create($task_apply_data);

            foreach ($res as $key => $val) {
                if (!$val) {
                    Db::rollback();
                    $this->_error = '并发处理异常';
                    return false;
                }
            }

            Db::commit();

            //已经结束，需要删除redis平台任务缓存
            if ($is_complete) {
                $this->updatePlatformTaskSet(0, $task_id);
            }
            //增加已完成任务id
            $this->updateMyTaskSet($user['id'], $task_id);
            //今日数据
            $task['type'] == 1 ? $data['is_pt_task'] = 1 : $data['is_jx_task'] = 1;
            $this->updateTodayTaskData($user['id'], $data);

            return $task_apply;
        }catch (\Exception $e){
            Db::rollback();
            $this->_error = $e->getMessage();
            return false;
        }


    }


    //判断是否能接任务
    private function canApply($user, $task){

        if (!$task) {
            $this->_error = '该任务不存在';
            return false;
        }

        if ($task->is_complete == 1) {
            $this->_error = '该任务已失效';
            return false;
        }

        //判断是否到期
        if ($task->end_time <= time()) {
            $this->_error = '该任务已失效(到期)';
            return false;
        }
        
        // xxxxxxxxxxxxxxxxxx
        // 判断无可用任务

		if ($task->max_apply_num == $task->apply_num) {
            $this->_error = '该任务已领完';
            return false;
        }
		// xxxxxxxxxxxxxxxxxx
        if ($task['type'] == 2 && $user['user_level_id'] != 8) {
            $this->_error = '钻石任务只有钻石身份能领取';
            return false;
        }


        //可以考虑使用redis已存的结果任务id(redis缓存)
        $this->getMyTaskSet($user['id']);
        $have = Predis::getInstance()->sIsMember('task'.$user['id'].'_set', $task['id']);
        /*  mysql判断
         * $have = TaskApplyModel::where([
            'user_id' => $user['id'],
            'task_id' => $task['id'],
            'is_delete' => 0
        ])->value('id');*/
        if ($have) {
            $this->_error = '你已接过此任务';
            return false;
        }


        //普通任务
        if ($task['type'] == 1) {
            $today_task_pt_num = $this->getTodayTaskData($user['id'],'today_task_pt');//今日总任务数
           
            $user_level_hidden = explode(',', $user['user_level_hidden']);
            $day_task_num = UserLevelModel::whereIn('id', $user_level_hidden)->where('id != 8')->sum('day_task_num');//今日可接数量
	
            if ($today_task_pt_num >= $day_task_num) {
                $this->_error = '一天最多只能接'.$day_task_num.'普通个任务';
                return false;
            }

        } else {//钻石任务
            $today_task_jx_num = $this->getTodayTaskData($user['id'], 'today_task_jx');//今日总任务数
            $day_task_num = UserLevelModel::where('id', $user['user_level_id'])->value('day_task_num');//今日可接数量

            if ($today_task_jx_num >= $day_task_num) {
                $this->_error = '一天最多只能接'.$day_task_num.'个钻石任务';
                return false;
            }

        }


        return true;
    }
}
