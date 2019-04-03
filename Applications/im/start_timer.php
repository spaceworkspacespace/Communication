<?php

namespace app\im\util;

use Workerman\Worker;
use Workerman\Lib\Timer;
use GatewayWorker\Lib\Gateway;

// $interval = 60 * 1;
$interval = 5; // 定时器间隔, 单位 秒
$maxResend = 1000; // 最大重发次数
$minResendTime = 3; // 当发送时间过去多少秒后可以重发 
Gateway::$registerAddress = "0.0.0.0:1238";
$hashName = "im_chat_last_send_time_hash";
$listName = "im_chat_resend_list";
$cache = new \Redis();
$cache->connect("192.168.0.80");

function resend() {
    global $cache, $hashName, $listName, $maxResend, $minResendTime;
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

Timer::add($interval, "\\app\\im\\util\\resend");

