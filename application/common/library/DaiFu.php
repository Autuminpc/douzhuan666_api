<?php

namespace app\common\library;
use app\admin\model\finance\Withdraw;
use app\common\model\Config as ConfigModel;

class DaiFu{

    private $appid;
    private $private_key;
    private $pay_url;

    public function __construct()
    {
        $this->appid = ConfigModel::where('name', 'pay_appid')->value('value');
        $this->private_key = ConfigModel::where('name', 'pay_private_key')->value('value');
        $this->pay_url = ConfigModel::where('name', 'pay_url')->value('value');
    }


    public function submit($id, $sn){
        
    	$withdraw_data = Withdraw::get($id);
        $appid = $this->appid;
    	$key = $this->private_key;
        
    	$timestamp = time();
    	$orderid = $sn;
    	$price = $withdraw_data['arrival_amount'];
    	$Accountname = $withdraw_data['bank_user'];
        $Accountcardno = $withdraw_data['bank_number'];
        
    	$notifyurl = absolutePath(url('index/index/daifuNotify'));
    	$extends_param = $id;
    	$sign = md5($appid.$timestamp.$orderid.$price.$Accountname.$Accountcardno.$notifyurl.$extends_param.$key);
    	$gateway = $this->pay_url.'/MyBank/Handler/BillPaying.ashx';
        $tmp_param_arr['appid'] =  $appid;
        $tmp_param_arr['timestamp'] = $timestamp;
        $tmp_param_arr['orderid'] = $orderid;
        $tmp_param_arr['price'] = $price;
        $tmp_param_arr['Accountname'] = $Accountname;
        $tmp_param_arr['Accountcardno'] = $Accountcardno;
        $tmp_param_arr['notifyurl'] = $notifyurl; //根据实际情况修改;
        $tmp_param_arr['extends_param'] = $extends_param;
		$tmp_param_arr['sign'] = $sign;

      	$res = $this->postForm($gateway, $tmp_param_arr);
        file_put_contents('tixian_result.txt', $res.PHP_EOL, FILE_APPEND);
        $result = json_decode($res,true);
		$tmp_param_arr['result'] = $result;
		file_put_contents('tixian_post_result.txt', json_encode($tmp_param_arr).PHP_EOL, FILE_APPEND);
		
        return $result;
    }

    public static function postForm($url, $data, $headers = array(), $referer = NULL) {
		$headerArr = array();
		if (is_array($headers)) {
			foreach ($headers as $k => $v) {
				$headerArr[] = $k.': '.$v;
			}
		}
		$headerArr[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
		if ($referer) {
			curl_setopt($ch, CURLOPT_REFERER, "http://{$referer}/");
		}
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
}