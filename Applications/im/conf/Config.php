<?php
namespace  app\im\conf;
require_once __DIR__.'/../exception/IllegalStateException.php';

use app\im\exception\IllegalStateException;

class Config {
    private static $default_conf = null;
    
    public static function setDefaultConf(array $conf): void {  
        if (static::$default_conf != null) {
            throw new IllegalStateException("默认配置已经设置.");
        }
        static::$default_conf = $conf;
    }
    
    public static function getFromDefault(string $name, string $sp = ".") {
        $k = explode($sp, $name);
        return array_reduce($k, function($config, $key) {
            return $config[$key];
        }, static::$default_conf);
    }
}