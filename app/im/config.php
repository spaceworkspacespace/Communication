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
        "remote_log_uid" => "socket_log3"
    ],
    // gateway 设置
    "gateway" => [
        "remote" => "192.168.0.80:1238",
        "remote_log_uid" => "socket_log3"
    ]
//     ,
//     "cache"=>[
//         "type"=>"Redis",
//         "host"       => '127.0.0.1'
//     ]
];