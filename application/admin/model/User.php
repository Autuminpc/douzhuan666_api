<?php

namespace app\admin\model;

use app\common\model\MoneyLog;
use think\Model;

class User extends Model
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [
        'prevtime_text',
        'logintime_text',
        'jointime_text'
    ];
    public function getOriginData()
    {
        return $this->origin;
    }
    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            //如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = \fast\Random::alnum();
                    $row->password = \app\common\library\Auth::instance()->getEncryptPassword(md5($changed['password']), $salt);
                    $row->salt = $salt;
                    $row->has_update_pwd = 1;
                } else {
                    unset($row->password);
                }
            }
        });


        self::beforeUpdate(function ($row) {
            $changedata = $row->getChangedData();
            if (isset($changedata['money'])) {
                $origin = $row->getOriginData();
                if($row->totol_reward + ($changedata['money'] - $origin['money']) > 0){
                    $row->totol_reward += $changedata['money'] - $origin['money'];
                }
                MoneyLog::create(['type'=>'5','user_id' => $row['id'], 'money' => $changedata['money'] - $origin['money'], 'before' => $origin['money'], 'after' => $changedata['money'], 'remark' => '管理员变更金额']);
            }
        });
    }

    

    public function getStatusList()
    {
        return ['1' => __('Normal'), '0' => __('Hidden')];
    }

    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['prevtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['logintime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['jointime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPrevtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setLogintimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setJointimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function userlevel(){

        return $this->belongsTo('app\admin\model\user\UserLevel', 'user_level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function useragent(){
        
        return $this->belongsTo('app\admin\model\user\UserAgent', 'user_agent_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    /**
     * 更新会员余额
     * @param $user_id
     * @param $price
     * @param int $type
     * @param string $remark
     * @return bool
     */
    public function incPrice($user_id, $price, $type, $remark = '', $no=''){
        if( !($price>0) ) {
            return false;
        }
        $res = $this->where('id', $user_id)->setInc('money', $price);
        if ($res) {
            //更新总收入 当提现失败返回余额时不加进累计收入
            if( in_array($type, ['1','2','3'])) {
                $this->where('id', $user_id)->setInc('totol_reward', $price);
            }
            //提现
            if( $type == 6 ) {
                $this->where('id', $user_id)->setInc('totol_withdraw', abs($price));
            }
            //任务收入 
            if($type == 1){
                $this->where('id', $user_id)->setInc('total_task_income', $price);
            }
            //任务分佣
            if($type == 2){
                $this->where('id', $user_id)->setInc('total_task_commission', $price);
            }
            //套餐分佣
            if($type == 3){
                $this->where('id', $user_id)->setInc('total_recommend_income', $price);
            }
            //提现驳回
            if($type == 7){
                $this->where('id', $user_id)->setDec('totol_withdraw', abs($price));
            }
            //查询当前余额
            $after_money = $this->where('id', $user_id)->value('money');
            //添加日志
            $this->price_log($user_id, $type, $price, $after_money, $remark, $no);
        }
        return $res;
    }

    /**
     * 金额变动日志
     *
     * @param [type] $user_id
     * @param [type] $type
     * @param [type] $change_money
     * @param [type] $after_money
     * @param string $remark
     * @param string $no
     * @return void
     */
    protected function price_log($user_id, $type, $change_money, $after_money, $remark = '', $no = ''){
        $add_data = [
            'type' => $type,
            'user_id' => $user_id, 
            'money' => $change_money,  //变动金额
            'before' => $after_money - $change_money, //当前金额 - 变动金额 = 变动前金额
            'after' => $after_money,                   //变动后金额
            'remark' => $remark,
            'no' => $no
        ];
        $res = MoneyLog::create($add_data);

        if($res){
            return true;
        }
        return false;
    }
    

}
