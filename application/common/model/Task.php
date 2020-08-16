<?php

namespace app\common\model;

use think\Model;

class Task extends Model
{


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $append = [
    ];

    public function taskPlatform(){
        return $this->hasOne('TaskPlatform', 'id', 'task_platform_id')->field('cover, name');
    }

    public function taskCategory(){
        return $this->hasOne('TaskCategory', 'id', 'task_category_id')->field('name');
    }

    public function user(){
        return $this->hasOne('User', 'id', 'user_id')->field('username');
    }

    //任务类型
    public function getInfoTypeStrAttr($value, $data){
        $para = ['1' => '供应信息', '2' => '需求信息'];
        return isset($para[$data['info_type']])?$para[$data['info_type']]:'';
    }

    //任务类型
    public function getTypeNameAttr($value, $data){
        $para = ['1' => '普通', '2' => '钻石'];
        return $para[$data['type']];
    }

    //平台名称
    public function getTaskPlatformStrAttr(){

        return $this->taskPlatform['name'];

    }

    //平台封面图
    public function getCoverPathAttr(){

        return oosAbsolutePath($this->taskPlatform->cover);

    }

    //任务封面图
    public function getTaskCoverPathAttr(){
        //absolutePath($this->cover)
        return '';

    }

    //需求
    public function getTaskCategoryNameAttr(){

        return $this->taskCategory['name'];

    }

    //需求方
    public function getOwnerAttr($value, $data){

        if ($data['user_id']) {
            return substr_replace($this->user['username'],'****',3,4);
        }
        return 'admin';

    }

    //剩余数量
    public function getSurplusNumAttr($value, $data){
        return $data['max_apply_num'] - $data['apply_num'];
    }

    //任务状态
    public function getCompleteStrAttr($value, $data){
        $para = ['0' => '未结束', '1' => '已结束'];
        return $para[$data['is_complete']];
    }

    //提取链接
    public function getVideoUrlPathAttr($value, $data){
        $result = [];
        if (preg_match('/(https?|http|ftp|file):\/\/[-A-Za-z0-9+&@#\/\%\?=~_|!:,.;]+[-A-Za-z0-9+&@#\/\%=~_|]/', $data['video_url'], $result)){
            return $result[0];
        } else {
            return '';
        }
    }

    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d H:i', $data['create_time']);
    }
    public function getEndTimeStrAttr($value, $data){
        return date('Y-m-d H:i', $data['end_time']);
    }

    public function getTaskArray($where = [], $page = 1, $row = 10, $order='id desc')
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('id, task_platform_id, task_category_id, user_id, cover, type, reward_amount, max_apply_num, apply_num, create_time, end_time, is_complete')->order($order)->page($page, $row)->select());



        if (count($data)) {

            $data->visible(['id', 'max_apply_num', 'apply_num', 'reward_amount', 'is_complete'])->append(['surplus_num', 'cover_path', 'type_name', 'task_category_name', 'owner', 'create_time_str', 'end_time_str', 'complete_str']);
        }
        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }

}
