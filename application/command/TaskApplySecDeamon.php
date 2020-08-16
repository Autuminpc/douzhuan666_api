<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/22 0022
 * Time: 09:17
 */
namespace app\command;

use think\console\Command;
use app\common\library\TaskDeal as TaskDealLib;
use app\common\library\Predis;

class TaskApplySecDeamon extends Command
{
    protected function configure()
    {
        //这里是设置命令的名称和描述
        $this->setName('taskapplysecdeamon')->setDescription('任务审核的守护进程2,晚上11点到凌晨5点不开启');
    }

    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {
        $wait_time = 0;
        while (true) {

            //23点到5点不处理任务
            if (date('H')==23) {
                sleep(21600);
            }

            $task_apply_id = Predis::getInstance()->rPop('task_apply_list');

            //echo '一次'.time().PHP_EOL;
            //有任务不睡眠
            if ($task_apply_id) {
                $wait_time = 0;
                $res = TaskDealLib::completeTask($task_apply_id);
                if (!$res) {
                    //trace("task_apply_id : ".$task_apply_id.TaskDealLib::$_error.PHP_EOL, 'shanghaokj');
                    $str = time()."task_apply_id : ".$task_apply_id.TaskDealLib::$_error.PHP_EOL;
                    echo $str;
                    file_put_contents('task_apply_fail.log', $str, FILE_APPEND);
                    /*
                    Predis::getInstance()->incr('tpi_'.$task_apply_id);
                    //处理失败超过5次，不加回队列
                    if (Predis::getInstance()->get('tpi_'.$task_apply_id) >= 5) {
                        Predis::getInstance()->del('tpi_'.$task_apply_id);//删除
                        //加入到失败集合
                        Predis::getInstance()->sAdd('task_apply_fail', $task_apply_id);
                    }else {
                        //处理失败不超过5次，重新加回队列
                        Predis::getInstance()->lPush('task_apply_list', $task_apply_id);
                    }
                    */

                } else {
                    echo time()."task_apply_id : ".$task_apply_id."已完成".PHP_EOL;
                }


            } else {

                //如果休息了，直接停止10分钟
                if ($wait_time == 0) {
                    $wait_time = 600;
                }

                //没任务睡眠
                sleep($wait_time);
            }


        }

    }


}