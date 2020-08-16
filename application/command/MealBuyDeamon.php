<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/22 0022
 * Time: 09:17
 */
namespace app\command;

use think\console\Command;
use app\common\library\Meal as MealLib;
use app\common\library\Predis;

class MealBuyDeamon extends Command
{
    protected function configure()
    {
        //这里是设置命令的名称和描述
        $this->setName('mealbuydeamon')->setDescription('套餐购买记录的守护进程');
    }

    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {

        while (true) {

            $time = time()-259200;

            $meal_buy_ids = Predis::getInstance()->zRangeByScore('meal_buy_dj_zset', 0, $time);
            //echo '一次'.time().PHP_EOL;
            if ($meal_buy_ids) {
                foreach ($meal_buy_ids as $key => $meal_buy_id) {
                    $res = MealLib::mealBuyDj($meal_buy_id);
                    if ($res) {
                        Predis::getInstance()->zRem('meal_buy_dj_zset', $meal_buy_id);
                        //echo "task_apply_id : ".$meal_buy_id."已完成(".MealLib::$deamon_error.")".PHP_EOL;
                    } else {
                        //echo "task_apply_id : ".$meal_buy_id."处理失败(".MealLib::$deamon_error.")".PHP_EOL;
                    }

                }

            }
            //10分钟执行一次
            sleep(600);
        }

    }


}