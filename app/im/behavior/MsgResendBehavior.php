<?php
namespace app\im\behavior;

use app\im\model\RedisModel;

class MsgResendBehavior
{

    public static function run(&$params)
    {
        var_dump($params);
        return;
        // 获取到消息内容
        $data = $params["payload"]["data"];
        $cache = RedisModel::getRedis();
        // 获取使用缓存的键名
        $hashName = config("im.cache_chat_last_send_time_key");
        $listName = config("im.cache_chat_resend_list_key");
        foreach($data as $m) {
//             $cache->rawCommand("")
        }
        // 存入 redis
        $data["timestamp"] = time();
    }
}