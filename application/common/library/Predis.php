<?php
/**
 * Created by PhpStorm.
 * User: wangzewei
 * Date: 2018/4/7
 * Time: 下午6:46
 */

namespace app\common\library;

use Exception;
use Redis;

class Predis {

    //定义单例模式的变量
    private static $instance = null;

    public $redis = null;

    //检测是否实例化过对象
    public static function getInstance($port='6379')
    {


        $port = (int) $port;

        if (! isset(self::$instance[$port])) {

            self::$instance[$port] = new Predis($port);

        }

        return self::$instance[$port];
    }

    //禁止外部直接实例化
    private function __construct($port)
    {
        //$port = (int) $port;

        $this->redis = new Redis();
        $result = $this->redis->connect(config('redis.host'), $port, config('redis.time_out'));
        if($result === false){
            throw new Exception('redis connect error');
        }

        $this->redis->auth(config('redis.password'));
        //$this->redis->select($hash);
    }

/*
//检测是否实例化过对象
    public static function getInstance()
    {
        if(empty(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    //禁止外部直接实例化
    private function __construct()
    {
        $this->redis = new Redis();
        $result = $this->redis->connect(config('redis.host'), config('redis.port'), config('redis.time_out'));
        if($result === false){
            throw new Exception('redis connect error');
        }

        $this->redis->auth(config('redis.password'));
    }*/
    //禁止克隆对象
    private function __clone()
    {

    }

    /**
     * TODO: 选择数据库
     * @author wangzw
     * @param $index
     * @return object|bool
     * @date 2018-05-31
     */
    /*
    public function select($index)
    {
        if(!is_int($index)) {
            return false;
        }

        $this->redis->select($index);

        return $this::getInstance();
    }*/

    /**
     * TODO: 查找所有符合给定模式的键
     * @author wangzw
     * @param $pattern
     * @return array|bool
     * @date 2018-04-07
     */
    public function keys($pattern)
    {
        if(!$pattern){
            return false;
        }

        return $this->redis->keys($pattern);
    }

    /**
     * TODO: 设置键的有效时间
     * @author wangzw
     * @param $key
     * @param $expire_time
     * @return bool
     * @date 2018-04-07
     */
    public function expire($key, $expire_time)
    {
        if(!$key){
            return false;
        }

        return $this->redis->expire($key, $expire_time);
    }

    /**
     * TODO: 获取键的有效时间
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-07
     */
    public function ttl($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->ttl($key);
    }

    /**
     * TODO: 移除键的过期时间
     * @author wangzw
     * @param $key
     * @return bool
     * @date 2018-04-08
     */
    public function persist($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->persist($key);
    }

    /**
     * TODO: 在键存在时删除键
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-07
     */
    public function del($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->del($key);
    }

    /**
     * TODO: 设置指定键的值
     * @author wangzw
     * @param $key
     * @param $value
     * @param int $expire_time
     * @return bool
     * @date 2018-04-07
     */
    public function set($key, $value, $expire_time = 0)
    {
        if(!$key || !$value){
            return false;
        }

        if(is_array($value)){
            $value = json_encode($value);
        }

        if($expire_time <= 0){
            return $this->redis->set($key, $value);
        }

        return $this->redis->setex($key, $expire_time, $value);
    }

    /**
     * TODO: 获取指定键的值
     * @author wangzw
     * @param $key
     * @return bool|string
     * @date 2018-04-07
     */
    public function get($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->get($key);
    }

    /**
     * TODO: 返回键所储存的字符串值的长度
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-08
     */
    public function strLen($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->strlen($key);
    }

    /**
     * TODO: 将键中储存的数字值增一
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-08
     */
    public function incr($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->incr($key);
    }

    /**
     * TODO: 将键所储存的值加上给定的增量值
     * @author wangzw
     * @param $key
     * @param $value
     * @return bool|int
     * @date 2018-04-08
     */
    public function incrBy($key, $value)
    {
        if(!$key || !$value || !is_numeric($value)){
            return false;
        }

        return $this->redis->incrBy($key, (int)$value);
    }


    /**
     * TODO: 将键所储存的值加上给定的浮点增量值
     * @author wangzw
     * @param $key
     * @param $value
     * @return bool|float
     * @date 2018-04-08
     */
    public function incrByFloat($key, $value)
    {
        if(!$key || !$value || !is_numeric($value)){
            return false;
        }

        return $this->redis->incrByFloat($key, (float)$value);
    }

    /**
     * TODO: 将键中储存的数字值减一
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-08
     */
    public function decr($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->decr($key);
    }

    /**
     * TODO: 将键所储存的值减去给定的减量值
     * @author wangzw
     * @param $key
     * @param $value
     * @return bool|int
     * @date 2018-04-08
     */
    public function decrBy($key, $value)
    {
        if(!$key || !$value){
            return false;
        }

        return $this->redis->decrBy($key, (int)$value);
    }

    /**
     * TODO: 将指定的值追加到键的值的末尾
     * @author wangzw
     * @param $key
     * @param $value
     * @return bool|int
     * @date 2018-04-08
     */
    public function append($key, $value){
        if(!$key || !$value){
            return false;
        }

        return $this->redis->append($key, $value);
    }

    /**
     * TODO: 向无序集合添加一个或多个成员
     * @author wangzw
     * @param $key
     * @param $value1
     * @param null $value2
     * @param array ...$valueN
     * @return bool|int
     * @date 2018-04-07
     */
    public function sAdd($key, $value)
    {
        if(!$key || !$value){
            return false;
        }

        return $this->redis->sAdd($key, $value);
    }

    public function sAdds($key, ...$valueN)
    {

        return $this->redis->sAdd($key, ...$valueN);
    }

    /**
     * TODO: 向无序集合删除一个或多个成员
     * @author wangzw
     * @param $key
     * @param $value1
     * @param null $value2
     * @param array ...$valueN
     * @return bool|int
     * @date 2018-04-07
     */
    public function sRem($key, $value1, $value2 = null, ...$valueN)
    {
        return $this->redis->sRem($key, $value1, $value2, ...$valueN);
    }

    /**
     * TODO: 获取无序集合的成员数
     * @param $key
     * @return int
     * @date 2018-04-07
     */
    public function sCard($key)
    {
        if(!$key){
            return 0;
        }

        return $this->redis->sCard($key);
    }

    /**
     * TODO: 获取无序集合中的所有成员
     * @author wangzw
     * @param $key
     * @return array|false
     * @date 2018-04-07
     */
    public function sMembers($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->sMembers($key);
    }

    public function sRandMember($key, $num=10)
    {
        if(!$key){
            return false;
        }

        return $this->redis->sRandMember($key, $num);
    }


    public function sDiff($key1, $key2){
        return $this->redis->sDiff($key1, $key2);
    }

    /**
     * TODO: 判断 member 元素是否是集合 key 的成员
     * @author wangzw
     * @param $key
     * @param $member
     * @return array|false
     * @date 2018-12-04
     */
    public function sIsMember($key, $member)
    {
        return $this->redis->sIsMember($key, $member);
    }

    /**
     * TODO: 同时将多个field-value对设置到哈希表键中
     * @author wangzw
     * @param $key
     * @param $value
     * @return bool|false
     * @date 2018-04-12
     */
    public function hMSet($key, $value)
    {
        if(!$key || !is_array($value)){
            return false;
        }

        return $this->redis->hMset($key, $value);
    }

    /**
     * TODO: 将哈希表键中的字段的值设为对应值
     * @author wangzw
     * @param $key
     * @param $hash_key
     * @param string $value
     * @return int|bool
     * @date 2018-04-12
     */
    public function hSet($key, $hash_key, $value = '')
    {
        if(!$key || !$hash_key){
            return false;
        }

        return $this->redis->hSet($key, $hash_key, $value);
    }

    /**
     * TODO: 获取存储在哈希表中指定的字段的值
     * @author wangzw
     * @param $key
     * @param $field
     * @return bool|string
     * @date 2018-04-16
     */
    public function hGet($key, $field)
    {
        if(!$key || !$field){
            return false;
        }

        return $this->redis->hGet($key, $field);
    }

    /**
     * TODO: 获取在哈希表中指定键的所有字段的值
     * @author wangzw
     * @param $key
     * @return array|bool
     * @date 2018-04-16
     */
    public function hGetAll($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->hGetAll($key);
    }

    /**
     * TODO: 删除一个或多个哈希表字段
     * @author wangzw
     * @param $key
     * @param $field1
     * @param null $field2
     * @param array ...$fieldN
     * @return bool|int
     * @date 2018-04-16
     */
    public function hDel($key, $field1, $field2 = null, ...$fieldN)
    {
        if(!$key || !$field1){
            return false;
        }

        return $this->redis->hDel($key, $field1, $field2, ...$fieldN);
    }

    /**
     * TODO: 查看哈希表键中，指定的字段是否存在
     * @author wangzw
     * @param $key
     * @param $field
     * @return bool
     * @date 2018-04-16
     */
    public function hExists($key, $field)
    {
        if(!$key || !$field){
            return false;
        }

        return $this->redis->hExists($key, $field);
    }

    /**
     * TODO: 获取哈希表中的字段的数量
     * @author wangzw
     * @param $key
     * @return bool|int
     * @date 2018-04-16
     */
    public function hLen($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->hLen($key);
    }

    /**
     * TODO: 获取所有哈希表中的字段
     * @author wangzw
     * @param $key
     * @return array|bool
     * @date 2018-04-16
     */
    public function hKeys($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->hKeys($key);
    }

    /**
     * TODO: 获取哈希表中的值
     * @author wangzw
     * @param $key
     * @return array|bool
     * @date 2018-04-16
     */
    public function hVals($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->hVals($key);
    }


    /*********************有序集合操作*********************/
    /**
     * 给当前集合添加一个元素
     * 如果value已经存在，会更新order的值。
     * @param string $key
     * @param string $order 序号
     * @param string $value 值
     * @return bool
     */
    public function zAdd($key,$order,$value)
    {
        return $this->redis->zAdd($key,$order,$value);
    }
    /**
     * 给$value成员的order值，增加$num,可以为负数
     * @param string $key
     * @param string $num 序号
     * @param string $value 值
     * @return 返回新的order
     */
    public function zinCry($key,$num,$value)
    {
        return $this->redis->zinCry($key,$num,$value);
    }
    /**
     * 删除值为value的元素
     * @param string $key
     * @param stirng $value
     * @return bool
     */
    public function zRem($key,$value)
    {
        return $this->redis->zRem($key,$value);
    }
    /**
     * 集合以order递增排列后，0表示第一个元素，-1表示最后一个元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array|bool
     */
    public function zRange($key,$start,$end)
    {
        return $this->redis->zRange($key,$start,$end);
    }
    /**
     * 集合以order递减排列后，0表示第一个元素，-1表示最后一个元素
     * @param string $key
     * @param int $start
     * @param int $end
     * @return array|bool
     */
    public function zRevRange($key,$start,$end)
    {
        return $this->redis->zRevRange($key,$start,$end);
    }
    /**
     * 集合以order递增排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param string $key
     * @param int $start
     * @param int $end
     * @package array $option 参数
     *   withscores=>true，表示数组下标为Order值，默认返回索引数组
     *   limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array|bool
     */
    public function zRangeByScore($key,$start='-inf',$end="+inf",$option=array())
    {
        return $this->redis->zRangeByScore($key,$start,$end,$option);
    }
    /**
     * 集合以order递减排列后，返回指定order之间的元素。
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param string $key
     * @param int $start
     * @param int $end
     * @package array $option 参数
     *   withscores=>true，表示数组下标为Order值，默认返回索引数组
     *   limit=>array(0,1) 表示从0开始，取一条记录。
     * @return array|bool
     */
    public function zRevRangeByScore($key,$start='-inf',$end="+inf",$option=array())
    {
        return $this->redis->zRevRangeByScore($key,$start,$end,$option);
    }
    /**
     * 返回order值在start end之间的数量
     * @param unknown $key
     * @param unknown $start
     * @param unknown $end
     */
    public function zCount($key,$start,$end)
    {
        return $this->redis->zCount($key,$start,$end);
    }
    /**
     * 返回值为value的order值
     * @param unknown $key
     * @param unknown $value
     */
    public function zScore($key,$value)
    {
        return $this->redis->zScore($key,$value);
    }
    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * @param unknown $key
     * @param unknown $value
     */
    public function zRank($key,$value)
    {
        return $this->redis->zRank($key,$value);
    }
    /**
     * 返回集合以score递增加排序后，指定成员的排序号，从0开始。
     * @param unknown $key
     * @param unknown $value
     */
    public function zRevRank($key,$value)
    {
        return $this->redis->zRevRank($key,$value);
    }
    /**
     * 删除集合中，score值在start end之间的元素　包括start end
     * min和max可以是-inf和+inf　表示最大值，最小值
     * @param unknown $key
     * @param unknown $start
     * @param unknown $end
     * @return 删除成员的数量。
     */
    public function zRemRangeByScore($key,$start,$end)
    {
        return $this->redis->zRemRangeByScore($key,$start,$end);
    }
    /**
     * 返回集合元素个数。
     * @param unknown $key
     */
    public function zCard($key)
    {
        return $this->redis->zCard($key);
    }

    public function lPush($key, $val)
    {

        return $this->redis->lPush($key, $val);

    }

    public function lPushs($key, ...$arr)
    {

        return $this->redis->lPush($key, ...$arr);

    }

    public function lPop($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->lPop($key);
    }

    public function rPop($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->rPop($key);
    }


    public function lLen($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->lLen($key);
    }

    public function setNx($key, $value){

        return $this->redis->setnx($key, $value);
    }

    public function flushDB()
    {
        return $this->redis->flushDB();
    }

    public function flushAll()
    {
        return $this->redis->flushAll();
    }

    public function multi(){
        return $this->redis->multi();
    }

    public function exec(){
        return $this->redis->exec();
    }


    public function exists($key)
    {
        if(!$key){
            return false;
        }

        return $this->redis->exists($key);
    }
}