<?php

namespace app\im\util;

use Workerman\Worker;
use Workerman\Lib\Timer;
use GatewayWorker\Lib\Gateway;
use app\im\Factory;

// $interval = 60 * 1;
// $interval = 5; // 定时器间隔, 单位 秒
// $maxResend = 5; // 最大重发次数
// $minResendTime = 5; // 当发送时间过去多少秒后可以重发 
// $hashName = "im_chat_last_send_time_hash";
// $listName = "im_chat_resend_list";
// $onlineListName = "im_chat_online_user_set";
// $callingIdList = "im_calling_id_list";
// $callingIdTimeHash = "im_calling_idtime_hash";


function resend() {
    $hashName = config("redis_keys.cache_chat_last_send_time_key");
    $listName = config("redis_keys.cache_chat_resend_list_key");
    $maxResend = config("resend.max_resend_count");
    $minResendTime = config("resend.resend_interval");
    $onlineListName = config("redis_keys.cache_chat_online_user_key");
    
    $cache = Factory::getRedis(config("redis.host"));
    
    // 队列不存在, 没有要发送的消息
    if (!$cache->rawCommand("EXISTS", $listName)) {
//         echo "重发队列没有记录\n";
        return;
    }
    $msgSign = $cache->rawCommand("LINDEX", $listName, "-1");
    // 缓存没有数据
    if (!$msgSign) return;
    var_dump($msgSign);
    list($sign, $time, $count) = explode("-", $msgSign);
    $now = time();
    // 判断是否重发
    if ($now > $time+$minResendTime) {
        // 执行操作, 移除队列数据
        $cache->rawCommand("RPOP", $listName);
        $data = $cache->rawCommand("HGET", $hashName, $sign);
        if ($data) {
            list( "id"=>$id, "data"=>$data) =  json_decode($data, true);

            // 发送
            // Gateway::$method($id, $data);
            if (Gateway::isOnline($id)) {
                Gateway::sendToClient($id, $data);
                
                // 没有超过最大次数
                if (++$count <= $maxResend) {
                    // 更新
                    $cache->rawCommand("LPUSH", $listName, "$sign-$now-$count");
                } else {
                    // 删除详情数据
                    $cache->rawCommand("HDEL", $hashName, $sign);
                }
            } else {
                // 删除详情数据
                $cache->rawCommand("HDEL", $hashName, $sign);
            }
        }
        return resend();
    }
}

function checkLostConnection() {
    $callingIdList = config("redis_keys.im_calling_id_list_key");
    $callingIdTimeHash = config("redis_keys.im_calling_idtime_hash_key");
    $cache = Factory::getRedis(config("redis.host"));
    $oldId = $cache->rawCommand("LRANGE", $callingIdList, 0, 0);
    if(!empty($oldId[0])){
        $data = json_decode($cache->rawCommand("HGET", $callingIdTimeHash, $oldId[0]), true);
        if(!empty($data)){
            if($data['timestamp']+10 < time()){
                //掉线
                $cache->rawCommand("LPOP", $callingIdList);
                $cache->rawCommand("HDEL", $callingIdTimeHash, $oldId[0]);
                //发送请求到tp
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'https://im.5dx.ink/im/chat/OfflineProcessing');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, ['_ajax'=>true,'client_id'=>$oldId[0]]);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
                curl_exec($curl);
                curl_close($curl);
            }
        }
    }
    
}

Timer::add(config("resend.resend_interval"), "\\app\\im\\util\\resend");
// Timer::add(5, "\\app\\im\\util\\checkLostConnection");

