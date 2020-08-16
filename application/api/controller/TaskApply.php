<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\TaskDeal as TaskDealLib;
use app\common\model\TaskApply as TaskApplyModel;
use app\common\library\Predis;
/**
 * 首页接口
 */
class TaskApply extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function taskApplyList()
    {

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');


        $task_apply_where = [
            ['user_id', 'in', $user_id],
        ];



        if (isset($status) && $status !== '' && $status != '-1') {
            if ($status == 0) {
                array_push($task_apply_where, ['status', '=', 0]);
            } elseif ($status == 1) {
                array_push($task_apply_where, ['status', '=', 1]);
            } else {
                array_push($task_apply_where, ['status', 'in', [2, 3]]);
            }

        }



        $task_list = (new TaskApplyModel())->getTaskApplyArray($task_apply_where, $page, $row);


        $this->success('请求成功', $task_list);
    }

    //获取任务详情
    public function taskApply(){
        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post('id');

        if (!$id) {
            $this->error('参数有误');
        }

        $task_apply = TaskApplyModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->field('id, task_id, reward_amount, create_time, submit_time, verify_time, submit_file, verify_mark, status')->find();

        if (!$task_apply) {
            $this->error('该任务不存在', '', 10100);
        }

        $task_apply->append(['cover_path', 'task_cover_path', 'type_name', 'task_category_name', 'create_time_str', 'submit_time_str', 'verify_time_str', 'status_str', 'cover_path', 'video_name', 'describe', 'video_url', 'video_url_path', 'info_type_str', 'task_name']);

        $this->success('请求成功', $task_apply);
    }

    //提交任务
    public function submitTaskApply(){
        $id = $this->request->post('id');
        $submit_file = $this->request->post('submit_file');

        if (!($id&&$submit_file)) {
            $this->error('参数有误');
        }

        $task_apply = TaskApplyModel::where('id', $id)->field('id, submit_file, submit_time, status')->find();

        if (!$task_apply) {
            $this->error('该任务不存在', '', 10100);
        }

        if ($task_apply->status != 0) {

            $this->error('该任务已提交');
        }

        $task_apply->submit_file = $submit_file;
        $task_apply->submit_time = time();
        $task_apply->status = 1;
        $task_apply->save();

        Predis::getInstance()->lPush('task_apply_list', $id);

        /*
        $res = TaskDealLib::completeTask($id);
*/
        if (1) {
            $this->success('提交成功', $id);
        } else {
            $this->error(TaskDealLib::$_error, $id);
        }


    }



}
