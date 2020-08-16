<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\user\UserLevel;
use app\admin\model\user\Meal as mealModel;
use app\admin\model\finance\PayOrder as payOrderModel;
use app\common\library\MealDeal as mealDealLibrary;
use app\admin\model\store\Store as StoreModel;
use app\admin\model\store\StoreMeal as StoreMealModel;
use app\common\model\StoreMealOrder as StoreMealOrderModel;
use app\common\library\StoreMeal as StoreMealLibrary;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;


    /**
     * @var \app\admin\model\User
     */
    protected $model = null;
    protected $noNeedRight = ['multi'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');

        $this->softDel = true;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $listSql = $this->model
            ->with(['userlevel','useragent'])
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)->fetchSql(true)->select();
            
            $whereHex = md5($listSql);
            //$listCache = cache($whereHex);

            //if($listCache){
                //list($total, $list) = $listCache;
                //$result = array("total" => $total, "rows" => $list);
                //return json($result);
            //};

            
            $total = $this->model
                ->with(['userlevel','useragent'])
                ->where($where)
                ->order($sort, $order)->count();
            
            $list = $this->model
                ->with(['userlevel','useragent'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)->select();

            foreach ($list as $k => $v) {
                $v->hidden(['password', 'salt']);
                //统计团队人数
                $list[$k]['organ_count'] = $this->model->where("find_in_set('" . $v['id'] . "',parent_id) and is_delete=0")->count();
                $list[$k]['organ_count'] -= 1;
                //统计一级团队人数
                $list[$k]['first_count'] = $this->model->where("first_parent",$v['id'])->where('is_delete','0')->count();
                // 是否开通了店铺
                $list[$k]['has_store'] = StoreModel::where('user_id',$v['id'])->where('is_delete','0')->count();
                
            }
            //cache($whereHex, [$total, $list], 300);
            $result = array("total" => $total, "rows" => $list, 'md5'=>$whereHex, 'sql' => $listSql);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
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
                    //验证登陆账号是否已经存在
                    $has = $this->model->where('id','neq',$row->id)->where('username',$params['username'])->value('id');
                    if($has){
                        $this->error('该账号已经存在，无法添加相同账户');
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
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
     * 查看销售团队
     *
     * @param [type] $ids
     * @return void
     */
    public function team($ids){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('用户不存在'));
        }
        //查询一级团队
        $first_list = $this->model->where('first_parent',$row->id)->field('id,nickname,username,create_time')->select();
        $second_list = $this->model->where('second_parent',$row->id)->field('id,nickname,username,create_time')->select();
        $third_list = $this->model->where('third_parent',$row->id)->field('id,nickname,username,create_time')->select();

        $this->assign('first_list',$first_list);
        $this->assign('second_list',$second_list);
        $this->assign('third_list',$third_list);
        // $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * 设置代理等级
     * @param [type] $ids
     * @return void
     */
    public function set_agent($ids){

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
        if ($this->request->isAjax()) {
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
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
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
        $this->assign('row',$row);
        return $this->fetch();
    }


    /**
     * 手动充值余额
     * @param [type] $ids
     * @return void
     */
    public function rechage($ids){
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
        if ($this->request->isAjax()) {
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
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
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
        $this->assign('row',$row);
        return $this->fetch();
    }


    /**
     * 线下充值套餐
     *
     * @param [type] $ids
     * @return void
     */
    public function rechage_meal($ids){
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
        if ($this->request->isAjax()) {
            $meal_id = $this->request->post("meal_id");
            $meal = mealModel::where('is_delete','0')->where('status','1')->where('id',$meal_id)->find();
            if ($meal) {
                //验证套餐是否存在
                Db::startTrans();
                try {
                    //生成充值记录
                    $pay_order_data = [
                        'order_type' => '1',
                        'user_id' => $row->id,
                        'meal_id' => $meal_id,
                        'order_no' => "VIP".$this->create_order_no($row->id),
                        'pay_amount' => $meal['sell_amount'],
                    ];
                    $pay_order = payOrderModel::create($pay_order_data);

                    //调用充值成功回调
                    $pay_no = 'admin'.date('YmdHis',time()).rand(1, 9999);

                    $result = (new mealDealLibrary())->completeMeal($pay_order_data['order_no'], $pay_no, 'admin');
                    
                    Db::commit();
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
            $this->error('套餐不存在');
        }
        $this->assign('row',$row);
        return $this->fetch();
    }
    /**
     * 生成订单号
     * @param [type] $user_id
     * @return void
     */
    private function create_order_no($user_id) {
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn = $yCode[intval(date('Y')) - 2019] . $user_id . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $orderSn;
    }

    /**
     * 设置会员等级
     * @param [type] $ids
     * @return void
     */
    public function set_level($ids){
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
        if ($this->request->isAjax()) {
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
                    //判断会员等级
                    $user_level_list = explode(',',$row->user_level_hidden);
                    if(!in_array($params['user_level_id'],$user_level_list)){
                        //如果以前没有这个等级，则需要把它覆给用户
                        $user_level_list = array_merge($user_level_list,["0" => $params['user_level_id'] ]);
                        //如果用户升级的不是普通会员，则剔除它以前普通会员的身份了
                        if($params['user_level_id'] != '1'){
                            if(in_array('1',$user_level_list)){
                                $key = array_search('1' ,$user_level_list);
                                array_splice($user_level_list,$key,1);
                            }
                        }
                    }
                    //判断用户的最高等级，并保持显示用户的最高等级
                    $system_level_list = UserLevel::where('id','in',$user_level_list)->order('weight desc')->column('id');
                    $params['user_level_id'] = $system_level_list[0];
                    $params['user_level_hidden_id'] = $system_level_list[0];
                    $params['user_level_hidden'] = implode(',',array_reverse($system_level_list));

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
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
        $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * 设置团队
     *
     * @param [type] $ids
     * @return void
     */
    public function set_team($ids){
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
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    
                    //找出上级
                    $first_parent_info = $this->model->where('id',$params['first_parent'])->field('id,parent_id,first_parent,second_parent,third_parent')->find();

                    //更改自己的上级、上上级、parent_id组
                    $this->model->where('id',$row->id)->update(['first_parent'=>$first_parent_info['id'],'second_parent'=>$first_parent_info['first_parent'],'third_parent'=>$first_parent_info['second_parent'],'parent_id'=>$first_parent_info['parent_id'].$row->id.',']);
                    // var_dump($res);
                    //更换直接下级的二级、三级
                    $child_list = $this->model->where('first_parent',$row->id)->column('id');
                    if($child_list){
                        $this->model->where('id','in',$child_list)->update(['second_parent'=>$first_parent_info['id']]);

                        $this->model->where('id','in',$child_list)->update(['third_parent'=>$first_parent_info['parent_id']]);
                    }
                    //更换间接下级的三级
                    $child_list = $this->model->where('second_parent',$row->id)->column('id');
                    if($child_list){
                        $this->model->where('id','in',$child_list)->update(['third_parent'=>$first_parent_info['id']]);
                    }

                    //找出所有属于自己的所有下级
                    $all_child_list = $this->model->where("find_in_set('" . $row->id . "',parent_id)")->where('id','neq',$row->id)->column('id');
                    if($all_child_list){
                        //更改所有下级的parent_id组信息
                        foreach ($all_child_list as $item) {
                            $child_user_parent_id = $this->model->where('id',$item)->value('parent_id');
                            $temp = explode($row->id.',',$child_user_parent_id);
                            if(!isset($temp[1])){
                                // var_dump(1);
                                continue;
                                // $this->error('系统错误');
                            }
                            $new_parent_id = $first_parent_info['parent_id'].$row->id.','.$temp[1];

                            $this->model->where('id',$item)->update(['parent_id'=>$new_parent_id]);
                        }
                    }
                    $result = true;
                    // exit();
                    // $this->change_team();
                    // $result = $row->allowField(true)->save($params);
                    Db::commit();
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
        $this->assign('row',$row);
        return $this->fetch();
    }
    
    /**
     * 开通店铺
     * @param [type] $ids
     * @return void
     */
    public function add_store($ids){
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
        if ($this->request->isAjax()) {
            $params = $this->request->post("row/a");
            // if ($params) {
                // $params = $this->preExcludeFields($params);
                //验证用户是否已经开通了店铺
                $store = StoreModel::where('user_id',$ids)->where('is_delete','0')->find();
                if($store){
                    $this->error('改用户已经开通了店铺，无需重复开通');
                }
                $result = false;
                Db::startTrans();
                try {    
                    //购买套餐，暂且固定为id是1
                    $store_meal = StoreMealModel::where([
                        'id' => 1,
                        'status' => 1,
                        'is_delete' =>0
                    ])->find();

                    if (!$store_meal) {
                        throw new Exception('商城套餐不存在');
                    }

                    //生成订单
                    $store_meal_order_data = [
                        'user_id' => $ids,
                        'sn' => makeOrderNo(),
                        'store_meal_id' => $store_meal['id'], //暂且固定为id是1
                        'sell_amount' => $store_meal['sell_amount'],
                        'product_public_num' => $store_meal['product_public_num'],
                        // 'store_name' => $params['name'],
                        // 'store_brief' => $params['store_brief'],
                        // 'store_avatar' => $params['avatar'],
                        // 'store_image' => $params['store_image'],
                        // 'service_image' => $params['service_image'],
        
                    ];
                    $store_meal_order = StoreMealOrderModel::create($store_meal_order_data);

                    //模拟回调，进行支付
                    $pay_order_id = $store_meal_order['id'];
                    $pay_no = 'admin'.date('YmdHis',time()).rand(1, 9999);
                    $payment_type = 'admin';
                    (new StoreMealLibrary())->successStoreMealOrder($pay_order_id, $pay_no, $payment_type);

                    $result = true;
                    Db::commit();
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
            // }
            // $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('row',$row);
        return $this->fetch();
    }

}
