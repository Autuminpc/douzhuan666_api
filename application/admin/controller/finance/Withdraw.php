<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\Config as ConfigModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\library\DaiFu;
use app\admin\model\User;
use app\common\library\alipay\AlipayTransfer;
use think\Session;
use app\admin\model\Admin;

/**
 * 提现管理
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{
    
    /**
     * Withdraw模型对象
     * @var \app\admin\model\finance\Withdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\finance\Withdraw;
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
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();
            $total_amount = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->sum('amount');
            $total_service_amount = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->sum('service_amount');
            $total_arrival_amount = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->sum('arrival_amount');
            $list = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $aAdminTmpList = Admin::field('id,nickname')->select();
            $aAdminList = [];
            foreach ($aAdminTmpList as $row) {
                $aAdminList[$row['id']] = $row['nickname'];
            }

            foreach ($list as $key => &$row) {
                $list[$key]['admin_name'] = $row['verify_adminid'] ? $aAdminList[$row['verify_adminid']] : '';
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list, "extend" => ['total_amount' => $total_amount,'total_service_amount' => $total_service_amount,'total_arrival_amount' => $total_arrival_amount]);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 审核操作
     */
    public function verify_withdraw($ids){
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

                    //提现审核渠道
                    $payTypeCode = ConfigModel::where('name', 'withdraw_type_code')->value('value');
                    if(!$payTypeCode){
                        $this->error(__('代付渠道关闭中无法代付'));
                    }

                    //代付
                    if($params['status'] == '1'){
                        //限制只有待处理和打款失败才可以代付
                        if(!in_array($row->status,['0','2'])){
                            $this->error(__('只有待处理或打款失败的申请才可以进行代付'));
                        }
                        //生成代付订单号
                        $params['order_no'] = str_pad($row->id.$row->create_time,15,'0',STR_PAD_RIGHT).rand(1000,9999);

                        if($payTypeCode == 'alipay'){
                            //支付宝下发渠道
                            $daifu_res = (new AlipayTransfer())->doPay($row->id, $params['order_no']);
                            $daifu_res = $daifu_res['alipay_fund_trans_toaccount_transfer_response'];
                            if($daifu_res['code'] && $daifu_res['code']!='10000'){
                                $date = date('ymd');
                                dlog($daifu_res, 'log/withdraw_auto/error_'.$date.'.log', 1);
                                $this->error($daifu_res['msg'].' : '.$daifu_res['sub_msg']);
                            }
                            $params['status'] = 3;
                            $params['arrival_time'] = time();
                        }else{
                            //提交代付
                            $daifu_res = (new DaiFu())->submit($row->id, $params['order_no']);
                            if($daifu_res['code'] == 'error'){
                                $this->error($daifu_res['msg']);
                            }
                        }


                        //记录代付结果
                        $params['daifu_res'] = json_encode($daifu_res);
                    }
                    $params['verify_time'] = time();
                    //提现驳回，需要返回金额生成记录
                    if($params['status'] == 4){
                        (new User())->incPrice($row->user_id, $row->amount, 7, '提现审核不通过，返回资金');
                    }
                    $params['verify_adminid'] = Session::get('admin')['id'];
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error(1);
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error(2);
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error(3);
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
     * 批量代付
     */
    public function multi_daifu($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            $count = 0;
            Db::startTrans();
            try {
                $time = time();
                $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                foreach ($list as $key => $value) {
                    //限制只有待处理和打款失败才可以代付
                    if(!in_array($value->status,['0','2'])){
                        $this->error('ID:'. $value->id .'不满足条件，只有待处理或打款失败的申请才可以进行代付');
                    }
                }
                foreach ($list as $index => $item) {
                    $values['status'] = '1';
                    $values['verify_mark'] = '';
                    //生成代付订单号
                    $values['order_no'] = str_pad($item->id.$item->create_time,15,'0',STR_PAD_RIGHT).rand(1000,9999);
                    //提交代付
                    $daifu_res = [];
                    $daifu_library = new DaiFu();
                    $daifu_res = $daifu_library->submit($item->id, $values['order_no']);
                    //记录代付结果
                    $values['daifu_res'] = json_encode($daifu_res);
                    $values['verify_time'] = $time;
                    if($daifu_res['code'] == 'error'){
                        $values['status'] = '2';
                        $values['verify_mark'] = $daifu_res['msg'];
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
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
