<?php

namespace app\common\library\alipay;

use app\common\model\Config as ConfigModel;
use app\admin\model\finance\Withdraw;
use think\exception\HttpResponseException;
use think\Response;

class AlipayTransfer {
    protected $appId;
    //私钥值
    protected $rsaPrivateKey;

    public function __construct() {
        $this->appId         = ConfigModel::where('name', 'alipay_appid')->value('value');
        $this->charset       = 'utf8';
        $this->rsaPrivateKey = ConfigModel::where('name', 'alipay_private_key')->value('value');
    }

    /**
     * 转帐
     * @param float $totalFee 转账金额，单位：元。
     * @param string $outTradeNo 商户转账唯一订单号
     * @param string $remark 转帐备注
     * @return array
     */
    public function doPay($id, $outTradeNo, $remark = '') {
        $withdraw_data = Withdraw::get($id);
        //请求参数
        $requestConfigs        = [
            'out_biz_no'      => $outTradeNo,
            'payee_type'      => 'ALIPAY_LOGONID',
            'payee_account'   => $withdraw_data['bank_number'],
            'payee_real_name' => $withdraw_data['bank_user'],  //收款方真实姓名
            'amount'          => $withdraw_data['arrival_amount'], //转账金额，单位：元。
            'remark'          => $remark,  //转账备注（选填）
        ];
        $commonConfigs         = [
            //公共参数
            'app_id'      => $this->appId,
            'method'      => 'alipay.fund.trans.toaccount.transfer',             //接口名称
            'format'      => 'JSON',
            'charset'     => $this->charset,
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'biz_content' => json_encode($requestConfigs),
        ];
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result                = $this->curlPost('https://openapi.alipay.com/gateway.do', $commonConfigs);
        $resultArr             = json_decode($result, true);
        if (empty($resultArr)) {
            $result = iconv('GBK', 'UTF-8//IGNORE', $result);
            return json_decode($result, true);
        }
        return $resultArr;
    }

    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA") {
        $priKey = $this->rsaPrivateKey;
        $res    = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if(!$res){
            $response = Response::create([
                'code' => 0,
                'msg'  => '您使用的私钥格式错误，请检查RSA私钥配置',
                'data' => [],
                'url'  => '',
                'wait' => 3,
            ], 'json');
            throw new HttpResponseException($response);
        }
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION, '5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i                = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }

    public function curlPost($url = '', $postData = '', $options = array()) {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}