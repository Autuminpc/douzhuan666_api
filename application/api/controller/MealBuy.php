<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\MealBuy as MealBuyModel;
use app\common\model\Meal as MealModel;
use app\common\model\TaskPlatform as TaskPlatformModel;
use app\common\library\Lock as LockLib;
use app\common\library\Meal as MealLib;

/**
 * 会员接口
 */
class MealBuy extends Api
{
    protected $noNeedLogin = [''];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }



    //套餐列表
    public function mealBuyList(){

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $status = $this->request->post('status');



        //产品列表
        $meal_buy_where = [
            ['user_id', '=',  $user_id],
        ];
        //是否有订单状态条件(0待处理，1审核通过等待打款，2审核通过打款失败，3已完成，4审核不通过)
        if (isset($status) && $status !== '' && $status != '-1') {
            if ($status) {
                array_push($meal_buy_where, ['status', '=', $status]);
            } else {
                array_push($meal_buy_where, ['status', 'in', [0, 2]]);
            }


        }


        $meal_buy_list = (new MealBuyModel())->getMealBuyArray($meal_buy_where, $page, $row);

        $this->success('获取成功', $meal_buy_list);
    }



    //发布任务
    public function addTask(){

        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        if (!$id) {
            $this->error('参数有误');
        }

        $meal_buy = MealBuyModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'is_delete' =>0
        ])->field('id, meal_id, status')->find();

        if (!$meal_buy) {
            $this->error('任务购买记录不存在', '', 10100);
        }

        if ($meal_buy->status != 0 && $meal_buy->status != 2) {
            $this->error('该任务已发布');
        }

        $meal = MealModel::where('id', $meal_buy->meal_id)->field('task_user_level_id, task_category_id, task_reward_amount, task_apply_num')->find()->append(['type_str', 'task_category_name']);


        $data['id'] = $meal_buy['id'];
        $data['type_str'] = $meal['type_str'];
        $data['task_category_name'] = $meal['task_category_name'];
        $data['task_reward_amount'] = $meal['task_reward_amount'];
        $data['task_apply_num'] = $meal['task_apply_num'];

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


    //保存任务
    public function saveTask(){
        $user_id = $this->auth->getUser()['id'];
        $param['id'] = $this->request->post("id");
        $param['name'] = $this->request->post("name");
        $param['info_type_id'] = $this->request->post("info_type_id");
        $param['task_platform_id'] = $this->request->post("task_platform_id");
        $param['video_name'] = $this->request->post("video_name");
        $param['video_url'] = $this->request->post("video_url");
        $param['cover'] = $this->request->post("cover");
        $param['describe'] = $this->request->post("describe");
        if (!($param['name']&&$param['info_type_id']&&$param['task_platform_id']&&$param['video_url'])) {
            $this->error('缺少参数');
        }
        $have = TaskPlatformModel::where('id', $param['task_platform_id'])->count();
        if (!$have) {
            $this->error('平台类型不存在');
        }

        //加锁
        if (LockLib::redisLock('public_task'.$param['id'], 5)) {
            $MealLib = (new MealLib());
            $res = $MealLib->saveTask($user_id, $param);
        } else {
            $this->error('服务器异常', '频繁提交');
        }

        //释放锁
        LockLib::redisUnLock('public_task'.$param['id']);

        if ($res) {
            $this->success('提交成功', $res);
        } else {
            $this->error($MealLib->_error);
        }

    }




}
