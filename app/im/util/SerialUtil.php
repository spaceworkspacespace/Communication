<?php
namespace app\im\util;

class SerialUtil {
    /**
     * 序列化一个对象保存在缓存中
     * @param string $tag
     * @param mixed $obj
     * @param boolean $replace
     */
    public static function serialize(string $tag, $obj, $replace=true) {
        $cache = cache(config("cache.redis"));
//         im_log("debug", $obj);
        if ($replace){
            $cache->set($tag, serialize($obj));
        } else {
            if (!$cache->has($tag)) {
                $cache->set($tag, serialize($obj));
            }
        }
    }
    
    /**
     * 对象反序列化
     * @param string $tag
     * @return mixed 对象或 false.
     */
    public static function unserialize(string $tag) {
        $cache = cache(config("cache.redis"));
        $storable = $cache->get($tag);
        if ($storable) {
//             im_log("debug", $storable);
            return unserialize($storable);
        } else {
            return $storable;
        }
    }
}