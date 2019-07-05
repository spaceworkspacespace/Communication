<?php
/**
 * 配置文件
 */
return [
    // 数据库类型
    'type'     => 'mysql',
    // 服务器地址
    'hostname' => APP_DEBUG? getenv("MYSQL_HOST"): "127.0.0.1",
    // 'hostname' => "127.0.0.1",
    // 数据库名
    'database' => 'thinkcmf5',
    // 用户名
    'username' => 'root',
    // 密码
    'password' => '123456',
    // 端口
    // 'hostport' => '3306',
    'hostport' => APP_DEBUG? getenv("MYSQL_PORT"): '3306',
    // 数据库编码默认采用utf8
    'charset'  => 'utf8mb4',
    // 数据库表前缀
    'prefix'   => 'cmf_',
    "authcode" => 'LZinoHLtxrj9T3eSaG',
    //#COOKIE_PREFIX#
];
