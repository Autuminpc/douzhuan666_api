<?php

namespace app\common\model;

use think\Model;

/**
 * 会员地址模型
 */
class UserAddress Extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    public function provinceObj(){
        return $this->hasOne('Area', 'id', 'province')->field('name');
    }

    public function cityObj(){
        return $this->hasOne('Area', 'id', 'city')->field('name');
    }

    public function countyObj(){
        return $this->hasOne('Area', 'id', 'county')->field('name');
    }

    public function getAddressAttr($value, $data){
        return $this->provinceObj['name'].$this->cityObj['name'].$this->countyObj['name'].$data['detail'];
    }

    public function getShortAddressAttr($value, $data){
        return $this->provinceObj['name'].$this->cityObj['name'].$this->countyObj['name'];
    }

    public function getUserAddressArray($where = [])
    {


        array_push($where, ['is_delete', '=', 0]);



        $data = collection(self::where(function ($query) use ($where) {

            if ($where) {
                foreach ($where as $key => $val) {
                    $query->where($val[0], $val[1], $val[2]);
                }
            }

        })->order('id desc')->select());



        if (count($data)) {
            //'username', 'avatar_path',
            $data->visible(['id', 'name', 'mobile', 'detail', 'is_default'])->append(['short_address']);
        }
        $data->toArray();


        return $data;
    }

}
