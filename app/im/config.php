<?php 

return [
    "url_route_on"=>true,
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
        // "type" => "\\app\\im\\util\\SocketLogDriverImpl",
        // "remote" => "192.168.0.80:1238",
        "remote" => "127.0.0.1:1238",
        "remote_log_uid" => "socket_log"
    ],
    // gateway 设置
    "gateway" => [
        "remote" => "127.0.0.1:1238",
        "client_connect" => "ws://39.97.52.10:8080",
        "remote_log_uid" => "socket_log"
    ],
    // 程序的一些配置.
    "im" => [
        // 用于缓存中的命名
        "cache_chat_last_send_time_key"=>"im_chat_last_send_time_hash", // 保存消息重发的消息详情 
        "cache_chat_resend_list_key"=>"im_chat_resend_list", // 排列需要重发的消息
        "cache_chat_read_message_key"=>"im_chat_read_message_hash", // 暂存已读消息的 id.
        "cache_chat_online_user_key"=>"im_chat_online_user_set", // 在线用户的信息
    ]
//     ,
//     "cache"=>[
//         "type"=>"Redis",
//         "host"       => '127.0.0.1'
//     ]
];