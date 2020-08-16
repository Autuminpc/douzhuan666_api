<?php

namespace app\common\model;

use think\Model;

class TaskApply extends Model
{


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];


    public function task(){
        return $this->hasOne('Task', 'id', 'task_id')->field('name, video_url, video_url as video_url_path, task_platform_id, task_platform_id as cover_path, task_category_id, task_category_id as task_category_name, type, type as type_name, describe, video_name, info_type, info_type as info_type_str, cover, cover as task_cover_path');
    }


    //任务链接
    public function getTaskNameAttr($value, $data){
        return isset($this->task['name'])?$this->task['name']:'';
    }

    //任务名字
    public function getVideoNameAttr($value, $data){
        return isset($this->task->video_name)?$this->task->video_name:'';
    }

    //任务链接
    public function getVideoUrlAttr($value, $data){
        return isset($this->task->video_url)?$this->task->video_url:'';
    }
    //任务链接提取
    public function getVideoUrlPathAttr($value, $data){
        return isset($this->task->video_url_path)?$this->task->video_url_path:'';
    }

    //任务文案
    public function getDescribeAttr($value, $data){
        return isset($this->task->describe)?$this->task->describe:'';
    }

    //任务信息类型
    public function getInfoTypeStrAttr($value, $data){
        return isset($this->task->info_type_str)?$this->task->info_type_str:'';
    }

    //任务类型
    public function getTypeNameAttr($value, $data){
        return isset($this->task->type_name)?$this->task->type_name:'';
    }

    //封面图
    public function getCoverPathAttr(){

        return isset($this->task->cover_path)?$this->task->cover_path:'';

    }

    //任务图
    public function getTaskCoverPathAttr(){

        return isset($this->task->task_cover_path)?$this->task->task_cover_path:'';

    }

    //需求
    public function getTaskCategoryNameAttr(){

        return isset($this->task->task_category_name)?$this->task->task_category_name:'';

    }

    public function getStatusStrAttr($value, $data){
        $param = ['0' => '未完成', '1' => '待审核', '2' => '审核通过', '3' => '审核不通过'];
        return $param[$data['status']];
    }

    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d H:i', $data['create_time']);
    }
    public function getSubmitTimeStrAttr($value, $data){
        if ($data['submit_time']) {
            return date('Y-m-d H:i', $data['submit_time']);
        } else {
            return '';
        }

    }
    public function getVerifyTimeStrAttr($value, $data){
        return date('Y-m-d H:i', $data['verify_time']);
    }

    public function getTaskApplyArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, task_id, reward_amount, create_time, submit_time, verify_time, verify_mark, status')->order('id desc')->page($page, $row)->select());



        if (count($data)) {

            $data->append(['cover_path', 'type_name', 'task_category_name', 'create_time_str', 'submit_time_str', 'verify_time_str', 'status_str', 'video_url', 'video_url_path']);
        }
        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }
}
