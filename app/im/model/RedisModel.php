<?php
namespace app\im\model;

use app\im\exception\RedisConnException;

class RedisModel
{

    private static $redisInstance;

    /**
     * 
     * @param bool $isNew
     * @throws RedisConnException
     * @return \Redis
     */
    public static function getRedis(bool $isNew = false)
    {
        if ($isNew) {
            return new \Redis();
        } else if (! static::$redisInstance) {
            $redis = static::$redisInstance = new \Redis();
            // im_log("debug", config("cache.redis.host"), ", ", config("cache.redis.port"), ", ", config("cache.redis.host"));
            $host = config("cache.redis")["host"];
            $port = isset(config("cache.redis")["port"]) ? config("cache.redis")["port"] : 6379;
            if (! $redis->connect($host, $port)) {
                throw new RedisConnException("redis 连接失败! $host\:$port");
            }
            if ($pwd = isset(config("cache.redis")["password"]) ? config("cache.redis")["password"] : null) {
                $redis->auth($pwd);
            }
        }
        return static::$redisInstance;
    }
}