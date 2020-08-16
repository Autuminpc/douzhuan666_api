<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Task as TaskLib;
use app\common\model\Task as TaskModel;
use app\common\model\Meal as MealModel;
use app\common\model\Config as ConfigModel;
use app\common\model\TaskPlatform as TaskPlatformModel;
use app\common\library\Predis;
use app\common\library\Lock as LockLib;
/**
 * 首页接口
 */
class Task extends Api
{
    protected $noNeedLogin = ['taskList'];
    protected $noNeedRight = ['*'];


    //任务筛选
    public function taskConditionList(){
        $task_type = [
            ['id' => 1, 'name' => '普通任务'],
            ['id' => 2, 'name' => '钻石任务'],
        ];

        $task_platform = TaskPlatformModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->field('id, name')->select();


        $data['task_type'] = $task_type;
        $data['task_platform'] = $task_platform;
        $this->success('请求成功', $data);

    }

    /**
     * 任务列表
     *
     */
    public function taskList()
    {

        $user_id = $this->auth->getUser()['id'];
        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $type = $this->request->post('type')?:'';
        $task_platform_id = $this->request->post('task_platform_id')?:'';
        
        //任务列表
        //查找可接任务id

        $can_apply = (new TaskLib())->getCanApplyTaskIds($user_id);
 
        // $start = ($page-1)*100;
        // $can_apply = array_slice($can_apply, $start, 1000);
        //$can_apply = (new TaskLib())->getPlatformTaskSet($type, $task_platform_id, 1, $row);
		
        $task_where = [
            ['id', 'in', $can_apply],
            ['end_time','>=',time()],
            ['status', '=', 1]
        ];

        if (isset($type) && $type !== '' && $type != '-1') {
            array_push($task_where, ['type', '=', $type]);
        }

        if (isset($task_platform_id) && $task_platform_id !== '' && $task_platform_id != '-1') {
            array_push($task_where, ['task_platform_id', '=', $task_platform_id]);
        }
     
        $task_list = (new TaskModel())->getTaskArray($task_where, $page, $row, 'is_user desc, id asc');
        // $task_list['debug'] = [
        //     count($task_list['list'])
        //     ,$can_apply
        //     ,$row
        // ];

        if ($task_list['list']) {
            foreach ($task_list['list'] as $key => $task) {
                $task_list['list'][$key]['can_apply'] = Predis::getInstance()->sIsMember('task'.$user_id.'_set', $task['id'])?0:1;
            }
        }

        $task_list['current_page'] = $page;
		$this->success('请求成功', $task_list);
    }
    
    
   
    
    // 任务平台类型 do： x
    public function taskPlatform(){
    	$platfrom_list = (new TaskPlatformModel())->where(["status"=>1,"is_delete"=>0])->order('sort','desc')->select();
    
	   	$this->success('请求成功', $platfrom_list); 	
 
    }

    //任务领取
    public function applyTask(){
        $user = $this->auth->getUser();

        //后台是否开启接任务功能
        $can_apply_task = ConfigModel::where('id', 38)->value('value');
        
        //未开启
        if (!$can_apply_task) {
            //任务提示
            $no_apply_task_remark = ConfigModel::where('id', 39)->value('value');
            $this->error($no_apply_task_remark);
        }

        $task_id = $this->request->post('id');
    
        if (!$task_id) {
            $this->error('缺少参数');
        }

        //加锁
        if (LockLib::redisLock('apply_task'.$task_id, 5)) {
            $TaskLib = (new TaskLib());
            $res = $TaskLib->applyTask($user, $task_id);
        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('apply_task'.$task_id);

        if ($res) {
            $this->success('领取成功', $res);
        } else {
            $this->error($TaskLib->_error);
        }
    }

    //已发任务列表
    public function myTaskList(){

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $is_complete = $this->request->post('is_complete');




        $task_where = [
            ['user_id', '=',  $user_id],
            ['status', '=',  1],
        ];
        //是否有订单状态条件(0未结束，1已结束)
        if (isset($is_complete) && $is_complete !== '' && $is_complete != '-1') {

            array_push($task_where, ['is_complete', '=', $is_complete]);

        }


        $meal_buy_list = (new TaskModel())->getTaskArray($task_where, $page, $row);

        $this->success('获取成功', $meal_buy_list);
    }


    //发布任务
    public function task(){

        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        if (!$id) {
            $this->error('参数有误');
        }

        $task = TaskModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' =>0
        ])->field('id, type,name,video_name,video_url, task_category_id, info_type, cover, task_platform_id, max_apply_num, apply_num, reward_amount, cover, is_complete, create_time, end_time, describe')->find();

        if (!$task) {
            $this->error('该任务不存在', '', 10100);
        }


        $task->append(['task_category_name', 'create_time_str', 'task_platform_str', 'task_cover_path', 'info_type_str', 'task_cover_path', 'end_time_str', 'complete_str']);

       // $meal = MealModel::where('id', $task->meal_id)->field('task_category_id, task_reward_amount, task_apply_num')->find()->append(['task_category_name']);


        $data['id'] = $task['id'];
        $data['type_str'] = $task['type_name'];
        $data['task_category_name'] = $task['task_category_name'];
        $data['task_reward_amount'] = $task['reward_amount'];
        $data['task_apply_num'] = $task['max_apply_num'];
        $data['apply_num'] = $task['apply_num'];
        $data['name'] = $task['name'];
        $data['info_type'] = $task['info_type'];
        $data['info_type_str'] = $task['info_type_str'];
        $data['task_platform_id'] = $task['task_platform_id'];
        $data['task_platform_str'] = $task['task_platform_str'];
        $data['name'] = $task['name'];
        $data['video_name'] = $task['video_name'];
        $data['video_url'] = $task['video_url'];
        $data['end_time_str'] = $task['end_time_str'];
        $data['cover'] = $task['cover'];
        $data['task_cover_path'] = $task['task_cover_path'];
        $data['describe'] = $task['describe'];
        $data['is_complete'] = $task['is_complete'];
        $data['complete_str'] = $task['complete_str'];

        $data['info_type_list'] = [
            [
                'id' => 1,
                'name' => '供应信息'
            ],
            [
                'id' => 2,
                'name' => '需求信息'
            ],
        ];
        $data['task_platform_list'] = TaskPlatformModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->field('id, name')->select();


        $this->success('提交成功', $data);


    }


    //保存我的任务
    public function saveTask(){

        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");
        $param['name'] = $this->request->post("name");
        $param['info_type'] = $this->request->post("info_type_id");
        $param['task_platform_id'] = $this->request->post("task_platform_id");
        $param['video_name'] = $this->request->post("video_name");
        $param['video_url'] = $this->request->post("video_url");
        $param['cover'] = $this->request->post("cover");
        $param['describe'] = $this->request->post("describe");
        $param['update_time'] = time();

        $have = TaskPlatformModel::where('id', $param['task_platform_id'])->count();
        if (!$have) {
            $this->error('平台类型不存在');
        }


        if (!$id) {
            $this->error('参数有误');
        }

        $task = TaskModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' =>0
        ])->field('task_platform_id, is_complete, status')->find();

        if (!$task) {
            $this->error('该任务不存在', '', 10100);
        }

        if ($task->is_complete != 0) {
            $this->error('该任务已结束');
        }

        //更新任务
        $res = TaskModel::where([
            'id' => $id,
            'is_complete' => 0,
            'user_id' => $user_id,
            'status' => 1,
            'is_delete' =>0
        ])->update($param);

        if ($res) {
            //如果平台发生变动，且是上线的需要更改可领任务缓存
            if ($task['status'] == 1 && $task['task_platform_id'] != $param['task_platform_id']) {
                $TaskLib = (new TaskLib());
                $TaskLib->updatePlatformTaskSet(0, $id);
                $TaskLib->updatePlatformTaskSet(1, $id);
            }

            $this->success('保存成功', $id);
        } else {
            $this->error('保存失败');
        }
    }

}
