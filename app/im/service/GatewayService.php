<?php
namespace app\im\service;

use GatewayClient\Gateway;
use think\Hook;
use app\im\model\RedisModel;
use function Qiniu\base64_urlSafeEncode;

class GatewayService implements IGatewayService
{
    public function cacheMessage($data) {
//         im_log("debug", $data);
        $cache = RedisModel::getRedis();
        $hashName = config("im.cache_chat_last_send_time_key");
        $listName = config("im.cache_chat_resend_list_key");
        $timestamp = time();
        $messageId = $data["mark"]; 
        $messageDesc = implode([
            $messageId,
            "-",
            $timestamp, // 最后发送的时间
            "-",
            1 // 发送的次数
        ]);
        
        // 加入缓存
        $cache->rawCommand("LPUSH", $listName, $messageDesc);
//         im_log("debug", $res);
        
        $cache->rawCommand("HSET", $hashName, $messageId,  json_encode($data));
//         im_log("debug", "$res, $hashName, $listName,  ", json_encode($data));
    }
    
    public function addToUid($uid, $data): void {
        $data = [
            "id"=>"u-$uid", // 用于客户端识别密码的 key
            "payload"=> [
                "type"=>self::ADD_TYPE,
                "data"=>$data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }
    
    public function assembleClientDataByGroupId($gid, $rawData, $data, $cids) {
        $userIds = model("groups")->getUserIdInGroup($gid);
        
        $result = [];
        foreach($userIds as $uid) {
            $addition = static::assembleCliendDataByUid($uid, $rawData, $data, $cids);
            $result = array_merge($result, $addition);
        }
        
        return $result;
    }
    
    public function assembleCliendDataByUid($uid, $rawData, $data, $cids) {
        $lcgValue = lcg_value();
        $timestamp = time();
        $clientIds = Gateway::getClientIdByUid($uid);
        
        $result = [];
        foreach($clientIds as $clientId) {
//             im_log("debug", $cids);
            // 每个客户端都特有的消息签名
            $sign = base64_urlSafeEncode(implode([$uid, $lcgValue, $timestamp]));
            $data["sign"] = $sign;
//             $type = explode("-", $data["id"])[0];
//             im_log("debug", $type, ", ", $type !== "u"? "group": "friend");
//             $type = $type !== "u"? "group": "friend";
            // 消息负载
            $addition = [
                // 发送时的参数
                "id"=>$clientId,
                "data" => json_encode($data),
                // 其他描述信息
                "cids"=>$cids,
                "mark"=>$sign,
//                 "type"=>$type
                "rawdata"=>$rawData,
            ];
            array_push($result, $addition);
        }
        
        return $result;
    }
    
    public function msgToClient($clientId, $data): void
    {
        $uid = Gateway::getUidByClientId($clientId);
        // 获取所有消息 id
        $cids = array_column($data, "cid");
        $sign = base64_urlSafeEncode(implode([$clientId, lcg_value(), time()]));
        
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "id"=>$clientId,
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ],
            "sign"=>$sign
        ];
        
        $rawData = $data;
        
        Hook::listen("gateway_send", $data);
        Gateway::sendToClient($clientId, $data);
        
        $addition = [
            // 发送时的参数
            "id"=>$clientId,
            "data" => $data,
            // 其他描述信息
            "cids"=>$cids,
            "mark"=>$sign,
            "rawdata"=>$rawData
        ];
        
        static::cacheMessage($addition);
    }
    
    public function msgToGroup($group, $data): void
    {
        $userIds = model("groups")->getUserIdInGroup($group);
        foreach($userIds as $userId) {
            static::msgToUid($userId, $data);
        }
        /*
//         $method = "sendToGroup";
//         $sign = base64_urlSafeEncode(implode([$method, $group, lcg_value(), time()]));
        $cids = array_column($data, "cid");
        $data = [
            "id" => "g-$group",
            "payload" => [
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ]
//             "sign"=>$sign
        ];
        $rawData = $data;
        Hook::listen("gateway_send", $data);
        Gateway::sendToGroup($group, $data);
        $msgs = static::assembleClientDataByGroupId($group, $rawData, json_decode($data, true), $cids);
//         im_log("debug", $msgs);
        foreach($msgs as $msg) {
            static::cacheMessage($msg);
        }
        
//         $addition = [
//             // 发送时的参数
//             "method" => $method,
//             "id"=>$group,
//             "data" => $data,
//             // 其他描述信息
//             "cids"=>$cids,
//             "mark"=>$sign
//         ];
//         static::cacheMessage($addition);
        */
    }

    public function msgToUid($uid, $data): void
    {
        $clientIds = Gateway::getClientIdByUid($uid);
        foreach ($clientIds as $id) {
            static::msgToClient($id, $data);
        }
        /*

//         $sign = base64_urlSafeEncode(implode([$uid, lcg_value(), time()]));
        $cids = array_column($data, "cid");
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ],
//             "sign"=>$sign
        ];
        
        $rawData = $data;
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
        $msgs = static::assembleCliendDataByUid($uid, $rawData, json_decode($data, true), $cids);
        foreach ($msgs as $msg) {
            static::cacheMessage($msg);
        }
//         $addition = [
//             // 发送时的参数
//             "id"=>$uid,
//             "data" => $data,
//             // 其他描述信息
//             "cids"=>$cids,
//             "mark"=>$sign
//         ];
//         static::cacheMessage($addition);
        */
    }

    public function askToClient($clientId, $data): void
    {
        $uid = Gateway::getUidByClientId($clientId);
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::ASK_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToClient($clientId, $data);
    }

    public function askToUid($uid, $data): void
    {
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::ASK_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }

    public function sendToClient($clientId, $data, $type, $cids=[], $resend=3) {
        $uid = Gateway::getUidByClientId($clientId);
        $sign = base64_urlSafeEncode(implode([$clientId, lcg_value(), time()]));
        
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "id"=>$clientId,
                "type" => $type,
                "data" => $data
            ],
            "sign"=>$sign
        ];
        
        $rawData = $data;
        
        Hook::listen("gateway_send", $data);
        Gateway::sendToClient($clientId, $data);
        
        $addition = [
            // 发送时的参数
            "id"=>$clientId,
            "data" => $data,
            // 其他描述信息
            "cids"=>$cids,
            "mark"=>$sign,
            "rawdata"=>$rawData
        ];
        
        static::cacheMessage($addition);
    }
    
    public function sendToUser($userId, $data, $type, $cids = [], $resend = 3)
    {
        $clientIds = Gateway::getClientIdByUid($userId);
        foreach ($clientIds as $id) {
            static::sendToClient($id, $data, $type, $cids = [], $resend = 3);
        }
    }
    
    public function sendToGroup($groupId, $data, $type, $cids = [], $resend = 3)
    {
        $userIds = model("groups")->getUserIdInGroup($groupId);
        foreach($userIds as $userId) {
            static::sendToUser($userId, $data, $type, $cids = [], $resend = 3);
        }
    }
    
    public function updateToUid($uid, $data): void
    {
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::UPDATE_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }
}