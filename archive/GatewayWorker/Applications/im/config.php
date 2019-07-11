<?php
return [
    // 消息重发设置
    "resend" => [
        "max_resend_count" => 5, // 重发次数
        "resend_interval" => 6, // 重发间隔 秒
        "timeout"=> 5, // 超时时间
    ],
    // 通话掉线设置
    "losecall"=>[
        "max_check_count"=> 3, // 最大检查次数, 超过次数就掉线.
        "check_interval"=> 3, // 检查间隔
        "ping_data"=> "c", // 在通话期间证明在线的字段 (确保掉线后, 或客户端逻辑已经完成后能尽快发现)
        "timeout"=> 5, // 超时时间
    ],
    "redis_keys" => [
        "cache_chat_last_send_time_key" => "im_chat_last_send_time_hash", // 保存消息重发的消息详情
        "cache_chat_resend_list_key" => "im_chat_resend_list", // 排列需要重发的消息
        "cache_chat_online_user_key" => "im_chat_online_user_set", // 在线用户的信息
        "im_calling_id_list_key" => "im_calling_id_list", // 通话中id
        "im_calling_idtime_hash_key" => "im_calling_idtime_hash", // key = id value = 时间
        // 换个简短的名字...
        "user_h"=>"im_call_calling_user_{userId}_hash",
        "group_h"=> "im_call_calling_gruop_{groupId}_hash",
        "detail_h"=>"im_chat_calling_communication_info_hash",
        "calling_l"=>"im_calling_id_list",
        "calling_h"=>"im_calling_idtime_hash",
    ],
    "gateway" => [
        "register_host" => "0.0.0.0",
        "register_port" => "1238",
        "ws_port" => 8080,
        "cert"=>__DIR__ . "/conf/chat.pybycl.com.crt",
        "pk"=>__DIR__ . "/conf/chat.pybycl.com.key"
    ],
    "redis" => [
        "host" => CONTAINERIZATION? getenv("REDIS_HOST"): "127.0.0.1",
        "port"=> CONTAINERIZATION? getenv("REDIS_PORT"): "6379",
    ],
    "urls"=>[
        // 主机设置
        "protocol"=>"https",
        "host"=>"im.5dx.ink",
        "port"=>"",
        // 资源路径
        "chat"=> [
            "offlineProcessing"=>"/im/chat/offlineprocessing"
        ]
    ]
];