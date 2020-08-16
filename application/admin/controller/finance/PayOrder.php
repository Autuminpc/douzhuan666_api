<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;

use app\common\library\MealDeal as MealDeal;
use app\common\library\StoreMeal as StoreMeal;
use app\common\library\ProductOrder as ProductOrder;


/**
 * 会员充值订单管理
 *
 * @icon fa fa-circle-o
 */
class PayOrder extends Backend
{
    
    /**
     * PayOrder模型对象
     * @var \app\admin\model\finance\PayOrder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\finance\PayOrder;
        $this->view->assign("orderTypeList", $this->model->getOrderTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());

        $this->softDel = true;
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
    public function changeStatus()
    {
	 $id = $this->request->request('id');
	 $orderType = $this->request->request('order_type');
	 $userId = $this->request->request('user_id');
	 $mealId = $this->request->request('meal_id');
	 $orderNo = $this->request->request('order_no');
	 $payAmount = $this->request->request('pay_amount');
	 $status = $this->request->request('status');
	 $res = false;
	 $err = '';
	 if($orderType == 1){
                $mealDealLib = (new MealDeal());
	 	$res = $mealDealLib->completeMeal($id, $id, 'admin');
		if(!$res)
			return json(["code"=>"-1", "info"=>$mealDealLib->_error]);
	 }
	 if($orderType == 3){
		 $StoreMealLib = (new StoreMeal());
		 $res = $StoreMealLib->successStoreMealOrder($id, $id, 'admin');
		if(!$res)
			return json(["code"=>"-1", "info"=>$StoreMealLib->_error]);
	 }
	 if($orderType == 2){
		 $ProductOrderLib = (new ProductOrder());
		 $res = $ProductOrderLib->successOrder($id, $id, 'admin');
		if(!$res)
			return json(["code"=>"-1", "info"=>$ProductOrderLib->_error]);
	 }
	 return json(["code"=>"1", "info"=>"ok"]);
    }

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
                        ->with(['user','meal','storemeal'])
                        ->where($where)
                  //      ->where('pay_order.status = 1')
                        ->order($sort, $order)
                        ->count();
                $total_amount = $this->model->with(['user','meal','storemeal'])->where($where)->where('pay_order.status = 1')->sum('pay_order.pay_amount');

            }else{
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();
                $total = $this->model
                        // ->with(['user','meal'])
                        ->where($where)
                    //    ->where('status = 1')
                        ->order($sort, $order)
                        ->count();
                $total_amount = $this->model->where($where)->where('status = 1')->sum('pay_amount');
                
                //当前是否为关联查询
                $this->relationSearch = true;
                list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            }
            $list = $this->model
                    ->with(['user','meal','storemeal'])
                    ->where($where)
                  //  ->where('pay_order.status = 1')
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
