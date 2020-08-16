<?php

namespace app\admin\controller\store;

use app\common\controller\Backend;

/**
 * 店铺开通套餐管理
 *
 * @icon fa fa-circle-o
 */
class StoreMeal extends Backend
{
    
    /**
     * StoreMeal模型对象
     * @var \app\admin\model\store\StoreMeal
     */
    protected $model = null;
    protected $noNeedRight = ['searchList','searchListForMeal'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\store\StoreMeal;

        $this->softDel = true;
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

     /**
     * searchList
     *
     * @return void
     */
    public function searchList(){
        
        $result = $this->model->select();
        $searchlist = [];
        foreach ($result as $key => $value) {
            $searchlist[] = ['id' => $value['id'], 'name' => $value['name']];
        }
        $data = ['searchlist' => $searchlist];
        $this->success('', null, $data);

    }


    /**
     * searchListForMeal
     *
     * @return void
     */
    public function searchListForMeal(){
        $result = $this->model->select();
        $searchlist = [];
        foreach ($result as $key => $value) {
            $searchlist[$value['id']] = $value['name'];
        }
        $this->success('', null, $searchlist);
    }

}
