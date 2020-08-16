<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\UserAddress as UserAddressModel;
use app\common\model\Area as AreaModel;
use think\Validate;

/**
 * 会员接口
 */
class UserAddress extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    //地址列表
    public function userAddressList(){
        $user_id = $this->auth->getUser()['id'];

        $user_address_where = [
            ['user_id', '=', $user_id]
        ];
        $user_address_list = (new UserAddressModel())->getUserAddressArray($user_address_where);

        $this->success('获取成功', $user_address_list);
    }

    //获取下级地址
    public function getArea(){
        $id = $this->request->post("id");
        $area_list = AreaModel::where('pid', $id)->field('id, name')->select();

        $this->success('获取成功', $area_list);
    }

    //编辑地址界面
    public function userAddress(){

        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        //默认北京
        $province_id = 1;
        $city_id = 2;
        $county_id = 3;
        $data['name'] = '';
        $data['mobile'] = '';
        $data['detail'] = '';
        $data['is_default'] = 0;
        //如果有
        if ($id) {
            $user_address = UserAddressModel::where([
                'id' => $id,
                'user_id' => $user_id,
                'is_delete' => 0
            ])->find();

            if (!$user_address) {

                $this->error('地址不存在');
            }

            $province_id = $user_address['province'];
            $city_id = $user_address['city'];
            $county_id = $user_address['county'];
            $data['name'] = $user_address['name'];
            $data['mobile'] = $user_address['mobile'];
            $data['detail'] = $user_address['detail'];
            $data['is_default'] = $user_address['is_default'];
        }

        $province = AreaModel::where([
            'pid' => 0
        ])->field('id, name')->select();
        if (!$province) {
            $this->error('请求失败');
        }

        $city = AreaModel::where([
            'pid' => $province_id
        ])->field('id, name')->select();
        if (!$city) {
            $this->error('请求失败');
        }

        $county = AreaModel::where([
            'pid' => $city_id
        ])->field('id, name')->select();
        if (!$county) {
            $this->error('请求失败');
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

        $data['province'] = $province;
        $data['city'] = $city;
        $data['county'] = $county;

        $data['label'] = [$province_label, $city_label, $county_label];

        $this->success('获取成功', $data);
    }

    //设置为默认
    public function defaultAddress(){
        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        $user_address = UserAddressModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->find();

        if (!$user_address) {
            $this->error('地址不存在');
        }

        $user_address->is_default = 1;
        $user_address->save();

        UserAddressModel::where([
            'user_id' => $user_id,
            'is_default' => 1,
            'is_delete' => 0
        ])->update(['is_default' => 0]);

        $this->success('设置成功');
    }

    //保存编辑
    public function saveAddress(){
        $data['user_id'] = $this->auth->getUser()['id'];
        $id = $this->request->post("id");
        $data['province'] = $this->request->post("province");
        $data['city'] = $this->request->post("city");
        $data['county'] = $this->request->post("county");
        $data['detail'] = $this->request->post("detail");
        $data['name'] = $this->request->post("name");
        $data['mobile'] = $this->request->post("mobile");
        $data['is_default'] = $this->request->post("is_default");

        if (!($data['province']&&$data['city']&&$data['county']&& $data['detail']&&$data['name'])) {
            $this->error('缺少参数');
        }

        if ($data['mobile'] && !Validate::regex($data['mobile'], "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }

        if (!in_array($data['is_default'], [0,1])) {
            $this->error('参数错误');
        }

        //将其他地址默认去除
        if ($data['is_default'] == 1) {
            UserAddressModel::where([
                'user_id' => $data['user_id'],
                'is_default' => 1,
                'is_delete' => 0
            ])->update(['is_default' => 0]);
        }

        //编辑
        if ($id) {
            $have = UserAddressModel::where([
                'id' => $id,
                'user_id' => $data['user_id'],
                'is_delete' => 0
            ])->count();
            if ($have) {
                $res = UserAddressModel::where('id', $id)->update($data);
            }else {
                $this->error('地址不存在');
            }

        } else {//增加地址
            //如果是首个地址必须为默认地址
            $have = UserAddressModel::where([
                'user_id' => $data['user_id'],
                'is_delete' => 0
            ])->count();
            if ($have) {
                $data['is_default']=1;
            }
            $res = UserAddressModel::create($data);
            $id = $res['id'];
        }

        if ($res) {
            $this->success('保存成功', $id);
        } else {
            $this->error('操作失败');
        }

    }

    //删除地址
    public function delAddress(){
        $user_id = $this->auth->getUser()['id'];
        $id = $this->request->post("id");

        $res = UserAddressModel::where([
            'id' => $id,
            'user_id' => $user_id,
            'is_delete' => 0
        ])->update(['is_delete' => 1]);

        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

}
