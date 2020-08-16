<?php

namespace app\admin\controller\task;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\library\Task as TaskLibrary;
/**
 * 任务需求管理
 *
 * @icon fa fa-circle-o
 */
class Task extends Backend
{
    
    /**
     * Task模型对象
     * @var \app\admin\model\task\Task
     */
    protected $model = null;
    protected $noNeedRight = ['multi'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\task\Task;
        $this->view->assign("isUserList", $this->model->getIsUserList());
        $this->view->assign("statusList", $this->model->getStatusList());

        $this->softDel = true;
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
        //当前是否为关联查询
        $this->relationSearch = true;
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
                    ->with(['taskcategory','taskplatform','userlevel','user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['taskcategory','taskplatform','userlevel','user'])
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    //判断普通任务还是钻石任务
                    $task_user_level_list = explode(',', $params['user_level_id']);
                    if(in_array('8',$task_user_level_list)){
                        $params['type'] = '2';
                    }else{
                        $params['type'] = '1';
                    }
                    $result = $this->model->allowField(true)->create($params);
                    Db::commit();
                    if(strtotime($params['end_time']) >= time()){   //&& $params['status'] == '1' //TODO CK调试
                        //增加成功，生成redis缓存
                        $task_library = new TaskLibrary();
                        $task_library->updatePlatformTaskSet('1',$result->id);
                    }
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error('模型验证'.$e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error('PDO验证'.$e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error('系统报错'.$e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
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
                    //判断普通任务还是钻石任务
                    $task_user_level_list = explode(',', $params['user_level_id']);
                    if(in_array('8',$task_user_level_list)){
                        $params['type'] = '2';
                    }else{
                        $params['type'] = '1';
                    }
                    if(strtotime($params['end_time']) >= time() && $row->max_apply_num > $row->apply_num && $row->status == '1'){
                        //增加成功，生成redis缓存
                        $task_library = new TaskLibrary();
                        $task_library->updatePlatformTaskSet('1',$row->id);
                        $params['is_complete'] = '0';
                    }
                    //判断url是否有效
                    if (preg_match('/(https?|http|ftp|file):\/\/[-A-Za-z0-9+&@#\/\%\?=~_|!:,.;]+[-A-Za-z0-9+&@#\/\%=~_|]/', $params['video_url'], $result)){
                        $params['effective_url'] = 1;
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
     * 批量更新
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values || $this->auth->isSuperAdmin()) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                        foreach ($list as $index => $item) {
                            if(isset($values['status']) ){
                                $task_library = new TaskLibrary();
                                if($values['status'] == 1){
                                    //分开判断，防止影响了定时器
                                    if($item->end_time >= time() && $item->max_apply_num > $item->apply_num && $item->is_complete == '0'){
                                        //开启缓存
                                        $task_library->updatePlatformTaskSet('1',$item->id);
                                    }
                                }else{
                                    //关闭缓存
                                    $task_library->updatePlatformTaskSet('0',$item->id);
                                }
                            }
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
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }


    /**
     * 快速复制
     *
     * @param [type] $ids
     * @return void
     */
    public function copy($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('任务不存在'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $task = [];
                $task['type'] = $row->type;
                $task['info_type'] = $row->info_type;
                $task['task_category_id'] = $row->task_category_id;
                $task['task_platform_id'] = $row->task_platform_id;
                $task['user_level_id'] = $row->user_level_id;
                $task['is_user'] = 0;
                $task['user_id'] = 0;
                $task['name'] = $row->name;
                $task['describe'] = $row->describe;
                $task['cover'] = $row->cover;
                $task['reward_amount'] = $row->reward_amount;
                $task['video_name'] = $row->video_name;
                $task['video_url'] = $row->video_url;
                $task['max_apply_num'] = $row->max_apply_num;
                $task['end_time'] = $row->end_time;
                $task['status'] = $row->status;
                $result = $this->model->create($task);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                if($task['end_time'] >= time() && $task['status'] == '1'){
                    //增加成功，生成redis缓存
                    $task_library = new TaskLibrary();
                    $task_library->updatePlatformTaskSet('1',$result->id);
                }
                $this->success('复制成功');
            } else {
                $this->error('复制失败');
            }
        }
        $this->error('系统错误');
    }


}
