<?php

namespace app\api\controller;
use think\Db;
use app\common\controller\Api;
use app\common\library\Pay as PayLib;

use app\common\library\MealDeal as MealDeal;
use app\common\library\StoreMeal as StoreMeal;
use app\common\library\ProductOrder as ProductOrder;

use app\common\model\PayOrder as PayOrderModel;

/**
 * 首页接口
 */
class Pay extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }


    
private function getConfig(){
	$config = [
		["code"=>"wwf_al", "type"=>"alipay", "remark"=>"wwf支付宝扫码", "isfixed"=>0, "state"=>0, "amounts"=>"50,5000"],
		["code"=>"qiqi_al", "type"=>"alipay", "remark"=>"七七支付宝扫码", "isfixed"=>0, "state"=>0, "amounts"=>"300,5000"],
		["code"=>"qiqi_pdd_al", "type"=>"alipay", "remark"=>"七七支付宝PDD", "isfixed"=>1, "state"=>0, "amounts"=>"299,399,499,599,699,799,899,999,1499,1999,2499,2999,3999,4999"],
		["code"=>"qiqi_pdd_al", "type"=>"alipay", "remark"=>"七七支付宝PDD", "isfixed"=>1, "state"=>0, "amounts"=>"296,387,485,997,1012,1515,1996,2505,2993"],
		["code"=>"wangfu_al", "type"=>"alipay", "remark"=>"网付支付宝扫码", "isfixed"=>0, "state"=>0, "amounts"=>"500,5000"],
		["code"=>"wangfu_h5_al", "type"=>"alipay", "remark"=>"网付支付宝h5", "isfixed"=>1, "state"=>0, "amounts"=>"50,100,200,300,500"],//接口提示最小300
		["code"=>"xunke_h5_al", "type"=>"alipay", "remark"=>"讯科支付宝h5", "isfixed"=>1, "state"=>0, "amounts"=>"300,500,1000"],
		["code"=>"xunke_hb_al", "type"=>"alipay", "remark"=>"讯科支付宝红包", "isfixed"=>1, "state"=>0, "amounts"=>"100,200,300,500,1000"],
		["code"=>"juli_al", "type"=>"alipay", "remark"=>"聚利支付宝h5", "isfixed"=>1, "state"=>0, "amounts"=>"98,198,398,998,1000,2000"],
		["code"=>"juli_wx", "type"=>"wxpay", "remark"=>"聚利微信h5", "isfixed"=>1, "state"=>0, "amounts"=>"98,198,398,998,1000,2000"],
	];
	return $config;
}


private function getPayTypeByAmount($amount){
	$res = [];
	$mis = 6;
	$config = $this->getConfig();
	foreach($config as $line){
		if($line['state'] != 1)continue;
		$arr = explode(",", $line['amounts']);
		if($line['isfixed'] == 0){
			if($amount > $arr[0] && $amount < $arr[1]){
				array_push($res, ["code"=>$line['code'], "type"=>$line['type'], "isfixed"=>$line['isfixed'], "amount"=>$amount]);	
			}
		}else{
			for($i = 0; $i < count($arr); $i++){
				if(abs($arr[$i] - $amount) < $mis){
					array_push($res, ["code"=>$line['code'], "type"=>$line['type'], "isfixed"=>$line['isfixed'], "amount"=>$arr[$i]]);	
				}
			}
		}
	}
	return $res;
}

private function getIconByType($type){

	if($type == 'alipay')
		return absolutePath('/assets/img/alipay.jpg');
	return absolutePath('/assets/img/wxpay.jpg');
}

//var_dump(getPayTypeByAmount(398));


    public function payType(){
        //类型
        $type = $this->request->post('type');
        $order_id = $this->request->post('id');

        $list = [

            // [
            //     'type' => 1,//wwf 扫码 300-10000
            //     'text' => '支付宝3',
            //     'image' => absolutePath('/assets/img/alipay.jpg')
            // ],
            // [
            //     'type' => 2,
            //     'text' => '微信支付',
            //     'image' => absolutePath('/assets/img/wxpay.jpg')
            // ]
        ];
	$order = PayOrderModel::where('id', $order_id)->find();
	$payList = $this->getPayTypeByAmount($order['pay_amount']);


	foreach($payList as $line){
		array_push($list, [
					'type'=>$line['code'], 
					'text'=>($line['type']=='alipay'?'支付宝':'微信') .'-'. ($line['isfixed']?'':'扫码').' (实付: '.$line['amount'].')', 
					'amount'=>$line['amount'],
					'image'=>$this->getIconByType($line['type'])
				]);
	}
    $type = 'score';
        if ($type == 'score') {

            $money = $this->auth->getUser()['money'];
            $arr = [
                'type' => 'score',
                'text' => '余额支付',
                'image' => absolutePath('/assets/img/yue.png'),
                'money' => $money
            ];
            array_push($list, $arr);
        }
        $this->success('请求成功', $list);
    }

    public function notify(){
	    //TODO: 只允许指定IP
	    $id = $_GET['id'];
	    $mealDealLib = (new MealDeal());
	    $res = $mealDealLib->completeMeal($id, $id, 'alipay');
	    var_dump($res);
    }

    //
    public function payyongxinNotify(){
die;
        $post_data =json_encode($_GET);
        file_put_contents('notify_data.txt', $post_data.PHP_EOL, FILE_APPEND);
        if ($post_data) {
            $params_arr = json_decode($post_data, true);

            $PayLib = new PayLib();
            $res = $PayLib->notifyMeal($params_arr);
            if ($res) {

                exit('SUCCESS');
            } else {
                exit('FAIL');
            }
        }
    }

    //店铺充值回调
    public function payyxNotifyStore(){
        $post_data =json_encode($_POST);
        file_put_contents('notify_data_store.txt', $post_data.PHP_EOL, FILE_APPEND);
        if ($post_data) {
            $params_arr = json_decode($post_data, true);

            $PayLib = new PayLib();
            $res = $PayLib->notifyStoreMeal($params_arr);
            if ($res) {

                exit('SUCCESS');
            } else {
                exit('FAIL');
            }
        }
    }

    //订单支付回调
    public function payyxNotifyOrder(){
        $post_data =json_encode($_POST);
        file_put_contents('notify_data_store.txt', $post_data.PHP_EOL, FILE_APPEND);
        if ($post_data) {
            $params_arr = json_decode($post_data, true);

            $PayLib = new PayLib();
            $res = $PayLib->notifyProductOrder($params_arr);
            if ($res) {

                exit('SUCCESS');
            } else {
                exit('FAIL');
            }
        }
    }
}
