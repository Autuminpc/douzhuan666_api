<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Area;
use app\common\model\Version;
use app\common\model\Config as ConfigModel;
use app\common\library\Predis;
use fast\Random;
use think\Config;
use think\Log;
use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    var $yunxin_config=array();
    /**
     * 加载初始化
     *
     * @param string $version 版本号
     * @param string $lng     经度
     * @param string $lat     纬度
     */
    public function init()
    {
        if ($version = $this->request->request('version')) {
            $lng = $this->request->request('lng');
            $lat = $this->request->request('lat');
            $content = [
                'citydata'    => Area::getCityFromLngLat($lng, $lat),
                'versiondata' => Version::check($version),
                'uploaddata'  => Config::get('upload'),
                'coverdata'   => Config::get("cover"),
            ];
            $this->success('', $content);
        } else {
            $this->error(__('Invalid parameters'));
        }
    }

    /**
     * 获取签名
     */
    public function oosParams()
    {
        Config::set('default_return_type', 'json');

        $name = $this->request->post('name');
        $md5 = $this->request->post('md5');
        $auth = new \addons\alioss\library\Auth();
        $params = $auth->params($name, $md5);
        //去除最后一个自负
        $params['key'] = substr($params['key'], 0, -1);
        $params['key'] .= md5(uniqid().rand(0,1000000)).'.';
        $this->success('', $params);
        return;
    }

    //阿里oos上传
    public function oosUpload($object, $filePath){
        $config = get_addon_config('alioss');

        $endpoint = "http://" . $config['endpoint'];

        try {
            $ossClient = new OssClient($config['app_id'], $config['app_key'], $endpoint);
            $ossClient->uploadFile($config['bucket'], $object, $filePath);
        } catch (OssException $e) {
            $this->error(__('Upload successful'), $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }

        $thumb = $this->request->post('thumb')?:0;

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        //禁止上传PHP和HTML文件
        if (in_array($fileInfo['type'], ['text/x-php', 'text/html']) || in_array($suffix, ['php', 'html', 'htm'])) {
            $this->error(__('Uploaded file format is limited'));
        }
        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        //验证是否为图片文件
        $imagewidth = $imageheight = 0;
        if (in_array($fileInfo['type'], ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
            $imgInfo = getimagesize($fileInfo['tmp_name']);
            if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                $this->error(__('Uploaded file is not a valid image'));
            }
            $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
            $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo) {
            $params = array(
                'admin_id'    => 0,
                'user_id'     => (int)$this->auth->id,
                'filesize'    => $fileInfo['size'],
                'imagewidth'  => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $fileInfo['type'],
                'url'         => $uploadDir . $splInfo->getSaveName(),
                'uploadtime'  => time(),
                'storage'     => 'local',
                'sha1'        => $sha1,
            );
            $attachment = model("attachment");
            $attachment->data(array_filter($params));
            $attachment->save();
            \think\Hook::listen("upload_after", $attachment);
            $root_path = ROOT_PATH . '/public' . $uploadDir . $splInfo->getSaveName();
            if ($thumb) {
                //图片压缩处理
                $image = \think\Image::open($root_path);
                $thumb_rate = $image->width() / 200;
                $heigh = $image->width() * $thumb_rate;
                $root_path = ROOT_PATH . '/public' . $uploadDir . 'thumb' . $splInfo->getSaveName();
                $image->thumb(200, $heigh)->save($root_path);
                $url = $uploadDir . 'thumb' . $splInfo->getSaveName();
            } else {
                $url = $uploadDir . $splInfo->getSaveName();
            }

            $res = $this->oosUpload(substr($url, 1, -1), $root_path);
            if ($res) {
                $this->success(__('Upload successful'), [
                    'url' => $url,
                    'absolute_url' => absolutePath($url),
                ]);
            } else {
                $this->error(__('Upload successful'), $url);
            }

        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }



//验证
    public function v5Check($verifyid){
        /*$time = time().'000';
        $v5_token = Predis::getInstance()->get('v5_token');
        //$expiresIn = 3600000;
        $sgin = 'timestamp'.$time.'token'.$v5_token.'verifyid'.$verifyid.'ca585b388e6d4140b7b1296d3e119090';
        $sgin = md5($sgin);
        $resjson = file_get_contents('https://free7jysj6c2.verify5.com/openapi/getToken?verifyid='.$verifyid.'&timestamp='.$time.'&token='.$v5_token.'&signature='.$sgin);
        $res = json_decode($resjson, true);

        return $res;
        */
    }

    //发送短信
    public function sms(){

        $phone = input('phone');

        if (input('self')) {
            $phone = $this->auth->getUser()['username'];
        }

        //判断手机号传
        if (!$phone) {
            $this->error('请输入手机号');
        }

        $code = rand(100000,999999);
        $key="register_".$phone;

        //TODO CK调试
        Predis::getInstance()->set($key, $code, 300);
        
        $this->success('发送成功,验证码是'.$code);

        //$this->v5Check(input('verifyid'));

        //TODO CK调试
        //$res = $this->tianruiSms($phone, $code);
        /*  
            $res = true;
            if (!$res) {
                $sms_data = [
                    'sid' => '',//'e1e56529839caa4248b0f02921642ac0',
                    'token' => '877a81365bd8f1546e90000f6c7dc038',
                    'appid' => '',//'fd72c25d4040477c87fe9678da549490',
                    'templateid' => '531994',
                    'param' => $code,
                    'mobile' => $phone,
                    'uid' => time()
                ];
                $sms_data = json_encode($sms_data);
                $sms_res = $this->postForm('https://open.ucpaas.com/ol/sms/sendsms', $sms_data);
                //echo json_encode( array( 'msg' => '短信发送失败:'.$sms_res, 'code' => 0 ) );
                // exit;
                $sms_res =json_decode($sms_res, true);
                if( $sms_res['code'] == '000000' ) {
                    $res = true;
                } else {
                    $res = false;
                }

            }
        */
        //$res = $this->yunxinSms($phone, $code);

        if( $res )
        {
            Predis::getInstance()->set($key, $code, 300);
            //TODO CK调试
            //$this->success('发送成功');
            $this->success('发送成功,验证码是'.$code);
        }
        else
        {
            $this->error('发送失败');

        }


    }

    //创蓝短信发送
    public function chuanglanSms($phone, $code){
        $clapi  = new \sms\ChuanglanSmsApi();

         //$true_code=Predis::getInstance()->get($key);
        //设置您要发送的内容：其中“【】”中括号为运营商签名符号，多签名内容前置添加提交
        $result = $clapi->sendSMS($phone, '【优点】您的验证码为'.$code.'，验证码5分钟内有效。');
        if(!is_null(json_decode($result))){
            $output=json_decode($result,true);
            if(isset($output['code'])  && $output['code']=='0'){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //天瑞-start
    public function tianruiSms($phone, $code){
        $encode='UTF-8';
        $accesskey='';//'eFcCUjBrPXhuKEmL';  //平台分配给用户的Accesskey，登录系统首页可点击"我的秘钥"查看
        $secret='';//'HzXSGDKvyjsdIDhSSNb33eycLfAcvPrI';  //平台分配给用户的AccessSecret，登录系统首页可点击"我的秘钥"查看
        $mobile=$phone;  //要发送的手机号
        $sign='【优点】';  //短信签名
        $templateId='177313';  //模板ID
        $content=$code.'##5';  //要发送的短信内容。

        $result = $this->send($accesskey,$secret,$mobile,$templateId,$sign,$content,$encode);  //进行发送
        $result =json_decode($result);
        if($result->code==0) {
            return true;
            //提交成功
            //逻辑代码
        } else {
            return false;

        }
        //$this->success('成功', $sms_res);
    }
    function send($accesskey,$secret,$mobile,$templateId,$sign,$content,$encode)
    {
        //发送链接（用户名，密码，手机号，内容）
        $url = "http://api.1cloudsp.com/api/v2/send?";
        $data=array
        (
            'accesskey'=>$accesskey,
            'secret'=>$secret,
            'encode'=>$encode,
            'mobile'=>$mobile,
            'content'=>$content,
            'sign'=>$sign,
            'templateId'=>$templateId
        );
        $result = $this->curlSMS($url,$data);
        //print_r($data); //测试
        return $result;
    }
    function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源
        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        //$this->success('xx', $res);
        return $res[1]; //然后在这里返回数组。
    }
    //天瑞-end

    //云信互联


	public function yunxinSms($mobile, $code) {

		$this->account = '';
		$this->passwd = '';
		$this->yunxin_config['api_send_url'] = 'https://u.smsyun.cc/sms-partner/access/'.$this->account.'/sendsms';
		$this->yunxin_config['api_account'] = $this->account;
		$this->yunxin_config['api_password'] = md5($this->passwd);
		//云信接口参数
		$postArr = array (
				'smstype' =>'4',//短信发送发送
				'clientid'  =>  $this->yunxin_config['api_account'],
				'password' => $this->yunxin_config['api_password'],
				'mobile' => $mobile,
				'content' => "您的验证码是".$code."，如非本人操作，请忽略此条短信。",
				'sendtime'=>date('Y-m-d H:i:s'),
				'extend'=>'00',
				'uid'=>'00'
		);
		$result = $this->curlPost( $this->yunxin_config['api_send_url'] , $postArr);
		Log::record('sendSMS:'.$result,'info');
		return $result;
	}

	private function curlPost($url,$postFields){
		$postFields = json_encode($postFields);
		//echo $postFields;
		$ch = curl_init ();
		curl_setopt( $ch, CURLOPT_URL, $url ); 
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Accept-Encoding: identity',
					'Content-Length: ' . strlen($postFields),
					'Accept:application/json',
					'Content-Type: application/json; charset=utf-8'   //json版本需要填写  Content-Type: application/json;
					)
			   );
		//curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //如果报错 name lookup timed out 报错时添加这一行代码
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt( $ch, CURLOPT_TIMEOUT,60); 
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		$ret = curl_exec ( $ch );
		if (false == $ret) {
			$result = curl_error(  $ch);
		} else {
			$rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
			if (200 != $rsp) {
				$result = "请求状态 ". $rsp . " " . curl_error($ch);
			} else {
				$result = $ret;
			}
		}
		return $result;
	}


    public function voiceVerify(){
        $sid = '';//'ed199509ba7b46efb4849e3b4911eb01';
        $token = 'd4';
        $appid = '';//'5e8f30e23ba64398b04ed7e4ed86e993';
        $data['voiceVerify'] = [
            'appId' => $appid,
            'captchaCode' => '1234',
            'to' => '17843542584',
        ];
        print_r($data);
        $data = json_encode($data);

        $time = date('YmdHis');
        $sig = strtoupper(md5($sid.$token.$time));
        $url = 'http://message.ucpaas.com/2017-06-30/Accounts/'.$sid.'/Calls/voiceVerify?sig='.$sig;

        $authorization = base64_encode($sid.':'.$time);
        // exit($authorization);
        $header = array(
            'Accept:application/json',
            'Connection:keep-alive',
            'Authorization:'.$authorization,
            'Content-Type:application/json;charset=utf-8'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $this->error('发送失败', $result);
        //echo json_encode( array( 'msg' => '短信发送失败:'.$sms_res, 'code' => 0 ) );
        // exit;
        $sms_res =json_decode($result, true);
        if( $sms_res['code'] == '000000' )
        {
            $this->success('发送成功');
        }
        else
        {
            $this->error('发送失败', $sms_res);

        }
    }

    public function postForm($url, $data, $headers = array(), $referer = NULL) {
        $header = array(
            'Accept:application/json',
            'Content-Type:application/json;charset=utf-8',
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;

    }


    //客服电话
    public function servicePhone(){
        $service_phone = ConfigModel::where('name', 'service_phone')->value('value');

        $this->success('请求成功', $service_phone);
    }

    //客服信息
    public function serviceInfo(){
        $data['service_phone'] = '13198982981';//ConfigModel::where('name', 'service_phone')->value('value');
        $data['service_image'] = '111111111111111111';//oosAbsolutePath(ConfigModel::where('name', 'service_image')->value('value'));

        $this->success('请求成功', $data);
    }

    //客服电话
    public function about(){
        $about = ConfigModel::where('name', 'about')->value('value');

        $this->success('请求成功', $about);
    }

    //注册协议
    public function registrationProtocol(){
        $registration_protocol = ConfigModel::where('name', 'registration_protocol')->value('value');

        $this->success('请求成功', $registration_protocol);
    }


    public function test(){

        $res = [];
        $this->success('请求成功', $res);
    }

    public function checkVersion(){
        $appid = $this->request->post('appid');
        $version = $this->request->post('version');

        if ($appid && $version) {
            $this->error('缺少参数');
        }

        $data['note'] = ConfigModel::where('id', 35)->value('value');
        $data['url'] = ConfigModel::where('id', 36)->value('value');
        $data['update'] = 1;
        $app_version = ConfigModel::where('id', 37)->value('value');
        if ($app_version==$version) {

            $data['update'] = 0;
        }$data['update'] = 0;
        $this->success('请求成功', $data);
    }

    public function appDownUrl(){

        $data = ConfigModel::where('id', 43)->value('value');
        $this->success('请求成功', $data);
    }

    public function getV5Token(){


        $time = time().'000';
        //$expiresIn = 3600000;
        $sgin = '';//'appidaa095a2a5e7c4ca280ec9b797f1bdb56timestamp'.$time.'ca585b388e6d4140b7b1296d3e119090';
        $sgin = md5($sgin);
        //var_dump($sgin);
        $resjson = file_get_contents('https://free7jysj6c2.verify5.com/openapi/getToken?appid=aa095a2a5e7c4ca280ec9b797f1bdb56&timestamp='.$time.'&signature='.$sgin);
        $res = json_decode($resjson, true);
        if ($res['success']) {
            Predis::getInstance()->set('v5_token', $res['data']['token']);
            Predis::getInstance()->expire('v5_token', 864000);
            $data = [
                'host' => 'free7jysj6c2.verify5.com',
                'token' => $res['data']['token']
            ];
            $this->success('请求成功', $data);
        } else {
            $data = [
                'host' => 'free7jysj6c2.verify5.com',
                'token' => ''
            ];
            $this->success('请求成功', $data);
        }

    }

}
