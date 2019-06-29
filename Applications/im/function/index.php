<?php
require_once __DIR__.'/../conf/Config.php';

\app\im\conf\Config::setDefaultConf(include __DIR__."/../config.php");

function config(string $name, $sp = ".") {
    return \app\im\conf\Config::getFromDefault($name, $sp);
}

function app_autoload($className) {
    $appDir = "Applications";
    $pathPart = explode("\\", $className);
    $classPath = "";
    
    // 不是能处理的类
    if (!is_array($pathPart) 
        || count($pathPart) <= 1 
        || $pathPart[0] !== "app") {
        return;
    }
    array_shift($pathPart);
    // 构成类路径
    $classPath = implode(DIRECTORY_SEPARATOR, 
            array_merge(
                [PROJECT_ROOT_DIR, $appDir], 
                $pathPart))
            .".php";
    
    include $classPath;
}
// 注册自动加载器
spl_autoload_register("app_autoload", true, true);