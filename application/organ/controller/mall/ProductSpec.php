<?php

namespace app\organ\controller\mall;

use app\common\controller\Organend;
use app\admin\model\mall\Product;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 商品规格管理
 *
 * @icon fa fa-circle-o
 */
class ProductSpec extends Organend
{
    
    /**
     * ProductSpec模型对象
     * @var \app\admin\model\mall\ProductSpec
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\mall\ProductSpec;
        //开启软删除
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
            //获取用户查看的产品ID
            $filter = $this->request->param('filter');
            if(!$filter){
                $this->error('您没有权限访问');
            }
            $filter_arr = json_decode($filter,true);
            if(!isset($filter_arr['product_id'])){
                $this->error('您没有权限访问');
            }
            //获取用户自己的所有产品
            $product_list = Product::where('is_delete','0')->where('store_id',$this->auth->__getstore('id'))->column('id');
            if(!in_array($filter_arr['product_id'],$product_list)){
                $this->error('您没有权限访问');
            }
            $total = $this->model
                    ->with(['product'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['product'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);

                    //添加规格需要更新商品的的最低单价
                    $spec_list = $this->model->where('product_id',$params['product_id'])->where('status=1 and is_delete=0')->column('price');
                    //加入新添加的商品的值
                    array_push($spec_list,$params['price']);
                    sort($spec_list);
                    $product_params['price'] = $spec_list[0];
                    //查找商品
                    $product = model('app\admin\model\mall\Product')->where('id',$params['product_id'])->find();
                    //如果添加的规格已经是上架的话，则要上架商品
                    if($params['status'] == '1' && $product['status'] == '0'){
                        $product_params['status'] = '1';
                        //添加商品增加店铺的商品数量
                        model('app\admin\model\store\Store')->where('id',$product['store_id'])->setInc('product_up_num');
                    }
                    $product->save($product_params);

                    Db::commit();
                    
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //获取商品ID
        $product_id = $this->request->param('product_id','');
        if(empty($product_id)){
            $this->error(__('所属商品是必须的', ''));
        }
        $this->assign('product_id',$product_id);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitOrganIds();
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
                    $result = $row->allowField(true)->save($params);

                    $product = model('app\admin\model\mall\Product')->where('id',$row['product_id'])->find();
                    //添加规格需要更新商品的的最低单价
                    $spec_list = $this->model->where('product_id',$product['id'])->where('status=1 and is_delete=0')->where("id","<>",$row['id'])->column('price');
                    if($params['status'] == '1'){
                        //加入新编辑的规格价格
                        array_push($spec_list,$params['price']);
                    }
                    sort($spec_list);
                    if($spec_list){
                        //说明这个商品有有效规格，最低价格为规格的最低价格
                        $product_params['price'] = $spec_list[0];

                        //商品有有效规格，如果商品之前是下架的，需要上架，并且增加店铺的上架商品数量
                        if($product['status'] == '0'){
                            $product_params['status'] = '1';
                            //更新店铺的上架数量
                            model('app\admin\model\store\Store')->where('id',$product['store_id'])->setInc('product_up_num');
                        }
                    }else{
                        //最低价格
                        $product_params['price'] = 0;

                        //商品没有效规格，如果商品是已经上架的话，则要下架商品，并且减少店铺的上架商品数量
                        if($product['status'] == '1'){
                            $product_params['status'] = '0';
                            //更新店铺的上架数量
                            model('app\admin\model\store\Store')->where('id',$product['store_id'])->setDec('product_up_num');
                        }
                    }
                    //保存商品信息
                    $product->save($product_params);

                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
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
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitOrganIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    if($this->softDel){
                        $v->is_delete = 1;
                        $count += $v->save();
                    }else{  
                        $count += $v->delete();
                    }
                }
                foreach ($list as $key => $value) {
                    $product = model('app\admin\model\mall\Product')->where('id',$value['product_id'])->find();
                    //删除规格需要更新商品的的最低单价
                    $spec_list = $this->model->where('product_id',$value['product_id'])->where('status=1 and is_delete=0')->column('price');
                    if($spec_list){
                        sort($spec_list);
                        $product_params['price'] = $spec_list[0];
                    }else{
                        $product_params['price'] = 0;
                        if($product['status'] == '1'){
                             //没有上架规格，则需要吧商品也一并下架了
                            $product_params['status'] = 0;
                            //更新店铺的上架数量
                            model('app\admin\model\store\Store')->where('id',$product['store_id'])->setDec('product_up_num');
                        }
                    }
                    $product->save($product_params);
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
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
