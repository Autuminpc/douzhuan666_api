<?php

namespace app\common\library;


class RequestLimit
{
    


    /**
        添加报名
     */
    public static function run()
    {

        self::ipLimit();


    }



    //ip频率限制
    private static function ipLimit(){
        $key = request()->ip();

        if ($key != '119.23.190.242' && $key != '127.0.0.1') {

            $minute_timer = 100;
            $hour_timer = 1000;

            $redis = Predis::getInstance();


            $minute_key = 'm_' . $key;
            $hour_key = 'h_' . $key;

            //一分钟内是否频繁请求
            if ($redis->setNx($minute_key, '1')) {

                $redis->expire($minute_key, 60);
            } else {
                $timer = $redis->get($minute_key);

                if ($minute_timer < $timer) {
                    exit('一分钟内频繁请求！');
                }

                $redis->set($minute_key, $timer + 1);

            }


            //一小时内是否频繁请求
            if ($redis->setNx($hour_key, '1')) {

                $redis->expire($hour_key, 3600);
            } else {
                $timer = $redis->get($hour_key);

                if ($hour_timer < $timer) {
                    exit('一小时内频繁请求！');
                }

                $redis->set($hour_key, $timer + 1);

            }
        }

    }





}
