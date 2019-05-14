<?php
namespace app\im\service;

use GatewayClient\Gateway;

class PushService implements IPushService
{

    public function pushAll($uid): bool
    {
        $this->pushMsgBoxNotification($uid);
        $this->pushUnreadMessage($uid);
        return true;
    }

    public function pushUnreadMessage($uid, $contactId = null, $type=IChatService::CHAT_FRIEND): bool
    {
        // $friend = $group = [];
        if (!Gateway::isUidOnline($uid)) {
            return true;
        }
        $message = SingletonServiceFactory::getChatService()->getUnreadMessage($uid, 1, 500, $type, $contactId);
        SingletonServiceFactory::getGatewayService()->sendToUser($uid, $message, IGatewayService::MESSAGE_TYPE, array_column($message, "cid"));
        /*
        if ($contactId) {
            switch($type) {
                case IChatService::CHAT_FRIEND:
                    $friend = $chatService->getUnreadMessage($uid, $contactId, IChatService::CHAT_FRIEND)->toArray();
                    break;
                case IChatService::CHAT_GROUP:
                    $group = $chatService->getUnreadMessage($uid, $contactId, IChatService::CHAT_GROUP)->toArray();
                    break;
            }
        } else {
            // 循环执行 sql, 之后再改吧.
            // 获取所有好友和分组
            $friends = model("friends")->getFriends($uid)->toArray();
            $groups = model("groups")->getGroups($uid)->toArray();
            // 查询所有未读消息
            //             var_dump($friends);
            foreach ($friends as $item) {
                //                 var_dump([$uid, $item["contact_id"]]);
                $result = $chatService->getUnreadMessage($uid, $item["contact_id"], IChatService::CHAT_FRIEND)
                    ->toArray();
                
                $result = array_map(function($entry)use($uid, $item){
                    if ($entry["id"] == $uid) {
                        return array_merge($entry, [
                            "id"=> $item["contact_id"],
                            "mine"=>true
                        ]);
                    }
                    return array_merge($entry, [
                        "id"=> $item["contact_id"],
                        "mine"=>false
                    ]);
                }, $result);
                    
                    $friend = array_merge($friend, $result);
            }
            
            foreach ($groups as $item) {
                $result = $chatService->getUnreadMessage($uid, $item["contact_id"], IChatService::CHAT_GROUP);
                $group = array_merge($group, $result);
            }
        }
        // 执行推送
        $group = array_map(function($item) {
            return [
                "username"=>$item["username"],
                "avatar"=>$item["avatar"],
                "id"=>$item["group_id"],
                "type"=>"group",
                "content"=>$item["content"],
                "cid"=>$item["cid"],
                "mine"=>false,
                "fromid"=>$item["id"],
                "timestamp"=>$item["send_date"]*1000,
                //                 "require"=> true // 表示前端强制添加此消息, 用于和自己发送的消息相区分
            ];
        }, $group);
            $friend = array_map(function($item) {
                return [
                    "username"=>$item["username"],
                    "avatar"=>$item["avatar"],
                    "id"=>$item["id"],
                    "type"=>"friend",
                    "content"=>$item["content"],
                    "cid"=>$item["cid"],
                    "mine"=>$item["mine"],
                    "fromid"=>$item["id"],
                    "timestamp"=>$item["send_date"]*1000,
                    "require"=> true
                ];
            }, $friend);
                GatewayServiceImpl::msgToUid($uid, $group);
                GatewayServiceImpl::msgToUid($uid, $friend);
                */
                return true;
    }

    public function pushMsgBoxNotification($uid): bool
    {
        try {
            // 不在线直接返回
            if (!Gateway::isUidOnline($uid)) {
                return true;
            }
            
            $count = model("msg_box")->getUnreadMsgCountByUser($uid);
            im_log("debug", "未读通知: ", $count);
            if (is_numeric($count) && $count > 0) {
                GatewayServiceImpl::askToUid($uid, ["msgCount"=>$count]);
            }
            return true;
        } catch (\Exception $e) {
            im_log("error", "消息盒子检查失败 ! id: $uid", $e);
        }
        return false;
        
    }
}