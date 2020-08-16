<?php

namespace app\common\model;

use think\Model;

/**
 * 会员模型
 */
class User extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function userAgent(){
        return $this->hasOne('UserAgent', 'id', 'user_agent_id')->field('name');
    }
    public function userLevel(){
        return $this->hasOne('UserLevel', 'id', 'user_level_id')->field('name, color');
    }

    public function getUserAgentNameAttr(){
        return $this->userAgent['name'];
    }

    public function getUserLevelNameAttr(){
        return $this->userLevel['name'];
    }

    public function getUserLevelColorAttr(){
        return $this->userLevel['color'];
    }

    //封面图
    public function getAvatarPathAttr($value, $data){

        return oosAbsolutePath($data['avatar']);

    }


    //创建时间
    public function getCreateTimeStrAttr($value, $data){
        return date('Y-m-d', $data['create_time']);
    }


    public function getUserArray($where = [], $page = 1, $row = 10)
    {


        array_push($where, ['is_delete', '=', 0]);


        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->field('avatar, avatar as avatar_path, user_agent_id, user_level_id, username, create_time, create_time as create_time_str')->order('id desc')->page($page, $row)->select());

        if (count($data)) {

            $data->append(['user_agent_name', 'user_level_name', 'user_level_color']);
        }

        $res['list'] = $data->toArray();
        $res['current_page'] = $page;


        return $res;
    }

}
