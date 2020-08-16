<?php

namespace app\organ\controller;

use app\common\controller\Organend;
use think\Config;
use think\Db;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Organend
{

    /**
     * 查看
     */
    public function index(){
        $now_time = time();
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',$now_time);

        $exam_model = model('app\organ\model\Exam');
        //统计已经报名人数
        $where_signed['organ_id'] = $this->organ_id;
        $where_signed['is_delete'] = 0;
        $where_signed['create_time'] = ['between',[$start_time, $end_time]];
        $data['signed'] = $exam_model->where($where_signed)->count();

        //统计待考试人数
        $where_unexam['organ_id'] = $this->organ_id;
        $where_unexam['status'] = 1;    //审核通过
        $where_unexam['is_delete'] = 0;
        $where_unexam['exam_time'] = ['>=',$now_time];
        $data['unexam'] = $exam_model->where($where_unexam)->count();

        //统计已经考试人数
        $where_examed['organ_id'] = $this->organ_id;
        $where_examed['status'] = 1;    //审核通过
        $where_examed['is_delete'] = 0;
        $where_examed['exam_time'] = ['<',$now_time];
        $data['examed'] = $exam_model->where($where_examed)->count();

        //统计待审核人数
        $where_unverify['organ_id'] = $this->organ_id;
        $where_unverify['status'] = 0;    //待审核
        $where_unverify['is_delete'] = 0;
        $data['unverify'] = $exam_model->where($where_unverify)->count();

        //统计已经审核人数
        $where_verified['organ_id'] = $this->organ_id;
        $where_verified['status'] = ['neq','0'];    //审核通过或者不通过的
        $where_verified['is_delete'] = 0;
        $data['verified'] = $exam_model->where($where_verified)->count();

        //统计未打印准考证
        $where_unprint['organ_id'] = $this->organ_id;
        $where_unprint['status'] = 1;    //审核通过
        $where_unprint['is_delete'] = 0;
        $where_unprint['print'] = 0;    //未打印
        $data['unprint'] = $exam_model->where($where_unprint)->count();

        //统计已经打印准考证的
        $where_printed['organ_id'] = $this->organ_id;
        $where_printed['status'] = 1;    //审核通过
        $where_printed['is_delete'] = 0;
        $where_printed['print'] = 1;    //已经打印
        $data['printed'] = $exam_model->where($where_printed)->count();

        //统计考生总数
        $where_student['is_delete'] = 0;
        $data['student_num'] = Db::name('student')->where($where_student)->where("FIND_IN_SET({$this->organ_id},`organ_id`)")->count();
        $this->assign('data',$data);
        return $this->fetch();
    }

}
