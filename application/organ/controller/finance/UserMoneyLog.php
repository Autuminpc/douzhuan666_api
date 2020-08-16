<?php

namespace app\organ\controller\finance;

use app\common\controller\Organend;

/**
 * 会员积分变动管理
 *
 * @icon fa fa-circle-o
 */
class UserMoneyLog extends Organend
{
    
    /**
     * UserMoneyLog模型对象
     * @var \app\admin\model\finance\UserMoneyLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\finance\UserMoneyLog;
        $this->view->assign("typeList", $this->model->getTypeList());

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

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            if(isset(json_decode($this->request->param('filter'),true)['user.username'])){
                //当前是否为关联查询
                $this->relationSearch = true;
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();
                $total = $this->model
                        ->with(['user'])
                        ->where('user.id',$this->auth->id)
                        ->where($where)
                        ->order($sort, $order)
                        ->count();
                $total_amount = $this->model
                        ->with(['user'])
                        ->where($where)
                        ->where('user.id',$this->auth->id)
                        ->sum('user_money_log.money');
            }else{
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();
                $total = $this->model
                        // ->with(['user'])
                        ->where($where)
                        ->where('user_id',$this->auth->id)
                        ->order($sort, $order)
                        ->count();
                $total_amount = $this->model
                        ->where($where)
                        ->where('user_id',$this->auth->id)
                        ->sum('money');
                //当前是否为关联查询
                $this->relationSearch = true;
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            }
            

            $list = $this->model
                    ->with(['user'])
                    ->where('user.id',$this->auth->id)
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $row) {
                
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list, "extend" => ['total_amount' => $total_amount]);

            return json($result);
        }
        return $this->view->fetch();
    }
}
