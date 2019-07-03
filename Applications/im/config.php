<?php
return [
    // 消息重发设置
    "resend" => [
        "max_resend_count" => 5, // 重发次数
        "resend_interval" => 5 // 重发间隔 秒
    ],
    "redis_keys" => [
        "cache_chat_last_send_time_key" => "im_chat_last_send_time_hash", // 保存消息重发的消息详情
        "cache_chat_resend_list_key" => "im_chat_resend_list", // 排列需要重发的消息
        "cache_chat_online_user_key" => "im_chat_online_user_set", // 在线用户的信息
        "im_calling_id_list_key" => "im_calling_id_list", // 通话中id
        "im_calling_idtime_hash_key" => "im_calling_idtime_hash" // key = id value = 时间
    ],
    "gateway" => [
        "register_host" => "0.0.0.0",
        "register_port" => "1238",
        "ws_port" => 8080
    ],
    "redis" => [
        "host" => "127.0.0.1"
    ]
];