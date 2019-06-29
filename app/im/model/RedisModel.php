<?php
namespace app\im\model;

use app\im\exception\RedisConnException;
use think\Cache;

class RedisModel
{

    private static $redisInstance;
    // 缓存字段名, 不用每次都重新获取.
//     private static $fieldNameCache = [];

    public static function del($key, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("DEL", $key);
    }
    
    public static function exists($key, $cache=null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("EXISTS", $key);
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
        $field = str_template($field, $data);
        return $field;
    }
    
    public static function hdel($key, $field, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
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
        return $cache->rawCommand("HGETALL", $key);
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
    
    public static function lrem($key, $value, $count=1, $cache = null) {
        if ($cache === null) $cache = static::getRedis();
        return $cache->rawCommand("LREM", $key, $count, $value);
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