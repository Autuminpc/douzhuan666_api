<?php

namespace app\common\library;



use app\common\model\PayOrder as PayOrderModel;
use app\common\model\User as UserModel;
use app\common\model\UserLevel as UserLevelModel;
use app\common\model\UserAgent as UserAgentModel;
use app\common\model\TaskSale as TaskSaleModel;
use app\common\model\Config as ConfigModel;
use app\common\model\Meal as MealModel;
use app\common\model\MealBuy as MealBuyModel;
use think\Db;
use think\Exception;

class MealDeal
{
    
    public $_error = '';


    public function completeMeal($pay_order_id, $pay_no, $payment_type){

        Db::startTrans();
        try {

            $pay_order = PayOrderModel::where('id', $pay_order_id)->find();
            if (!$pay_order){

                Db::rollback();
                $this->_error = '该充值申请记录不存在';
                return false;
            }

            if ($pay_order['status']==1) {
                $this->_error = '该充值申请记录已处理';
                return true;
            }

            //用户数据
            $user_data = UserModel::field('id,username,user_level_hidden,user_level_hidden_id,user_agent_id,first_parent,second_parent,third_parent,parent_id')->where(array('id'=>$pay_order->user_id))->find();





            //购买套餐等级奖励
            $reward_user_level_id = MealModel::where('id', $pay_order->meal_id)->value('reward_user_level_id');

            //生成套餐购买记录表
            $meal_buy = MealBuyModel::create(
                [
                    'user_id' => $pay_order->user_id,
                    'meal_id' => $pay_order->meal_id,
                    'reward_user_level_id' => $reward_user_level_id
                ]
            );


            if ($reward_user_level_id) {
                $user_level_ids = explode(',', $user_data['user_level_hidden']);
                //奖励等级是否已经存在
                if (!in_array($reward_user_level_id ,$user_level_ids)) {
                    $user_update_data = [];
                    //取出最高等级的weight值
                    $weight = UserLevelModel::whereIn('id', $user_level_ids)->max('weight');
                    //查找赠送等级的weight值
                    $user_level = UserLevelModel::where('id', $reward_user_level_id)->field('id, weight')->find();
                    //赠送等级笔记高
                    if ($user_level['weight'] >= $weight) {
                        $user_update_data['user_level_hidden_id'] = $reward_user_level_id;
                        $user_update_data['user_level_id'] = $reward_user_level_id;
                    }
                    $user_level_hidden = $user_data['user_level_hidden'].','.$reward_user_level_id;
                    //取出普通会员
                    $user_level_hidden_arr = explode(',', $user_level_hidden);
                    foreach ($user_level_hidden_arr as $key=>$val) {
                            if ($val==1)
                                unset($user_level_hidden_arr[$key]);
                    }
                    $user_update_data['user_level_hidden'] = implode(',', $user_level_hidden_arr);

                    UserModel::where(array('id'=>$pay_order->user_id))->update($user_update_data);

                }
            }

            //分佣奖励
            $user_reward = $this->add_meal_price($pay_order, $user_data, $meal_buy['id']);



            $pay_order_data_data['status'] = 1;
            $pay_order_data_data['pay_time'] = time();
            $pay_order_data_data['pay_no'] = $pay_no;
            $pay_order_data_data['payment_type'] = $payment_type;

            $update_success = PayOrderModel::where([
                'id' => $pay_order_id,
                'status' => 0
            ])->update($pay_order_data_data);
            //更新更改才进行奖励
            if ($update_success) {
                Db::commit();

                //进行缓存奖励更新
                if(isset($user_reward)){
                    $task_library = new Task();
                    foreach ($user_reward as $key => $value) {
                        $task_library->updateTodayTaskData($value['user_id'], $value, 1);
                    }
                }
                //加入72小时处理有序集合
                Predis::getInstance()->zAdd('meal_buy_dj_zset', time(), $meal_buy['id']);

                //同个套餐30秒不用能同个支付方式
                Predis::getInstance()->set('meal'.$pay_order->meal_id.'_'.$payment_type.'_'.$pay_order->user_id, 1);
                Predis::getInstance()->expire('meal'.$pay_order->meal_id.'_'.$payment_type.'_'.$pay_order->user_id, 30);

                return true;
            } else {
                $this->_error = '该充值申请记录状态修改失败';
                Db::rollback();
                return false;
            }





        } catch (Exception $e) {
            $this->_error = $e->getMessage();
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
    public function add_meal_price($_data, $user_data, $meal_buy_id){
        //定义一个数组专门存用户ID和收益金额，专门统计今日收益
        $user_reward = [];

        $user_model = new UserModel();



        //$_data = PayOrderModel::where(array('id'=>$pay_order_id))->find();




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
                    $agent_level_list[$agent_key]['amount'] = $_data['pay_amount'] * $agent_value['reward'];
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
                    $parent_info = $user_model->where('is_delete', '0')->where('status', '1')->where('id', $pid)->field('id,user_agent_id')->find();
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
                    $this->add_sale($meal_buy_id, $agent_level_list[$parent_info['user_agent_id']]['amount'], $parent_info['id'], 3, $agent_level_list[$parent_info['user_agent_id']]['name'] . '提成，来源用户' . $user_data['username'], $user_data['id']);
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

        $meal = MealModel::where('id', $_data['meal_id'])->field('first_meal_reward, second_meal_reward, third_meal_reward')->find();
        //等级分佣奖励
        if($is_open_level_reward && $meal){
            //一级分佣
            if($user_data['first_parent'] > 0){
                //获取一级用户信息
                $first_meal_max_money = PayOrderModel::where('user_id',$user_data['first_parent'])->where('is_delete','0')->where('status','1')->max('pay_amount')?:0;
                //用户存在
                if($meal['first_meal_reward'] > 0 && ($first_meal_max_money >= 296 || $first_meal_max_money >= $_data['pay_amount'])){

                    $first_reward_amount = $meal['first_meal_reward'];

                    //进行奖励
                    $this->add_sale($meal_buy_id, $first_reward_amount, $user_data['first_parent'], 3, '一级提成，来源用户'.$user_data['username'], $user_data['id']);
                    $user_reward[] = [
                            'user_id' => $user_data['first_parent'],
                            'money' => $first_reward_amount,
                    ];

                }

            }
            //二级分佣
            if($user_data['second_parent'] > 0){
                //获取一级用户信息
                $second_meal_max_money = PayOrderModel::where('user_id',$user_data['second_parent'])->where('is_delete','0')->where('status','1')->max('pay_amount')?:0;
                //用户存在
                if($meal['second_meal_reward'] > 0 && ($second_meal_max_money >= 296 || $second_meal_max_money >= $_data['pay_amount'])){

                    //计算奖励金额
                    $second_reward_amount = $meal['second_meal_reward'];

                    //进行奖励
                    $this->add_sale($meal_buy_id, $second_reward_amount, $user_data['second_parent'], 3, '二级提成，来源用户'.$user_data['username'], $user_data['id']);
                    $user_reward[] = [
                        'user_id' => $user_data['second_parent'],
                        'money' => $second_reward_amount,
                    ];


                }
            }
            //三级分佣
            if($user_data['third_parent'] > 0){
                //获取一级用户信息
                $third_meal_max_money = PayOrderModel::where('user_id',$user_data['third_parent'])->where('is_delete','0')->where('status','1')->max('pay_amount')?:0;
                //用户存在
                if($meal['third_meal_reward'] > 0 && ($third_meal_max_money >= 296 || $third_meal_max_money >= $_data['pay_amount'])){

                    //计算奖励金额
                    $third_reward_amount = $meal['third_meal_reward'];

                    //进行奖励
                    $this->add_sale($meal_buy_id, $third_reward_amount, $user_data['third_parent'], 3, '三级提成，来源用户'.$user_data['username'], $user_data['id']);
                    $user_reward[] = [
                        'user_id' => $user_data['third_parent'],
                        'money' => $third_reward_amount,
                    ];


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
    private function add_sale($meal_buy_id, $price, $user_id, $type, $remark, $from_user_id)
    {
        //添加直销收入记录
        $data['user_id'] = $user_id;
        $data['from_user_id'] = $from_user_id;
        $data['apply_id'] = $meal_buy_id;
        $data['price'] = $price;
        $data['remark'] = $remark;
        $data['create_time'] = time();
        $data['type'] = $type;
        $result = TaskSaleModel::create($data);


        //添加金额变动记录
        model('app\admin\model\User')->incPrice($user_id, $price, $type, $remark, $meal_buy_id);

    }
}
