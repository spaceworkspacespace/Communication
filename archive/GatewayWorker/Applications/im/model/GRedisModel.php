<?php
namespace app\im\model;
use app\im\Factory;

/**
 * @method lock($name)
 * @author silence
 *
 */
class GRedisModel extends RedisModel {
    use TRedisLock;
    
    public function handler() {
        return Factory::getRedis(config("redis.host"));
    }
    
    /**
     *
     * @param bool $isNew
     * @return \Redis
     */
    public static function getRedis(bool $isNew = false)
    {
        if ($isNew) {
            return Factory::getRedis(config("redis.host"), config("redis.port"), true);
        } else if (! static::$redisInstance) {
            static::$redisInstance = Factory::getRedis(config("redis.host"));
        }
        return static::$redisInstance;
    }
    
    public static function getKeyName($name, $data=[]) {
        $field = config("redis_keys.$name");
        $field = str_template($field, $data);
        return $field;
    }

    public function __call($method, $arguments) {
        return static::$method(...$arguments);
    }
}