<?php
namespace app\im;

class Factory {
    
    // redis host 和对象的 map
    private static $_redisMap = [];
    
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
}