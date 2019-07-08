<?php
namespace app\im\model;

use app\im\exception\RedisConnException;
use think\Cache;

class RedisModel
{

    protected static $redisInstance;
    // 缓存字段名, 不用每次都重新获取.
//     private static $fieldNameCache = [];

    public static function __callstatic($method, $arguments) {
        return call_user_func_array([static::getRedis(), $method], $arguments);
    }
    
    public static function del($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("DEL", $key);
    }
    
    public static function exists($key, $cache=null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("EXISTS", $key);
    }
    
    public static function get($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("GET", $key);
    }
    
    /**
     *
     * @param bool $isNew
     * @throws RedisConnException
     * @return \Redis
     */
    public static function getRedis(bool $isNew = false)
    {
        if ($isNew) {
            return Cache::connect(config("cache.redis"), true)->handler();
        } else if (! static::$redisInstance) {
            static::$redisInstance = Cache::store("redis")->handler();
        }
        return static::$redisInstance;
    }
    
    public static function getKeyName($name, $data=[]) {
        $field = config("im.$name");
        
        if (!is_string($field)) {
            $fields = config("im.redis_keys");
            if (!isset($fields["$name"])) return '';
            $field = $fields["$name"];
        }
        
        $field = str_template($field, $data);
        return $field;
    }
    
    public static function hdel($key, $field, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        println(implode(" ", ["HDEL", $key, $field]));
        return $cache->rawCommand("HDEL", $key, $field);
    }
    
    public static function hexists($key, $field, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("HEXISTS", $key, $field);
    }
    
    public static function hget($key, $field, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("HGET", $key, $field);
    }
    
    public static function hgetallJson($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        // hgetall 返回的是一个数组, 如在 redis-cli 中一样.
        $hall = $cache->rawCommand("HGETALL", $key);
        $result = [];
        for ($i=0; $i<count($hall); $i+=2) {
            $value = json_decode($hall[$i+1], true);
            if (is_null($value) && $hall[$i+1] !== "null") {
                $result[$hall[$i]] = $hall[$i+1];
            } else {
                $result[$hall[$i]] = $value;
            } 
        }
        return $result;
    }
    
    public static function hgetJson($key, $field, $cache = null) {
        $value = static::hget($key, $field, $cache);
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }
    
    public static function hkeys($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("HKEYS", $key);
    }
    
    public static function hset($key, $field, $value, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("HSET", $key, $field, $value);
    }
    
    public static function hsetJson($key, $field, $value, $cache = null) {
        return static::hset($key, $field, json_encode($value), $cache);
    }
    
    public static function lindex($key, $index, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("LINDEX", $key, $index);
    }
    
    public static function llen($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("LLEN", $key);
    }
    
    public static function lpush($key, $value, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("LPUSH", $key, $value);
    }
    
    public static function lrange($key, $start=0, $stop=null, $cache = null) {
        if (is_null($cache)) $cache = static::getRedis();
        if (is_null($stop)) $stop = static::llen($key);
        return $cache->rawCommand("LRANGE", $key, $start, $stop);
    }
    
    public static function lrem($key, $value, $count=1, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("LREM", $key, $count, $value);
    }
    
    /**
     * 将 $key1 尾部元素(最早加入的) 移除, 加入到 $key2 头部 (最新的, 最后加入的).
     * @param string $key1
     * @param string $key2
     * @param mixed $cache
     * @return string 移动的元素
     */
    public static function rpoplpush($key1, $key2, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("RPOPLPUSH", $key1, $key2);
    }

    public static function sismember($key, $value, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("SISMEMBER", $key, $value);
    }
    
    public static function smembers($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("SMEMBERS", $key);
    }
   
}