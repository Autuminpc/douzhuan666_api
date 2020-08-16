<?php

namespace app\admin\controller\task;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\library\Task as TaskLibrary;
use app\common\library\TaskDeal as TaskDeal;
/**
 * 任务领取记录管理
 *
 * @icon fa fa-circle-o
 */
class TaskApply extends Backend
{
    
    /**
     * TaskApply模型对象
     * @var \app\admin\model\task\TaskApply
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\task\TaskApply;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    // ->with(['task','user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();
            //当前是否为关联查询
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['task','user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 审核单个任务
     * @param [type] $ids
     * @return void
     */
    public function verify_one($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    if($params['status'] == '2'){
                        //进行审核通过操作
                        $task_deal = new TaskDeal();
                        $user_reward = $task_deal->add_task_price($row->id);
                    }
                    $params['verify_time'] = time();
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                    if(isset($user_reward)){
                        $task_library = new TaskLibrary();
                        foreach ($user_reward as $key => $value) {
                            $task_library->updateTodayTaskData($value['user_id'],$value);
                        }
                    }
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 批量审核通过
     * @param string $ids
     * @return void
     */
    public function handlepass($ids = ''){
        
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            $time = time();
            $values['status'] = '2';
            $values['verify_time'] = $time;
            if ($values) {
                $count = 0;
                Db::startTrans();
                try {
                    $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                    foreach ($list as $index => $item) {
                        if($item['status'] == '1'){
                            //进行审核通过操作
                            $task_deal = new TaskDeal();
                            $user_reward = $task_deal->add_task_price($item->id);
                        }
                        $count += $item->allowField(true)->isUpdate(true)->save($values);

                    }
                    Db::commit();
                    if(isset($user_reward)){
                        $task_library = new TaskLibrary();
                        foreach ($user_reward as $key => $value) {
                            $task_library->updateTodayTaskData($value['user_id'],$value);
                        }
                    }
                    
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($count) {
                    $this->success('审核成功');
                } else {
                    $this->error(__('No rows were updated'));
                }
            } else {
                $this->error(__('You have no permission'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 批量审核不通过
     * @param string $ids
     * @return void
     */
    public function handleunpass($ids = ''){
        
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            $time = time();
            $values['status'] = '3';
            $values['verify_time'] = $time;
            if ($values) {
                $count = 0;
                Db::startTrans();
                try {
                    $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                    foreach ($list as $index => $item) {
                        $count += $item->allowField(true)->isUpdate(true)->save($values);
                    }
                    Db::commit();
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($count) {
                    $this->success('审核成功');
                } else {
                    $this->error(__('No rows were updated'));
                }
            } else {
                $this->error(__('You have no permission'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    // /**
    //  * 任务奖励、等级分佣、代理等级分佣
    //  *
    //  * @param [type] $id
    //  * @return void
    //  */
    // protected function add_task_price($id){
    //     //定义一个数组专门存用户ID和收益金额，专门统计今日收益
    //     $user_reward = [];

    //     $task_apply_model = model('app\admin\model\task\TaskApply');
    //     $user_model = model('app\admin\model\User');
    //     $config_model = model('app\common\model\Config');
    //     $user_level_model = model('app\admin\model\user\UserLevel');
        

    //     $_data = $task_apply_model->where(array('id'=>$id))->find();

    //     if($_data['status'] != '1'){
    //         throw new Exception('任务状态异常');
    //     }

    //     //任务人收入
    //     $this->add_sale($id, $_data['reward_amount'], $_data['user_id'], 1, '任务收入' , $_data['user_id']);
    //     $user_reward[] = [
    //         'user_id' => $_data['user_id'],
    //         'money' => $_data['reward_amount'],
    //     ];
    //     //用户数据
    //     $user_data = $user_model->field('id,username,user_level_hidden_id,user_agent_id,first_parent,second_parent,third_parent,parent_id')->where(array('id'=>$_data['user_id']))->find();

    //     //是否开启代理分佣
    //     $is_open_agent_reward = $config_model::where('name','is_open_agent_reward')->value('value');
    //     //是否开启等级分佣
    //     $is_open_level_reward = $config_model::where('name','is_open_level_reward')->value('value');
        
    //     //代理奖励
    //     if($is_open_agent_reward){
    //         //分割上级ID
    //         $parent_list = explode(',', $user_data['parent_id']);
    //         //获取所有代理角色
    //         $agent_level_list = model('app\admin\model\user\UserAgent')->order('weight asc')->where('is_delete','0')->field('id,name,reward')->select();
    //         $agent_level_list = collection($agent_level_list)->toArray();
    //         //计算每一级代理的奖励
    //         foreach($agent_level_list as $agent_key => $agent_value){
    //             $agent_level_list[$agent_key]['amount'] = $_data['reward_amount'] * $agent_value['reward'];
    //             //设置该代理等级还未奖励过
    //             $agent_level_list[$agent_key]['has_reward'] = 0;
    //         }
    //         //用ID作为下标，方便拿数据
    //         $agent_level_list = array_column($agent_level_list, null, 'id');
    //         //循环遍历所有上级，找出对应角色进行奖励
    //         $reward_num = 0;
    //         $agent_level_count = count($agent_level_list,0);
    //         foreach ($parent_list as $pid) {
    //             if($reward_num == $agent_level_count){
    //                 //已经奖励完所有角色了，停止循环防止占用内存
    //                 break;
    //             }
    //             //获取上级信息
    //             $parent_info = $user_model->where('is_delete','0')->where('status','1')->where('id',$pid)->field('id,user_agent_id')->find();
    //             if(!$parent_info){
    //                 continue;
    //             }
    //             //判断用户代理等级
    //             if($user_data['user_agent_id'] > $parent_info['user_agent_id']){
    //                 //上级小于自己的代理等级，不拿奖励
    //                 continue;
    //             }
    //             if($agent_level_list[$parent_info['user_agent_id']]['has_reward'] == 1){
    //                 //该等级已经奖励过，不再奖励
    //                 continue;
    //             }
    //             if(sprintf("%.2f", $agent_level_list[$parent_info['user_agent_id']]['amount']) <= 0){
    //                 //奖励金额小于等于0，不进行奖励
    //                 continue;
    //             }
    //             //进行奖励
    //             $this->add_sale($id, $agent_level_list[$parent_info['user_agent_id']]['amount'], $parent_info['id'], 2, $agent_level_list[$parent_info['user_agent_id']]['name'].'提成，来源用户'.$user_data['username'], $user_data['id'] );
    //             $user_reward[] = [
    //                 'user_id' => $parent_info['id'],
    //                 'money' => $agent_level_list[$parent_info['user_agent_id']]['amount'],
    //             ];
    //             //增加奖励次数
    //             $reward_num++;
    //             //奖励过的这个等级，修改为已经奖励过了
    //             $agent_level_list[$parent_info['user_agent_id']]['has_reward'] = 1;
    //         }
    //     }
    //     //等级分佣奖励
    //     if($is_open_level_reward){
    //         //一级分佣
    //         if($user_data['first_parent'] >= 0){
    //             //获取一级用户信息
    //             $first_parent_info = $user_model->where('id',$user_data['first_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
    //             //用户存在
    //             if($first_parent_info){
    //                 //上级会员等级要大于自己的会员等级
    //                 if($first_parent_info['user_level_hidden_id'] >= $user_data['user_level_hidden_id']){
    //                     //上级必须不是普通会员
    //                     if($first_parent_info['user_level_hidden_id'] != '1'){
    //                         //查询等级奖励比例
    //                         $first_rate = $user_level_model->where('id',$first_parent_info['user_level_hidden_id'])->value('first_task_reward');
    //                         //计算奖励金额
    //                         $first_reward_amount = $_data['reward_amount'] * $first_rate;
    //                         //奖励金额不小于等于0，进行奖励
    //                         if(sprintf("%.2f", $first_reward_amount) <= 0){
    //                             //进行奖励
    //                             $this->add_sale($id, $first_reward_amount, $user_data['first_parent'], 2, '一级提成，来源用户'.$user_data['username'], $user_data['id'] );
    //                             $user_reward[] = [
    //                                 'user_id' => $user_data['first_parent'],
    //                                 'money' => $first_reward_amount,
    //                             ];
    //                         }
    //                     }
    //                 }
    //             }
                
    //         }
    //         //二级分佣
    //         if($user_data['second_parent'] >= 0){
    //             //获取一级用户信息
    //             $second_parent_info = $user_model->where('id',$user_data['second_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
    //             //用户存在
    //             if($second_parent_info){
    //                 //上级会员等级要大于自己的会员等级
    //                 if($second_parent_info['user_level_hidden_id'] >= $user_data['user_level_hidden_id']){
    //                     //上级必须不是普通会员
    //                     if($second_parent_info['user_level_hidden_id'] != '1'){
    //                         //查询等级奖励比例
    //                         $second_rate = $user_level_model->where('id',$second_parent_info['user_level_hidden_id'])->value('second_task_reward');
    //                         //计算奖励金额
    //                         $second_reward_amount = $_data['reward_amount'] * $second_rate;
    //                         //奖励金额不小于等于0，进行奖励
    //                         if(sprintf("%.2f", $second_reward_amount) <= 0){
    //                             //进行奖励
    //                             $this->add_sale($id, $second_reward_amount, $user_data['second_parent'], 2, '二级提成，来源用户'.$user_data['username'], $user_data['id'] );
    //                             $user_reward[] = [
    //                                 'user_id' => $user_data['second_parent'],
    //                                 'money' => $second_reward_amount,
    //                             ];
    //                         }
                            
    //                     }
    //                 }
    //             }
    //         }
    //         //三级分佣
    //         if($user_data['third_parent'] >= 0){
    //             //获取一级用户信息
    //             $third_parent_info = $user_model->where('id',$user_data['third_parent'])->where('is_delete','0')->where('status','1')->field('id,user_level_hidden_id')->find();
    //             //用户存在
    //             if($third_parent_info){
    //                 //上级会员等级要大于自己的会员等级
    //                 if($third_parent_info['user_level_hidden_id'] >= $user_data['user_level_hidden_id']){
    //                     //上级必须不是普通会员
    //                     if($third_parent_info['user_level_hidden_id'] != '1'){
    //                         //查询等级奖励比例
    //                         $third_rate = $user_level_model->where('id',$third_parent_info['user_level_hidden_id'])->value('third_task_reward');
    //                         //计算奖励金额
    //                         $third_reward_amount = $_data['reward_amount'] * $third_rate;
    //                         //奖励金额不小于等于0，进行奖励
    //                         if(sprintf("%.2f", $third_reward_amount) <= 0){
    //                             //进行奖励
    //                             $this->add_sale($id, $third_reward_amount, $user_data['third_parent'], 2, '三级提成，来源用户'.$user_data['username'], $user_data['id'] );
    //                             $user_reward[] = [
    //                                 'user_id' => $user_data['third_parent'],
    //                                 'money' => $third_reward_amount,
    //                             ];
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //     }

    //     return $user_reward;
    // }

    // /**
    //  * 直接奖励和分佣奖励记录
    //  *
    //  * @param [type] $apply_id
    //  * @param [type] $price
    //  * @param [type] $user_id
    //  * @param [type] $type
    //  * @param [type] $remark
    //  * @param [type] $from_user_id
    //  * @return void
    //  */
    // private function add_sale($apply_id, $price, $user_id, $type, $remark, $from_user_id)
    // {
    //     //添加直销收入记录
    //     $data['user_id'] = $user_id;
    //     $data['from_user_id'] = $from_user_id;
    //     $data['apply_id'] = $apply_id;
    //     $data['price'] = $price;
    //     $data['remark'] = $remark;
    //     $data['create_time'] = time();
    //     $data['type'] = $type;
    //     $result = model('app\admin\model\task\TaskSale')->create($data);
    //     if( $result ) {
    //         //添加金额变动记录
    //         model('app\admin\model\User')->incPrice($user_id, $price, $type, $remark, $result->id);
    //     } else {
    //         throw new Exception('添加收益失败');
    //     }
    // }



}
