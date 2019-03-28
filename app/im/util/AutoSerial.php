<?php
namespace app\im\util;

/**
 * 单例模式的自动序列化类
 * @author silence
 *
 */
abstract class AutoSerial {
    public const serialVersionUID = "im_serial_app\\im\\util\\AutoSerial";
    protected static $instance = null;
        
    /**
     * 获取单例
     * @return static
     */
    public static function getInstance() {
        if (!static::$instance) {
            static::$instance = SerialUtil::unserialize(static::getSerialVersionUID());
            if (!static::$instance) static::$instance = new static();
        }
        return static::$instance; 
    }
    
    public function __destruct() {
        SerialUtil::serialize(static::getSerialVersionUID(), static::$instance);
    }
    
    private static function getSerialVersionUID() {
        // 如果子类重新声明了 serialVersionUID, 就用子类的, 否则用 im_serial_类名.
        $class = static::class;
        if (declareConstant($class, "serialVersionUID")) {
            return static::serialVersionUID;
        } else {
            return "im_serial_$class";
        }
    }
}

/**
 * 判断类是否声明常量
 * @param string $class
 * @param string $name
 * @return bool
 */
function declareConstant($class, string $name): bool {
    $declareClass = (new \ReflectionClass($class))
        ->getReflectionConstant($name)
        ->getDeclaringClass()
        ->getName();
    return $class === $declareClass;
}