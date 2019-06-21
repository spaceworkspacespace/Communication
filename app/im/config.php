<?php 

return [
    "url_route_on"=>true,
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
//         'type'  => 'File',
        // 日志保存目录
//         'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
        "type" => "\\app\\im\\util\\SocketLogDriverImpl",
        "remote" => "127.0.0.1:1238",
        "remote_log_uid" => "socket_log"
    ],
    // gateway 设置
    "gateway" => [
        "remote" => "127.0.0.1:1238",
        "client_connect" => "ws://192.168.0.80:8080",
        "remote_log_uid" => "socket_log"
    ],
    // 程序的一些配置.
    "im" => [
        // 用于缓存中的命名
        "cache_chat_last_send_time_key"=>"im_chat_last_send_time_hash", // 保存消息重发的消息详情
        "cache_chat_resend_list_key"=>"im_chat_resend_list", // 排列需要重发的消息
        "cache_chat_read_message_key"=>"im_chat_read_message_hash", // 暂存已读消息的 id.
        "cache_chat_online_user_key"=>"im_chat_online_user_set", // 在线用户的信息
        "im_chat_calling_communication_hash_key"=>"im_chat_calling_communication_info_hash",
        "im_call_calling_communicating_list_key" => "im_call_calling_communicating_list",
        "cache_calling_communication_info_hash_key" => "im_chat_calling_communication_info_hash",
        "im_calling_id_list_key"=>"im_calling_id_list",//通话中id
        "im_calling_idtime_hash_key"=>"im_calling_idtime_hash"//key = id  value = 时间
    ]
//     ,
//     "cache"=>[
//         "type"=>"Redis",
//         "host"       => '127.0.0.1'
//     ]
];