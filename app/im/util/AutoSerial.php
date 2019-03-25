<?php
namespace app\im\util;

/**
 * 单例模式的自动序列化类
 * @author silence
 *
 */
abstract class AutoSerial {
    public const serialVersionUID = "im_serial_AutoSerial";
    protected static $instance = null;
        
    /**
     * 获取单例
     * @return static
     */
    public static function getInstance() {
        if (!static::$instance) {
            static::$instance = SerialUtil::unserialize(static::serialVersionUID);
//             im_log("debug", static::$instance);
            if (!static::$instance) static::$instance = new static();
        }
        return static::$instance; 
    }
    
    public function __destruct() {
//         im_log("info", "序列化 ", get_class($this), ", uid: ", static::serialVersionUID);
        SerialUtil::serialize(static::serialVersionUID, self::$instance);
    }
}