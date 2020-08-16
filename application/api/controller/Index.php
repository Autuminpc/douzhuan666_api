<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Task as TaskLib;
use app\common\model\Advert as AdvertModel;
use app\common\model\Announce as AnnounceModel;
use app\common\model\UserAnnounce as UserAnnounceModel;
use app\common\model\TaskPlatform as TaskPlatformModel;
use app\common\model\Task as TaskModel;
/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {

        $user_id = $this->auth->getUser()['id'];
        //获取轮播图
        $advert_where = [
            ['port', '=', 1],
            ['status', '=', 1]
        ];
        $advert_list = (new AdvertModel())->getAdvertArray($advert_where);

        //获取公告轮播
        $announce_where = [
            ['status', '=', 1]
        ];
        $announce_list = (new AnnounceModel())->getAnnounceArray($announce_where, 1, 3);
        for ($i=0; $i<count($announce_list['list']); $i++) {
            $announce_id = $announce_list['list'][$i]['id'];
            $announce_list['list'][$i]['is_read'] = UserAnnounceModel::where([
                'user_id' => $user_id,
                'announce_id' => $announce_id
            ])->value('id')?1:0;
        }
        $announce_ids = UserAnnounceModel::where([
            'user_id' => $user_id,
            'is_delete' => 0
        ])->column('announce_id');
        $wait_read_num = AnnounceModel::whereNotIn('id', $announce_ids)->where([
            'status' => 1,
            'is_delete' => 0
        ])->value('id')?:0;



        //任务平台
        $task_platform_list = TaskPlatformModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->field(['id, name'])->order('sort desc')->select();





        $data['advert_list'] = $advert_list;
        $data['announce_list'] = $announce_list;
        $data['task_platform_list'] = $task_platform_list;
        $data['wait_read_num'] = $wait_read_num;
        $this->success('请求成功', $data);
    }


    //公告列表
    public function announceList(){

        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $announce_where = [
            ['status', '=', 1]
        ];

        $announce_list = (new AnnounceModel())->getAnnounceArray($announce_where, $page, $row);

        for ($i=0; $i<count($announce_list['list']); $i++) {
            $announce_id = $announce_list['list'][$i]['id'];
            $announce_list['list'][$i]['is_read'] = UserAnnounceModel::where([
                'user_id' => $user_id,
                'announce_id' => $announce_id
            ])->value('id')?1:0;
        }

        $this->success('请求成功', $announce_list);
    }


    //公告详情
    public function announce(){
        $user_id = $this->auth->getUser()['id'];

        $id = $this->request->post('id');

        if (!$id) {
            $this->error('缺少参数');
        }

        $announce = AnnounceModel::where([
            'id' => $id,
            'status' => 1,
            'is_delete' => 0
        ])->field('id, title, content, create_time')->find()->append(['create_time_str']);

        if (!$announce) {
            $this->error('该公告不存在', '', 10100);
        }

        //判断是否已读
        $read = UserAnnounceModel::where([
            'user_id' => $user_id,
            'announce_id' => $id,
            'is_delete' => 0
        ])->value('id');
        if (!$read) {
            UserAnnounceModel::create([
                'user_id' => $user_id,
                'announce_id' => $id,
                'is_delete' =>0
            ]);
        }

        $this->success('请求成功', $announce);

    }
}
