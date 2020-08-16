<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Task as taskLibrary;
use app\common\model\Config as ConfigModel;
use think\Db;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';
    protected $old_db = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        //$config = get_addon_config('alioss');
        //var_dump($config);exit;
        //return $this->fetch();
        return '';
    }
    public function daifuNotify(){

        $private_key = ConfigModel::where('name', 'pay_private_key')->value('value');
        file_put_contents('tixian_notify_post.log', json_encode($_POST).PHP_EOL, FILE_APPEND);
    	file_put_contents('tixian_notify_get.log', json_encode($_GET).PHP_EOL, FILE_APPEND);

    	$sign = md5($_POST['extends_param'].$_POST['respCode'].$_POST['respMsg'].$_POST['price'].$_POST['id'].$_POST['orderid'].$_POST['endtime'].$private_key);

    	if ($sign != $_POST['sign']) {
    		file_put_contents('tixian_notify_fial.log', json_encode($_POST).PHP_EOL, FILE_APPEND);
    		exit('success');
    	}
    	$sn = $_POST['orderid'];
    	$tixian = model('app\admin\model\finance\Withdraw')->where(['order_no' => $sn])->find();

    	if (!in_array($tixian['status'],[1,2])) {
    		file_put_contents('tixian_notify_re.log', json_encode($_POST).PHP_EOL, FILE_APPEND);
    		exit('success');
    	}
		$id = $tixian['id'];
    	//回调成功
    	if ($_POST['respCode'] == '00') {

    		$data['id'] = $id;
    		$data['status'] = 3;
    		$data['arrival_time'] = time();


    		$result = model('app\admin\model\finance\Withdraw')->isUpdate(true)->save($data);
    		if ($result) {

	            //消息通知
	            $old_date = date('Y-m-d', $tixian['create_time']);
	            $price = abs($tixian['amount']);
                $msg = "您于{$old_date}申请提现{$price}元已通过审核。";

	            $noticeModel = model('app\common\model\Message');
                $message['user_id'] = $tixian['user_id'];
                $message['content'] = $msg;
	            $noticeModel->save($message);
	        }
    	} else { //回调失败

    		$data['id'] = $id;
    		$data['status'] = 2;
    		$data['verify_time'] = time();
            $data['daifu_res'] = json_encode($_POST);
            $data['verify_mark'] = $_POST['respMsg'];
            
            if($_POST['respMsg'] == '余额不足'){
                $data['verify_mark'] = '系统错误';
            }

    		model('app\admin\model\finance\Withdraw')->isUpdate(true)->save($data);

    	}

    	exit('success');
    }

    /**
     * 更新任务过期状态
     * @return void
     */
    public function check_task(){
        //找出所有已经过期、领取完、删除的任务
        $now_time = time();
        $task_model = model('app\admin\model\task\Task');
        $where = "(end_time <= {$now_time} Or is_delete=1 Or max_apply_num<=apply_num) And is_complete=0";
        $end_list = $task_model->where($where)->column('id');
        if(!$end_list){
            exit('暂无可更新数据');
        }
        //更新为已经完成
        $task_model->where('id','in',$end_list)->update(['is_complete'=>'1']);
        $task_library = new taskLibrary();
        foreach ($end_list as $key => $value) {
            $task_library->updatePlatformTaskSet(0,$value);
        }
        exit('操作成功');
    }



}
