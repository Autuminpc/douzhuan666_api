<?php

namespace app\common\library;

use think\Config;
use think\Db;
use think\Exception;
use think\Hook;
use think\Request;
use think\Validate;

class Lock
{
    
    //public static $_errot = '';

    /**
        加锁 xxxxxxxxxxx: 6380 -> 6379
     */
    public static function redisLock($key, $time)
    {

        if (Predis::getInstance('6379')->setNx($key, '1')){

            Predis::getInstance('6379')->expire($key, $time);

            return true;
        }else{

            return false;
        }

    }


    

    /**
        解锁
     */
    public static function redisUnLock($key)
    {   
        $res = Predis::getInstance('6379')->del($key);

        if ($res) {
            return true;
        }else{
            return false;
        }
        
    }

    
    
    
}
