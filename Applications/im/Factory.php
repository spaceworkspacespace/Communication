<?php
namespace app\im;

use app\im\model\GRedisModel;
use app\im\service\IntervalService;

class Factory {
    
    // redis host 和对象的 map
    private static $_redisMap = [];
    private static $_modelMap = [];
    private static $_serviceMap = [];
    
    /**
     * 获取一个共享的 redis 对象或重新生成一个 redis 对象
     * @param string $host
     * @param string $port
     * @param bool $new
     */
    public static function getRedis(string $host, int $port = 6379, bool $new = false) {
        $redis = null;
        if ($new) {
            $redis = new \Redis();
            $redis->connect($host, $port);
        } else if (isset(static::$_redisMap["$host:$port"])) {
            $redis = static::$_redisMap["$host:$port"];
        } else {
            $redis = static::$_redisMap["$host:$port"] = new \Redis();
            $redis->connect($host, $port);
        }
        return $redis;
    }
    
    /**
     * 
     * @return \app\im\model\GRedisModel
     */
    public static function getReidsModel()  {
        if (!isset(static::$_modelMap["redis"])) {
            static::$_modelMap["redis"] = new GRedisModel();
        }
        return static::$_modelMap["redis"];
    }
    
    public static function getIntervalService() {
        if (!isset(static::$_serviceMap["interval"])) {
            static::$_serviceMap["interval"] = new IntervalService();
        }
        return static::$_serviceMap["interval"];
    }
}