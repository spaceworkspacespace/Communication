<?php 

return [
    "url_route_on"=>true,
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
    //         'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
        "type" => "\\app\\im\\util\\SocketLogDriverImpl",
        "remote" => "192.168.0.80:1238",
        "remote_log_uid" => "socket_log14"
    ],
    // gateway 设置
    "gateway" => [
        "remote" => "192.168.0.80:1238",
        "remote_log_uid" => "socket_log14"
    ],
    // 程序的一些配置.
    "im" => [
        // 加密 key 保存的名称
        "keys_name" => "im_keys"
    ]
//     ,
//     "cache"=>[
//         "type"=>"Redis",
//         "host"       => '127.0.0.1'
//     ]
];