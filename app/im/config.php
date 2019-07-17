<?php 

return [
    "url_route_on"=>true,
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        // 'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
        
        // log 页面的日志配置
        "type" => "\\app\\im\\util\\SocketLogDriverImpl",
        "remote" => "127.0.0.1:1238",
        "remote_log_uid" => "socket_log"
    ],
    // gateway 设置
    "gateway" => [
        "remote" => "127.0.0.1:1238",
        "client_connect" => "ws://chat.pybycl.com:8080",
        "remote_log_uid" => "socket_log"
    ],
    // 程序的一些配置.
    "im" => [
        // ICE 配置
        "iceserver"=> [
            "iceServers"=> [
                // [ "urls" => "stun:im.5dx.ink:3479"],
                [ "urls" => "stun:stun1.l.google.com:19302"],
                // [ "urls" => "turn:im.5dx.ink:3478"],
            ]
        ],
        
        // 用于缓存中的命名
        "cache_chat_last_send_time_key"=>"im_chat_last_send_time_hash", // 保存消息重发的消息详情
        "cache_chat_resend_list_key"=>"im_chat_resend_list", // 排列需要重发的消息
        "cache_chat_read_message_key"=>"im_chat_read_message_hash", // 暂存已读消息的 id.
        "cache_chat_online_user_key"=>"im_chat_online_user_set", // 在线用户的信息
        "im_chat_calling_communication_hash_key"=>"im_chat_calling_communication_info_hash",
        "im_call_calling_communicating_list_key" => "im_call_calling_communicating_list",
        
        // RTC 通话字段
        "im_calling_id_list_key"=>"im_calling_id_list", // 通话中id
        "im_calling_idtime_hash_key"=>"im_calling_idtime_hash", // key = id  value = 时间
        "cache_calling_communication_info_hash_key" => "im_chat_calling_communication_info_hash", // 会话的详情数据
        "im_calling_comm_user_hash"=>"im_call_calling_user_{userId}_hash", // 会话的用户信息
        "im_calling_comm_group_hash"=>"im_call_calling_gruop_{groupId}_hash", // 会话的群聊 id
        
        "redis_keys"=>[
            "user_h"=>"im_call_calling_user_{userId}_hash",
            "group_h"=> "im_call_calling_gruop_{groupId}_hash",
            "detail_h"=>"im_chat_calling_communication_info_hash",
            "calling_l"=>"im_calling_id_list",
            "calling_h"=>"im_calling_idtime_hash",
        ]
    ],
//     ,
    'cache'                  => [
        'type'  =>  'complex',
        "default"=>[
            // 驱动方式
            'type'   => 'File',
            // 缓存保存目录
            'path'   => CACHE_PATH,
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        "file"=>[
            // 驱动方式
            'type'   => 'File',
            // 缓存保存目录
            'path'   => CACHE_PATH,
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        "redis"=> [
            "type"=>"\\app\\im\\util\\RedisCacheDriverImpl",
            // "host"       => '127.0.0.1'
            "host"=>APP_DEBUG? getenv("REDIS_HOST"): "127.0.0.1",
        ]
    ],
];