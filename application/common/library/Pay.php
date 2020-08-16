<?php

namespace app\common\library;


use app\common\model\Config as ConfigModel;

class Pay
{
    
    public $_error = '';

    private $appid;
    private $private_key;
    private $pay_url;

    public function __construct()
    {
        $this->appid = ConfigModel::where('name', 'pay_appid')->value('value');
        $this->private_key = ConfigModel::where('name', 'pay_private_key')->value('value');
        $this->pay_url = ConfigModel::where('name', 'pay_url')->value('value');
    }


  private function writelog($text, $aType) 
  { 
    if (! empty ( $text )) 
    {
        $fileType = mb_detect_encoding ( $text, array (
                'UTF-8',
                'GBK',
                'GB2312',
                'LATIN1',
                'BIG5' 
        ) );

        if ($fileType != 'UTF-8') 
        {
           $text = mb_convert_encoding ( $text, 'UTF-8', $fileType );
        }
		file_put_contents (dirname ( __FILE__ )."/log_".$aType. date( "Y-m-d" ).".txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
    }
   
  } 

    public function juhePay($params, $type){

	$juhe = new Juhe($params);
    $res = $juhe->getPayUrl($type, $params['price'], $params['extends_param'], $params['notifyurl']);
    
    if(is_object($res)){
        $code = $res->code;
    }else{
        $code = $res['code'];
    }

	if($code != 1001){
            $this->_error = is_object($res) ? $res->info : $res['info'];
            return false;
	}
	return is_object($res->data->payUrl) ? $res->data->payUrl : $res['data']['payUrl'];
    }
    //
    public function pay($params, $type){
 
         //$this->_error = '系统维护中';
         //   return false;
        // 接口地址 URL
        $gateway = $this->pay_url . '/MyBank/Handler/addPayChannelRecord.ashx';
        
       // $gateway='http://b.bs2021.com/MyBank/Handler/addPayChannelRecord.ashx';   //接口地址 URL 换在这
        $tmp_param_arr['appid'] =  $this->appid;

        $tmp_param_arr['timestamp'] = time();
        $tmp_param_arr['orderid'] = $params['sn'];
        $tmp_param_arr['channel'] = $params['type'];
        $tmp_param_arr['subject'] = $params['subject'];
        $tmp_param_arr['price'] = $params['price'];
        $tmp_param_arr['notifyurl'] = $params['notifyurl']; //根据实际情况修改;
        $tmp_param_arr['extends_param'] = $params['extends_param'];
        $tmp_param_arr['sign'] = md5($tmp_param_arr['appid'].$tmp_param_arr['timestamp'].$tmp_param_arr['orderid'].$tmp_param_arr['channel'].$tmp_param_arr['subject'].$tmp_param_arr['price'].$tmp_param_arr['notifyurl'].$tmp_param_arr['extends_param'].$this->private_key);

        //记录提交日志
        //file_put_contents('post.txt', $tmp_param_arr['timestamp'].':'.$params['sn'].'---->'.json_encode($tmp_param_arr).PHP_EOL, FILE_APPEND);

        //$res = post($gateway, $tmp_param_arr);
        //TODO CK调试
        $res = '{"data":{"url":"http://h5.pienvo.cn:11000/?OrderCode='.$tmp_param_arr['orderid'].'","merchant_id": "'.$tmp_param_arr['orderid'].'","h5_pay_url": "https://qr.alipay.com/'.$tmp_param_arr['orderid'].'"},"msg": "下单成功","code": "ok"}';
        $this->writelog($res,'xiadan');

        //记录提交结果日志
        //file_put_contents('post_result.txt', $tmp_param_arr['timestamp'].':'.$params['sn'].'---->'.$res.PHP_EOL, FILE_APPEND);

       // $this->_error = $res; 
       // return false;
        $result = json_decode($res,true);
        // if ($recharge['member_id'] == 10025) {
        // 	var_dump( $result);
        // }

        if ($result['code'] == 'ok') {
            if ($type == 1) {
                return $result['data']['h5_pay_url'];
            } else {
                return $result['data']['url'];
            }
        } else {
            $this->_error = $result['msg'];
            return false;
        }

    }




    public function notifyMeal($params_arr){
        //验证签名
        $md5_str = md5($this->appid.$params_arr['channel'].$params_arr['extends_param'].$params_arr['price'].$params_arr['id'].$params_arr['orderid'].$params_arr['timestamp'].$this->private_key);
        if ($md5_str == $params_arr['sign']) {

            //执行支付处理
            $pay_type = $params_arr['channel']==1?'alipay':'weixin';
            $mealDealLib = (new MealDeal());
            $res = $mealDealLib->completeMeal($params_arr['extends_param'], $params_arr['id'], $pay_type);
            //记录日志
          //  file_put_contents('notify_result.txt', $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$mealDealLib->_error.PHP_EOL, FILE_APPEND);
            return $res;
        } else {
            //记录日志
            file_put_contents('notify_fail.txt',  $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$md5_str.'==='.$params_arr['sign'].PHP_EOL, FILE_APPEND);
            return false;
        }
    }


    public function notifyStoreMeal($params_arr){
        //验证签名
        $md5_str = md5($this->appid.$params_arr['channel'].$params_arr['extends_param'].$params_arr['price'].$params_arr['id'].$params_arr['orderid'].$params_arr['timestamp'].$this->private_key);
        if ($md5_str == $params_arr['sign']) {

            //执行支付处理
            $pay_type = $params_arr['channel']==1?'alipay':'weixin';
            $StoreMealLib = (new StoreMeal());
            $res = $StoreMealLib->successStoreMealOrder($params_arr['extends_param'], $params_arr['id'], $pay_type);
            //记录日志
            file_put_contents('notify_result_store.txt', $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$StoreMealLib->_error.PHP_EOL, FILE_APPEND);
            return $res;
        } else {
            //记录日志
            file_put_contents('notify_fail_store.txt',  $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$md5_str.'==='.$params_arr['sign'].PHP_EOL, FILE_APPEND);
            return false;
        }
    }


    public function notifyProductOrder($params_arr){
        //验证签名
        $md5_str = md5($this->appid.$params_arr['channel'].$params_arr['extends_param'].$params_arr['price'].$params_arr['id'].$params_arr['orderid'].$params_arr['timestamp'].$this->private_key);
        if ($md5_str == $params_arr['sign']) {

            //执行支付处理
            $pay_type = $params_arr['channel']==1?'alipay':'weixin';
            $ProductOrderLib = (new ProductOrder());
            $res = $ProductOrderLib->successOrder($params_arr['extends_param'], $params_arr['id'], $pay_type);
            //记录日志
            file_put_contents('notify_result_product.txt', $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$ProductOrderLib->_error.PHP_EOL, FILE_APPEND);
            return $res;
        } else {
            //记录日志
            file_put_contents('notify_fail_product.txt',  $params_arr['timestamp'].':'.$params_arr['orderid'].'---->'.$md5_str.'==='.$params_arr['sign'].PHP_EOL, FILE_APPEND);
            return false;
        }
    }

}




class Juhe {
	var $baseUrl = 'http://pay.yk8.vip:58080/Api';
	var $cburl = 'http://127.0.0.1/pay/cb';

	function __construct($config) {
		if(isset($config['cburl']))
			$this->cburl = $config['cburl'];
	}
	function getPayUrl($vendor, $amount, $orderSn, $notify){
		$res = file_get_contents($this->baseUrl."/pay?vendor=".$vendor."&amount=".$amount."&orderId=".$orderSn);
//		var_dump($res);
		if($res){
			$res = json_decode($res);
		}
		return $res;
	}
}
