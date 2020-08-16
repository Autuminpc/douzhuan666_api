<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config;
use app\common\model\User as UserModel;
use app\common\model\UserLevel as UserLevelModel;
use app\common\model\Config as ConfigModel;
use app\common\model\Area as AreaModel;
use app\common\model\Poster as PosterModel;
use app\common\model\Store as StoreModel;
use app\common\model\UserMoneyLog as UserMoneyLogModel;
use app\common\library\Predis;
use app\common\library\Task as TaskLib;
use app\common\library\EditImg;
use app\common\library\QrCode;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'register', 'updatepwd', 'forgetpwd'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $user = $this->auth->getUser()->append(['user_agent_name', 'user_level_name']);

        $TaskLib = (new TaskLib());

        $data['user_agent_name'] = $user['user_agent_name'];
        $data['user_level_name'] = $user['user_level_name'];

        $data['avatar_path'] = oosAbsolutePath($user['avatar']);
        $data['id'] =  $user['id'];
        $data['username'] =  $user['username'];
        $data['nickname'] =  $user['nickname'];
        //总统计
        $data['total_task_income'] = $user['total_task_income'];//任务收益
        $data['money'] =  $user['money'];//可用余额
        $data['total_task_commission'] = $user['total_task_commission'];//任务佣金

        //$can_appl_ids = $TaskLib->getCanApplyTaskIds($user['id']);

        //今日可接钻石任务数量
        if ($user['user_level_id'] == 8) {
            $data['can_apply_task_jx_num'] = UserLevelModel::where('id', $user['user_level_id'])->value('day_task_num');
        } else {
            $data['can_apply_task_jx_num'] = 0;
        }
        //今日可接普通任务数量
        $user_level_hidden = explode(',', $user['user_level_hidden']);
        $data['can_apply_task_pt_num'] = UserLevelModel::whereIn('id', $user_level_hidden)->where('id != 8')->sum('day_task_num');

        $data['total_recommend_income'] = $user['total_recommend_income'];//推荐奖励
        //$data['team_num'] = UserModel::where('concat(\',\',parent_id) like \'%,'.$user['id'].',%\' and is_delete = "0"')->count()-1;
        $data['team_num'] = 0;
            //今日数据
        $task_user_data = $TaskLib->getTodayTaskData($user['id']);
        //$this->success('请求成功', $task_user_data);
        $data['today_task_pt'] = $task_user_data['today_task_pt']>$data['can_apply_task_pt_num']?$data['can_apply_task_pt_num']:$task_user_data['today_task_pt'];//今日总任务数
        $data['today_task_jx'] = $task_user_data['today_task_jx']>$data['can_apply_task_jx_num']?$data['can_apply_task_jx_num']:$task_user_data['today_task_jx'];//今日完成数
        $data['today_income'] = $task_user_data['today_income'];//今日收入

        //个人中心返回是否开店
        $data['has_store'] = StoreModel::where(['user_id' => $user['id'], 'status' => 1, 'is_delete' => 0])->count()?1:0;
        $data['opne_store_text'] = '请联系客服开通店铺';


        $this->success('请求成功', $data);
    }

    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            if ($this->auth->getError() == '系统升级，请重新设置密码') {
                $this->success($this->auth->getError(), '', '88888');
            }

            $this->error($this->auth->getError());
        }
    }



    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code   验证码
     */
    public function register()
    {

        $username = $this->request->post('mobile');
        $password = $this->request->post('password');
        $mobile = $this->request->post('mobile');
        $parent_id = $this->request->post('parent_id')?:0;
        $code = $this->request->post('code');
        if (!$mobile || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if (!($mobile && Validate::regex($mobile, "^1\d{10}$"))) {
            $this->error(__('Mobile is incorrect'));
        }
        
        // if (!$parent_id) {
        //     $this->error('请输入邀请码');
        // }

        //验证码
        $key="register_".$mobile;
        $true_code = Predis::getInstance()->get($key);
        if ($code != $true_code) {
            $this->error(__('Captcha is incorrect'));
        } else {

            //删除验证码，删除成功的进行保存
            if (Predis::getInstance()->del($key)) {
                //判断手机号是否存在
                $have = UserModel::where([
                    'username' => $mobile,
                    'is_delete' => 0
                ])->count();
                if ($have) {
                    $this->error('该手机号已注册');
                }
                $ret = $this->auth->register($username, $password, $mobile, $parent_id, []);
                if ($ret) {
                    $data = ['userinfo' => $this->auth->getUserinfo()];
                    $this->success(__('Sign up successful'), $data);
                } else {
                    $this->error($this->auth->getError());
                }
            } else {
                $this->error('服务器异常', '请不要重复提交');
            }


        }


    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    //查看会员资料
    public function user(){
        $user = $this->auth->getUser();
        $data['avatar_path'] = oosAbsolutePath($user['avatar']);
        $data['avatar'] = $user['avatar'];
        $data['nickname'] =  $user['nickname'];
        $data['mobile'] =  $user['mobile'];
        $data['sex'] =  $user['sex'];

        //默认北京
        $province_id = $user['address_province']?:1;
        $city_id = $user['address_city']?:2;
        $county_id = $user['address_area']?:3;
        $province = AreaModel::where([
            'pid' => 0
        ])->field('id, name')->select();
        if (!$province) {
            $province_id = $user['address_province']?:1;
            $city_id = $user['address_city']?:2;
            $county_id = $user['address_area']?:3;
        }

        $city = AreaModel::where([
            'pid' => $province_id
        ])->field('id, name')->select();
        if (!$city) {
            $province_id = $user['address_province']?:1;
            $city_id = $user['address_city']?:2;
            $county_id = $user['address_area']?:3;
        }

        $county = AreaModel::where([
            'pid' => $city_id
        ])->field('id, name')->select();
        if (!$county) {
            $province_id = $user['address_province']?:1;
            $city_id = $user['address_city']?:2;
            $county_id = $user['address_area']?:3;
        }

        $province_label = 0;
        foreach ($province as $key => $value) {
            if ($value['id'] == $province_id){
                $province_label = $key;
                break;
            }
        }

        $city_label = 0;
        foreach ($city as $key => $value) {
            if ($value['id'] == $city_id){
                $city_label = $key;
            }
        }

        $county_label = 0;
        foreach ($county as $key => $value) {
            if ($value['id'] == $county_id){
                $county_label = $key;
            }
        }


        $data['address_province'] = $province;
        $data['address_city'] = $city;
        $data['address_area'] = $county;
        $data['address_detail'] = $user['address_detail'];
        $data['label'] = [$province_label, $city_label, $county_label];


        $this->success('请求成功', $data);
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function saveUser()
    {
        $user_id = $this->auth->getUser()['id'];
        $field = $this->request->request('field');
        $value = $this->request->request('value');

        if ((!$field || !$value) && !in_array($field, ['username', 'avatar', 'email', 'wx_account', 'address'])) {
            $this->error(__('Invalid parameters'));
        }

        $user = UserModel::where('id', $user_id)->find();
        if (!$user) {
            $this->error(__('Username already exists'));
        }
        $user->{$field} = $value;
        $user->save();
        $this->success('修改成功');
    }

    public function saveAllUser()
    {
        $user_id = $this->auth->getUser()['id'];
        $nickname = $this->request->post('nickname');
        $avatar = $this->request->post('avatar');
        $mobile = $this->request->post('mobile');
        $sex = $this->request->post('sex');
        $address_detail = $this->request->post('address_detail');
        $address_province = $this->request->post('address_province');
        $address_city = $this->request->post('address_city');
        $address_area = $this->request->post('address_area');



        $user = UserModel::where('id', $user_id)->find();

        $user->nickname = $nickname;
        $user->avatar = $avatar;
        $user->mobile = $mobile;
        $user->sex = $sex;
        $user->address_detail = $address_detail;
        $user->address_province = $address_province;
        $user->address_city = $address_city;
        $user->address_area = $address_area;
        $user->save();
        $this->success('修改成功');
    }

    //获取银行卡信息
    public function getUserBank(){
        $user = $this->auth->getUser();

        $data['bank_list'] = array(
            "中国农业银行","中国建设银行","中国工商银行","中国银行","交通银行","邮政储蓄银行","招商银行","兴业银行",
            "中信银行","中国光大银行","上海浦东发展银行","中国民生银行","深圳发展银行","上海浦东发展银行","深圳发展银行","民生银行","广东发展银行","华夏银行"
        );

        $data['bank_name'] = $user['bank_name'];
        $data['subbranch_name'] = $user['subbranch_name'];
        $data['bank_user'] = $user['bank_user'];
        $data['bank_number'] = $user['bank_number'];



        $this->success('获取成功', $data);

    }

    //修改银行卡信息
    public function saveUserBank()
    {
        $user = $this->auth->getUser();
        $user_id = $user['id'];
        $mobile = $user['username'];
        $bank_name = $this->request->post('bank_name');
        $subbranch_name = $this->request->post('subbranch_name');
        $bank_user = $this->request->post('bank_user');
        $bank_number = $this->request->post('bank_number');
        // $code = $this->request->post('code');
        // if (!$code) {
        //     $this->error('请输入验证码');
        // }
		$bind_count = UserModel::where('bank_number', $bank_number)->count();
		if($bind_count >=5){
			$this->error('账号绑定数过多');
		}
		
        // $key="register_".$mobile;
        // $true_code = Predis::getInstance()->get($key);
        // if ($code != $true_code) {
            // $this->error(__('Captcha is incorrect'));
        // } else {

            $user = UserModel::where('id', $user_id)->field('id, nickname, bank_name, subbranch_name, bank_user, bank_number')->find();
            
            if(trim($user->bank_number) != "" && trim($user->bank_number) != "bank_user"){
            	$this->error("绑定后无法修改");
            }

            if (!$user->nickname) {
                $user->nickname = $bank_user;
            }
            
           
            
            $user->bank_name = $bank_name;
            $user->subbranch_name = $subbranch_name;
            $user->bank_user = $bank_user;
            $user->bank_number = $bank_number;
            $user->save();
            // Predis::getInstance()->del($key);//删除验证码
            $this->success('修改成功');
        // }
    }



    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function forgetpwd()
    {
        $mobile = $this->request->post("mobile");
        $newpassword = $this->request->post("password");
        $code = $this->request->post("code");
        if (!$newpassword || !$code) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $user = UserModel::where([
            'username' => $mobile,
            'is_delete' => 0
        ])->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        if ($user->status !=1 ) {
            $this->error('用户状态异常');
        }

        //验证码
        $key="register_".$mobile;
        $true_code = Predis::getInstance()->get($key);
        if ($code != $true_code) {
            $this->error(__('Captcha is incorrect'));
        }

        Predis::getInstance()->del($key);

        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function updatepwd()
    {
        $mobile = $this->request->post("mobile");
        $newpassword = $this->request->post("password");
        $code = $this->request->post("code");
        if (!$newpassword || !$code) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $user = UserModel::where([
            'username' => $mobile,
            'status' => 1,
            'is_delete' => 0
        ])->find();
        if (!$user) {
            $this->error(__('User not found'));
        }
	

        //验证码
        $key="register_".$mobile;
        $true_code = Predis::getInstance()->get($key);
        if ($code != $true_code) {
            $this->error(__('Captcha is incorrect'));
        }

        Predis::getInstance()->del($key);

        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->auth->direct($user->id);
            $data = ['userinfo' => $this->auth->getUserinfo()];
            //$this->success(__('Logged in successful'), $data);
            $this->success(__('Reset password successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    public function resetpwd()
    {

        $old_password = $this->request->post("old_password");
        $new_password = $this->request->post("new_password");

        if (!($old_password&&$new_password)) {
            $this->error('缺少参数');
        }

        $ret = $this->auth->changepwd($new_password, $old_password);

        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    //获取海报列表
    public function posterList(){

        $posters = PosterModel::where([
            'status' => 1,
            'is_delete' => 0
        ])->field('id, bg_img, bg_img as bg_img_path')->select();

        $this->success('获取成功', $posters);

    }

    //获取个人二维码
    public function getPoster(){
        $user = $this->auth->getUser();
        $poster_id = $this->request->post('id');
        $savepath='/uploads/qr/poster1_'.$user['id'].'.png';

        if (!$poster_id) {
            if (file_exists(ROOT_PATH."public".$savepath)) {
                $this->success('获取成功', absolutePath($savepath)."?time=".time());
            }
            $poster = PosterModel::where([
                'status' => 1,
                'is_delete' => 0
            ])->field('id, qrcode_x, qrcode_y, bg_img, bg_img as bg_img_path')->find();
        } else {
            $poster = PosterModel::where([
                'id' => $poster_id,
                'status' => 1,
                'is_delete' => 0
            ])->field('id, qrcode_x, qrcode_y, bg_img, bg_img as bg_img_path')->find();
        }

        $this->createPoster($user, $poster, $savepath);

        $this->success('获取成功', absolutePath($savepath)."?time=".time());
    }

    //生成海报
    public function createPoster($user, $poster, $savepath){
        $share_bg = $poster['bg_img'];
        $qrcode_x = $poster['qrcode_x'];
        $qrcode_y = $poster['qrcode_y'];

        $codeimg = ROOT_PATH."public".$share_bg;
        //判断背景图是否存在，不存在要下载下来
        if (!file_exists($codeimg)) {
            $this->checkDir($codeimg);//判断路径，不存在创建路径
            $erjinzhi = file_get_contents(oosabsolutePath($share_bg));
            file_put_contents($codeimg, $erjinzhi);
        }

        //被改后缀了会有bug
        //$codeimg = imagecreatefrompng($codeimg);
        $codeimg = $this->_formatImg($codeimg);


        $qrcode = (new QrCode());
        $wap_url = ConfigModel::where('name', 'wap_url')->value('value');
        $url=$wap_url.'/#/pages/login/reg?parent_id='.$user['id'];
        $base64 = $qrcode->build($url,178);
        $qr_path = base64Upload($base64, $user['id']);

        $font = './assets/fonts/SimHei.ttf';

        //白色底图（头部）
        $imgarr['path']= $qr_path;
        $imgarr['x']=$qrcode_x;
        $imgarr['y']=$qrcode_y;
        $imgarr['width']=210;
        $imgarr['height']=210;
        $picarr[]=$imgarr;
        $textarr = [];
        /*
        $textarr[0]['size']=20;
        $textarr[0]['x']=225;
        $textarr[0]['y']=400;
        $textarr[0]['rgb'][0]=255;
        $textarr[0]['rgb'][1]=255;
        $textarr[0]['rgb'][2]=255;
        $textarr[0]['text']='昵称：'.$user['nickname'];
        $textarr[0]['font']=$font;


        $textarr[1]['size']=20;
        $textarr[1]['x']=235;
        $textarr[1]['y']=450;
        $textarr[1]['rgb'][0]=255;
        $textarr[1]['rgb'][1]=255;
        $textarr[1]['rgb'][2]=255;
        $textarr[1]['text']='我为优点代言';
        $textarr[1]['font']=$font;
*/

        imagesavealpha($codeimg, true);//不要丢了$resize_im图像的透明色;


        (new EditImg())->editJpg(ROOT_PATH.'public'.$savepath,$codeimg,$picarr, $textarr);
        //第六：销毁画布
        imagedestroy($codeimg);

    }

    private function _formatImg($imgName) {
        $ename = getimagesize($imgName); 
        $ename = explode('/',$ename['mime']); 
        $ext   = $ename[1]; 
        switch($ext){ 
            case "png":
                $image = imagecreatefrompng($imgName); 
                break; 
            case "jpeg":
                $image = imagecreatefromjpeg($imgName); 
                break; 
            case "jpg":
                $image = imagecreatefromjpeg($imgName); 
                break; 
            case "gif":
                $image = imagecreatefromgif($imgName); 
                break; 
        } 
        return $image;
    }

    //团队总人数
    public function teamNum(){
        $user_id = $this->auth->getUser()['id'];
        //一级团队人数
        $data['first_num'] = UserModel::where([
            'first_parent' => $user_id,
            'is_delete' => 0
        ])->count();
        //二级团队人数
        $data['second_num'] = UserModel::where([
            'second_parent' => $user_id,
            'is_delete' => 0
        ])->count();
        //三级团队人数
        $data['third_num'] = 0;
        //团队总人数
        //$data['all_num'] = UserModel::where('concat(\',\',parent_id) like \'%,'.$user_id.',%\' and is_delete = "0"')->count()-1;
        $data['all_num'] = 0;
        $this->success('获取成功', $data);

        //一级团队人数
        $data['first_num'] = UserModel::where([
            'first_parent' => $user_id,
            'is_delete' => 0
        ])->count();
        //二级团队人数
        $data['second_num'] = UserModel::where([
            'second_parent' => $user_id,
            'is_delete' => 0
        ])->count();
        //三级团队人数
        $data['third_num'] = 0;
        //团队总人数
        $data['all_num'] = UserModel::where('concat(\',\',parent_id) like \'%,'.$user_id.',%\' and is_delete = "0"')->count()-1;

        $this->success('获取成功', $data);
    }

    //团队列表
    public function teamList()
    {
        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $type = $this->request->post('level')?:1;

        if ($type == 1) {
            $user_where = [
                ['first_parent', '=',  $user_id],
            ];
        } elseif ($type == 2) {
            $user_where = [
                ['second_parent', '=',  $user_id],
            ];
        } else {
            $user_where = [
                ['third_parent', '=',  $user_id],
            ];
        }

        $team_list = (new UserModel())->getUserArray($user_where, $page, $row);

        $this->success('请求成功', $team_list);
    }


    public function moneyLogList(){
        $user_id = $this->auth->getUser()['id'];

        $page = $this->request->post('page')?:1;
        $row = $this->request->post('row')?:10;
        $type = $this->request->post('type');



        //产品列表
        $user_money_log_where = [
            ['user_id', '=',  $user_id],
        ];
        //是否有订单状态条件(0待处理，1审核通过等待打款，2审核通过打款失败，3已完成，4审核不通过)
        if (isset($type) && $type !== '' && $type != '-1') {

            array_push($user_money_log_where, ['type', '=', $type]);

        }


        $meal_buy_list = (new UserMoneyLogModel())->getUserMoneyLogArray($user_money_log_where, $page, $row);

        $this->success('获取成功', $meal_buy_list);
    }

    public function checkDir($share_bg){
        $arr = explode('/', $share_bg);
        array_pop($arr);
        $path = implode('/', $arr);
        $path = '/'.$path.'/';
        $codeimg = $path;

        //ROOT_PATH . 'public/uploads/20200214/'
        if (is_dir($codeimg) || mkdir($codeimg, 0755, true)) {
            return true;
        }

        return false;
    }

    public function moneyTypeList(){
        $money_type[] = ['name' => '全部', 'value' => -1];
        $money_type[] = ['name' => '任务奖励', 'value' => 1];
        $money_type[] = ['name' => '任务分佣', 'value' => 2];
        $money_type[] = ['name' => '套餐提成', 'value' => 3];
        $money_type[] = ['name' => '充值消费', 'value' => 4];
        $money_type[] = ['name' => '后台充值', 'value' => 5];
        $money_type[] = ['name' => '提现', 'value' => 6];
        $money_type[] = ['name' => '提现驳回', 'value' => 7];
        $money_type[] = ['name' => '商品购买', 'value' => 8];
        $money_type[] = ['name' => '店铺收益', 'value' => 9];
        $money_type[] = ['name' => '其它', 'value' => 10];
        $money_type[] = ['name' => '注册赠送', 'value' => 11];
        $this->success('获取成功', $money_type);
    }
}
