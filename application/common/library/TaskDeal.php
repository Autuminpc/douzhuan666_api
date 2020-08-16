<?php

namespace app\common\library;



use app\common\model\TaskApply as TaskApplyModel;
use app\common\model\User as UserModel;
use app\common\model\Config as ConfigModel;
use app\common\model\UserLevel as UserLevelModel;
use app\common\model\UserAgent as UserAgentModel;
use app\common\model\TaskSale as TaskSaleModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use think\Db;
use think\Exception;

class TaskDeal
{
    
    public static $_error = '';


    public static function completeTask($task_apply_id){

        Db::startTrans();
        try {

            $task = TaskApplyModel::where('id', $task_apply_id)->find();
            if (!$task) {
                Db::rollback();
                self::$_error = '该任务申请记录不存在';
                return true;
            }

            $user_reward = self::add_task_price($task_apply_id);


            $data['status'] = 2;
            $data['verify_time'] = time();

            $update_success = TaskApplyModel::where([
                'id' => $task_apply_id,
                'status' => 1
            ])->update($data);
            //更新更改才进行奖励
            if ($update_success) {
                Db::commit();
                if(isset($user_reward)){
                    if ($user_reward&&is_array($user_reward)){
                        $task_library = new Task();
                        foreach ($user_reward as $key => $value) {
                            $task_library->updateTodayTaskData($value['user_id'], $value, 1);
                        }
                    }
                }
                return true;
            } else {
                Db::rollback();
                self::$_error = '该任务申请记录更新审核状态失败';
                return false;
            }

        } catch (Exception $e) {
            self::$_error = $e->getMessage();
            Db::rollback();
            return false;
        }
    }

    /**
     * 任务奖励、等级分佣、代理等级分佣
     *
     * @param [type] $id
     * @return void
     */
    public static function add_task_price($id){
        //定义一个数组专门存用户ID和收益金额，专门统计今日收益
        $user_reward = [];


        $_data = TaskApplyModel::where(array('id'=>$id))->find();

        if($_data['status'] != '1'){
            return [];
            //throw new Exception('任务状态异常');
        }

        //任务人收入
        self::add_sale($id, $_data['reward_amount'], $_data['user_id'], 1, '任务收入' , $_data['user_id']);
        $user_reward[] = [
            'user_id' => $_data['user_id'],
            'money' => $_data['reward_amount'],
        ];
        //用户数据
        $user_data = UserModel::field('id,username,user_level_hidden_id,user_agent_id,first_parent,second_parent,third_parent,parent_id')->where(array('id'=>$_data['user_id']))->find();

        //是否开启代理分佣
        $is_open_agent_reward = ConfigModel::where('name','is_open_agent_reward')->value('value');
        //是否开启等级分佣
        $is_open_level_reward = ConfigModel::where('name','is_open_level_reward')->value('value');

        //代理奖励
        if($is_open_agent_reward){

            //分割上级ID
            $parent_list = explode(',', $user_data['parent_id']);
            rsort($parent_list);
            //获取所有代理角色
            $agent_level_list = UserAgentModel::where('is_delete','0')->field('id,name,reward,weight')->order('weight asc')->select();
            $agent_level_list = collection($agent_level_list)->toArray();

            //最高代理等级权重
            $max_weight = $agent_level_list[count($agent_level_list)-1]['weight'];

            //获取用户代理等级
            $self_agent_weight = UserAgentModel::where('id',$user_data['user_agent_id'])->value('weight');

            //判断用户等级是不是最高,是最高等级不需要奖励
            if ($self_agent_weight <= $max_weight) {


                //计算每一级代理的奖励
                foreach ($agent_level_list as $agent_key => $agent_value) {
                    $agent_level_list[$agent_key]['amount'] = $_data['reward_amount'] * $agent_value['reward'];
                    //设置该代理等级还未奖励过
                    $agent_level_list[$agent_key]['has_reward'] = 0;
                }
                //用ID作为下标，方便拿数据
                $agent_level_list = array_column($agent_level_list, null, 'id');
                //循环遍历所有上级，找出对应角色进行奖励
                $reward_num = 0;
                $agent_level_count = count($agent_level_list, 0);
                //用一个变量记录最近一次奖励的代理身份的权重，以达到可以中间截断的目的
                $reward_agent_weight = 0;
                foreach ($parent_list as $pid) {
                    if ($reward_num == $agent_level_count) {
                        //已经奖励完所有角色了，停止循环防止占用内存
                        break;
                    }
                    //上级不存在
                    if (!$pid || $pid == $user_data['id']) {
                        continue;
                    }
                    //获取上级信息
                    $parent_info = UserModel::where('is_delete', '0')->where('status', '1')->where('id', $pid)->field('id,user_agent_id')->find();
                    if (!$parent_info) {
                        continue;
                    }
                    if ($agent_level_list[$parent_info['user_agent_id']]['has_reward'] == 1) {
                        //该等级已经奖励过，不再奖励
                        continue;
                    }
                    if (sprintf("%.3f", $agent_level_list[$parent_info['user_agent_id']]['amount']) <= 0) {
                        //奖励金额小于等于0，不进行奖励
                        continue;
                    }
                    //判断用户代理等级
                    $parent_agent_weight = UserAgentModel::where('id', $parent_info['user_agent_id'])->value('weight');
                    if ($self_agent_weight > $parent_agent_weight || $reward_agent_weight > $parent_agent_weight) {
                        //上级小于自己的代理等级，或者上级的代理等级比已经奖励的代理低等级低，不拿奖励
                        continue;
                    }

                    //进行奖励
                    self::add_sale($id, $agent_level_list[$parent_info['user_agent_id']]['amount'], $parent_info['id'], 2, $agent_level_list[$parent_info['user_agent_id']]['name'] . '分佣，来源用户' . $user_data['username'], $user_data['id']);
                    $user_reward[] = [
                        'user_id' => $parent_info['id'],
                        'money' => $agent_level_list[$parent_info['user_agent_id']]['amount'],
                    ];

                    //该等级为最高等级，不需要再网上奖励，已中断
                    if ($parent_agent_weight >= $max_weight) {
                        break;
                    }

                    //记录奖励的代理身份权重
                    $reward_agent_weight = $parent_agent_weight;
                    //增加奖励次数
                    $reward_num++;
                    //奖励过的这个等级，修改为已经奖励过了
                    $agent_level_list[$parent_info['user_agent_id']]['has_reward'] = 1;
                }
            }
        }
        //等级分佣奖励
        if($is_open_level_reward){
            //一级分佣
            if($user_data['first_parent'] >= 0){
                //获取一级用户信息
                $first_parent_info = UserModel::where('id',$user_data['first_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
                //用户存在
                if($first_parent_info){
                    //上级必须不是普通会员
                    //TODO CK调试
                    //if($first_parent_info['user_level_hidden_id'] != '1'){
                        //上级会员等级要大于自己的会员等级
                        $self_level_weight = UserLevelModel::where('id',$user_data['user_level_hidden_id'])->value('weight');
                        $first_level_weight = UserLevelModel::where('id',$first_parent_info['user_level_hidden_id'])->value('weight');
                        if($first_level_weight >= $self_level_weight){
                            //查询等级奖励比例
                            $first_rate = UserLevelModel::where('id',$first_parent_info['user_level_hidden_id'])->value('first_task_reward');
                            //计算奖励金额
                            $first_reward_amount = $_data['reward_amount'] * $first_rate;
                            //奖励金额不小于等于0，进行奖励
                            $first_reward_amount = round($first_reward_amount,3);
                            if($first_reward_amount > 0){
                                //进行奖励
                                self::add_sale($id, $first_reward_amount, $user_data['first_parent'], 2, '一级分佣，来源用户'.$user_data['username'], $user_data['id'] );
                                $user_reward[] = [
                                    'user_id' => $user_data['first_parent'],
                                    'money' => $first_reward_amount,
                                ];
                            }
                        }
                    //}
                }

            }
            //二级分佣
            if($user_data['second_parent'] >= 0){
                //获取一级用户信息
                $second_parent_info = UserModel::where('id',$user_data['second_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
                //用户存在
                if($second_parent_info){
                    //上级必须不是普通会员
                    //TODO CK调试
                    //if($second_parent_info['user_level_hidden_id'] != '1'){
                        //上级会员等级要大于自己的会员等级
                        $self_level_weight = UserLevelModel::where('id',$user_data['user_level_hidden_id'])->value('weight');
                        $second_level_weight = UserLevelModel::where('id',$second_parent_info['user_level_hidden_id'])->value('weight');
                        if($second_level_weight >= $self_level_weight){
                            //查询等级奖励比例
                            $second_rate = UserLevelModel::where('id',$second_parent_info['user_level_hidden_id'])->value('second_task_reward');
                            //计算奖励金额
                            $second_reward_amount = $_data['reward_amount'] * $second_rate;
                            //奖励金额不小于等于0，进行奖励
                            $second_reward_amount = round($second_reward_amount,3);
                            if($second_reward_amount > 0){
                                //进行奖励
                                self::add_sale($id, $second_reward_amount, $user_data['second_parent'], 2, '二级分佣，来源用户'.$user_data['username'], $user_data['id'] );
                                $user_reward[] = [
                                    'user_id' => $user_data['second_parent'],
                                    'money' => $second_reward_amount,
                                ];
                            }

                        }
                    //}
                }
            }
            //三级分佣
            if($user_data['third_parent'] >= 0){
                //获取一级用户信息
                $third_parent_info = UserModel::where('id',$user_data['third_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
                //用户存在
                if($third_parent_info){
                    //上级必须不是普通会员
                    if($third_parent_info['user_level_hidden_id'] != '1'){
                        //上级会员等级要大于自己的会员等级
                        $self_level_weight = UserLevelModel::where('id',$user_data['user_level_hidden_id'])->value('weight');
                        $third_level_weight = UserLevelModel::where('id',$third_parent_info['user_level_hidden_id'])->value('weight');

                        if($third_level_weight >= $self_level_weight){
                            //查询等级奖励比例
                            $third_rate = UserLevelModel::where('id',$third_parent_info['user_level_hidden_id'])->value('third_task_reward');
                            //计算奖励金额
                            $third_reward_amount = $_data['reward_amount'] * $third_rate;
                            //奖励金额不小于等于0，进行奖励
                            $third_reward_amount = round($third_reward_amount,3);
                            if($third_reward_amount > 0){
                                //进行奖励
                                self::add_sale($id, $third_reward_amount, $user_data['third_parent'], 2, '三级分佣，来源用户'.$user_data['username'], $user_data['id'] );
                                $user_reward[] = [
                                    'user_id' => $user_data['third_parent'],
                                    'money' => $third_reward_amount,
                                ];
                            }
                        }
                    }
                }
            }

        }

        return $user_reward;
    }

    /**
     * 直接奖励和分佣奖励记录
     *
     * @param [type] $apply_id
     * @param [type] $price
     * @param [type] $user_id
     * @param [type] $type
     * @param [type] $remark
     * @param [type] $from_user_id
     * @return void
     */
    private static function add_sale($apply_id, $price, $user_id, $type, $remark, $from_user_id)
    {
        //添加直销收入记录
        $data['user_id'] = $user_id;
        $data['from_user_id'] = $from_user_id;
        $data['apply_id'] = $apply_id;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['create_time'] = time();
        $data['type'] = $type;
        $result = TaskSaleModel::create($data);
        if( $result ) {
            //添加金额变动记录
            self::incPrice($user_id, $price, $type, $remark, $result->id);
        } else {
            throw new Exception('添加收益失败');
        }
    }


    public static function incPrice($user_id, $price, $type, $remark = '', $no=''){
        if( !($price>0) ) {
            return false;
        }
        $res = UserModel::where('id', $user_id)->setInc('money', $price);
        if ($res) {
            //更新总收入 当提现失败返回余额时不加进累计收入
            if( in_array($type, ['1','2','3'])) {
                UserModel::where('id', $user_id)->setInc('totol_reward', $price);
            }
            //提现
            if( $type == 6 ) {
                UserModel::where('id', $user_id)->setInc('totol_withdraw', abs($price));
            }
            //任务收入
            if($type == 1){
                UserModel::where('id', $user_id)->setInc('total_task_income', $price);
            }
            //任务分佣
            if($type == 2){
                UserModel::where('id', $user_id)->setInc('total_task_commission', $price);
            }
            //套餐分佣
            if($type == 3){
                UserModel::where('id', $user_id)->setInc('total_recommend_income', $price);
            }
            //提现驳回
            if($type == 7){
                UserModel::where('id', $user_id)->setDec('totol_withdraw', abs($price));
            }
            //查询当前余额
            $after_money = UserModel::where('id', $user_id)->value('money');
            //添加日志
            self::price_log($user_id, $type, $price, $after_money, $remark, $no);
        }
        return $res;
    }

    protected static function price_log($user_id, $type, $change_money, $after_money, $remark = '', $no = ''){
        $add_data = [
            'type' => $type,
            'user_id' => $user_id,
            'money' => $change_money,  //变动金额
            'before' => $after_money - $change_money, //当前金额 - 变动金额 = 变动前金额
            'after' => $after_money,                   //变动后金额
            'remark' => $remark,
            'no' => $no
        ];
        $res = UserMoneyLogModel::create($add_data);

        if($res){
            return true;
        }
        return false;
    }
}
