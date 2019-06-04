<?php
namespace app\im\service;

use GatewayClient\Gateway;
use app\im\exception\OperationFailureException;
use app\im\model\RedisModel;
use app\im\model\ModelFactory;

class ChatService implements IChatService {
    
    public function getMessage($userId, $pageNo = 1, $pageSize = 50, $chatType = null, $id = null)
    {
        foreach([$userId, $pageNo, $pageSize] as $item) {
            if (!is_numeric($item)) {
                throw new OperationFailureException("参数类型错误");
            }
        }
        $result = [];
        // 查询所有用户和好友的聊天记录
        if ($chatType != IChatService::CHAT_FRIEND 
            && $chatType != IChatService::CHAT_GROUP) {
            $friendIds = model("friends")->getFriendIds($userId);
            // 查询所有的好友聊天记录
            foreach($friendIds as $fid) {
                $ms = model("chat_user")->getMessageByUser($userId, $fid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 所有群聊的 id
            $groupIds = model("groups")->getGroupIds($userId);
            foreach($groupIds as $gid) {
                $ms = model("chat_group")->getMessageByUser($userId, $gid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 查询好友消息
        } else if ($chatType != IChatService::CHAT_GROUP ) {
            // 查询单个用户的聊天记录
            if ($id != null) {
                $result = model("chat_user")->getMessageByUser($userId, $id, $pageNo, $pageSize);
                // 查询所有用户的聊天记录
            } else {
                $friendIds = model("friends")->getFriendIds($userId);
                // 查询所有的好友聊天记录
                foreach($friendIds as $fid) {
                    $ms = model("chat_user")->getMessageByUser($userId, $fid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
            }
            // 查询群聊消息
        } else {
            if ($id != null) {
                $result = model("chat_group")->getMessageByUser($userId, $id, $pageNo, $pageSize);
                im_log("debug", $result);
            } else {
                $groupIds = model("groups")->getGroupIds($userId);
                foreach($groupIds as $gid) {
                    $ms = model("chat_group")->getMessageByUser($userId, $gid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
            }
        }
        return $result;
    }
    
    public function getUnreadMessage($userId, $pageNo = 1, $pageSize = 50, $chatType = null, $id = null)
    {
        foreach([$userId, $pageNo, $pageSize] as $item) {
            if (!is_numeric($item)) {
                throw new OperationFailureException("参数类型错误");
            }
        }
        $result = [];
        // 查询所有用户和好友的聊天记录
        if ($chatType != IChatService::CHAT_FRIEND
            && $chatType != IChatService::CHAT_GROUP) {
                $friendIds = model("friends")->getFriendIds($userId);
                // 查询所有的好友聊天记录
                foreach($friendIds as $fid) {
                    $ms = model("chat_user")->getUnreadMessageByUser($userId, $fid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
                // 所有群聊的 id
                $groupIds = model("groups")->getGroupIds($userId);
                foreach($groupIds as $gid) {
                    $ms = model("chat_group")->getUnreadMessageByUser($userId, $gid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
                // 查询好友消息
            } else if ($chatType != IChatService::CHAT_GROUP ) {
                // 查询单个用户的聊天记录
                if ($id != null) {
                    $result = model("chat_user")->getUnreadMessageByUser($userId, $id, $pageNo, $pageSize);
                    // 查询所有用户的聊天记录
                } else {
                    $friendIds = model("friends")->getFriendIds($userId);
                    // 查询所有的好友聊天记录
                    foreach($friendIds as $fid) {
                        $ms = model("chat_user")->getUnreadMessageByUser($userId, $fid, $pageNo, $pageSize);
                        $result = array_merge($result, $ms);
                    }
                }
                // 查询群聊消息
            } else {
                if ($id != null) {
                    $result = model("chat_group")->getUnreadMessageByUser($userId, $id, $pageNo, $pageSize);
                    im_log("debug", $result);
                } else {
                    $groupIds = model("groups")->getGroupIds($userId);
                    foreach($groupIds as $gid) {
                        $ms = model("chat_group")->getUnreadMessageByUser($userId, $gid, $pageNo, $pageSize);
                        $result = array_merge($result, $ms);
                    }
                }
            }
            return $result;
    }
    
    /*
    public function getUnreadMessage($userId, $contactId, $type, $pageNo=1, $pageSize=100): \think\Collection
    {
        $resultSet = null;
        // $query = ModelFactory::getQuery();
        // 查询好友间未读信息
        if ($type === self::CHAT_FRIEND) {
            // 获取最后一条接收到的消息
            //             dump([$userId, $contactId]);
            // $lastMsgId = model("friends")->getLastRead($userId, $contactId);
            // 获取未读信息
            /*
            $resultSet = $query->table("im_chat_user")
                ->alias("chat")
                ->field("user.id, avatar, user_nickname AS username, send_date AS date, content, chat.id AS cid, UNIX_TIMESTAMP(chat.send_date) AS send_date")
                ->join(["cmf_user"=>"user"], "chat.sender_id=user.id", "LEFT OUTER")
                ->where("chat.id > :unReadId AND (chat.sender_id=:sender AND chat.receiver_id=:receiver AND chat.visible_sender=1 OR chat.sender_id=:receiver2 AND chat.receiver_id=:sender2 AND chat.visible_receiver=1)")
                ->bind([
                    "unReadId"=>[$lastMsgId, \PDO::PARAM_INT],
                    "sender"=>[$userId, \PDO::PARAM_INT],
                    "receiver"=>[$contactId, \PDO::PARAM_INT],
                    "sender2"=>[$userId, \PDO::PARAM_INT],
                    "receiver2"=>[$contactId, \PDO::PARAM_INT],
                ])
                ->order("chat.id", "DESC")
                ->limit($pageSize*$pageNo, $pageSize)
                ->select();
            
            //             dump($resultSet->toArray());
            $resultSet = model("chat_user")->getUnreadMessageByUser($userId, $contactId, $pageNo, $pageSize);
        }
        // 查询与群组的未读记录
        if ($type === self::CHAT_GROUP) {
            /*
            // 获取最后一条接收到的消息
            $lastMsgId = model("groups")->getLastRead($userId, $contactId);
            // 获取未读信息
            $resultSet = $query->table("im_chat_group")
                ->alias("chat")
                ->field("user.id, user.avatar, user_nickname AS username, content, chat.id AS cid, chat.group_id, chat.send_date AS send_date")
                ->join(["cmf_user"=>"user"], "chat.sender_id=user.id", "LEFT OUTER")
                ->where("chat.id > :unReadId AND chat.group_id=:gid")
                ->bind([
                    "unReadId"=>[$lastMsgId, \PDO::PARAM_INT],
                    "gid"=>[$contactId, \PDO::PARAM_INT],
                ])
                ->order("chat.id", "DESC")
                ->limit($pageSize*$pageNo, $pageSize)
                ->select();
            
            $resultSet = model("chat_group")->getUnreadMessageByUser($userId, $contactId, $pageNo, $pageSize);
        }
        return $resultSet;
    }
    */
    
    public function hiddenMessage($userId, $cid, $type) {
        try {
            $result = null;
            switch($type) {
                case static::CHAT_FRIEND:
                    $result = model("chat_user")->setVisible($userId, $cid, 0);
                    break;
            }
            //             var_dump($result);
            im_log("debug", "隐藏信息. user: $userId, cid: $cid, result: ", $result);
        } catch(\Exception $e) {
            im_log("error", "隐藏聊天信息失败, ", $e);
            throw new OperationFailureException("删除失败，请稍后重试~");
        }
    }
    
    public function messageFeedback($userId, $sign)  {
        $cache = RedisModel::getRedis();
        $hashName = config("im.cache_chat_last_send_time_key");
        $data = $cache->rawCommand("HGET", $hashName, $sign);
        if (!$data) {
            im_log("error", "$hashName 中的数据只应由 MVC 框架删除.");
            return;
        }
        $cdata = json_decode($data, true);
        // 获取聊天记录信息
        $data = $cdata["rawdata"];
        if (!$data) {
            im_log("error", "缓存消息格式错误. ", $cdata);
            return;
        }
        switch ($data["payload"]["type"]) {
            case IGatewayService::MESSAGE_TYPE:
                $msgs = $data["payload"]["data"];
                // 根据 type 将消息分类
                $tidy = [];
                foreach($msgs as $msg) {
                    $chatType =  isset($msg["gid"])? "group": "friend";
                    if (!isset($tidy[$chatType])) {
                        $tidy[$chatType] = [];
                    }
                    array_push($tidy[$chatType], $msg);
                }
                // 执行已读操作
                $types = array_keys($tidy);
                foreach($types as $type) {
                    $cids = array_column($tidy[$type], "cid");
                    $type = $type!=="group"? IChatService::CHAT_FRIEND: IChatService::CHAT_GROUP;
                    im_log("debug", "标记已读消息, user: $userId, cids: ", $cids, ", type: $type");
                    $this->readMessage($userId, $cids, $type);
                }
                break;
            default:
                im_log("notice", "反馈了未实现处理方式的消息: ", $data);
                break;
        }
        
        // 清除缓存
        $cache->rawCommand("HDEL", $hashName, $sign);
    }
    
    public function readChatGroup($groupId, int $pageNo=0, int $pageSize=100)
    {
        if ($pageSize > 500)$pageSize = 100;
        try {
            //             $pageSize=1;
            return model("chat_group")->getQuery()
            ->alias("g")
            ->field("u.id AS id, u.user_nickname AS username, avatar, send_date AS date, content, g.id AS chat_id")
            ->join(["cmf_user"=> "u"], "g.sender_id = u.id")
            ->where("group_id=:gid")
            ->bind("gid", $groupId, \PDO::PARAM_INT)
            ->order("date", "desc")
            //                 ->paginate($pageSize);
            ->limit($pageNo*$pageSize, $pageSize)
            ->select();
        } catch(\Exception $e) {
            im_log("error", "读取群组聊天信息失败! $groupId", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function readChatUser($Id, $userId, int $pageNo, int $pageSize): \think\Collection
    {
        if ($pageSize > 500)$pageSize = 100;
        try {
            im_log("debug", "查找用户聊天信息 Id and $userId");
            //             $map=[];
            //             $mapx=[];
            //             $map['chat.sender_id'] = $Id;
            //             $map['chat.receiver_id'] = $userId;
            //             $map['chat.visible_sender']=1;
            //             $mapx['chat.sender_id'] = $userId;
            //             $mapx['chat.receiver_id'] = $Id;
            //             $mapx['chat.visible_receiver']=1;
            
            //             return Db::table('im_chat_user chat')
            //                 ->where($map)
            //                 ->whereOr($mapx)
            //                 ->join(['cmf_user'=>'user'],'chat.sender_id=user.id')
            //                 ->field('user.id, avatar, user_nickname AS username, send_date AS date, content')
            //                 ->order('date desc')
            //                 ->paginate($pageSize);
            //                 ->limit($pageNo*$pageSize, $pageSize)
            //                 ->select();
            
            return model("chat_user")->getQuery()
            ->alias("chat")
            ->field("user.id, avatar, user_nickname AS username, send_date AS date, content, chat.id AS chat_id")
            ->join(["cmf_user"=> "user"], "chat.sender_id=user.id ")
            ->where("chat.sender_id=:id AND chat.receiver_id=:id2 AND chat.visible_sender=1 OR chat.sender_id=:id3 AND chat.receiver_id=:id4 AND chat.visible_receiver=1")
            ->bind([
                "id"=>[ $Id, \PDO::PARAM_INT],
                "id2"=>[ $userId, \PDO::PARAM_INT],
                "id3"=>[ $userId, \PDO::PARAM_INT],
                "id4"=>[ $Id, \PDO::PARAM_INT],
            ])
            ->order("date", "desc")
            ->limit($pageNo*$pageSize, $pageSize)
            ->select();
        } catch(\Exception $e) {
            im_log("error", "读取聊天信息失败! $userId", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function readMessage($userId, $cids, $type) {
        im_log("debug", "readMessage $type start.");
        $cache = RedisModel::getRedis();
        $cacheName = config("im.cache_chat_read_message_key");
        
        // 更新群聊已读
        if (static::CHAT_FRIEND !== $type) {
            // 获取消息信息
            $chatMsg = [];
            $resultSet = model("chat_group")->getUnreadMessageByMsgId($userId, $cids);
            im_log("debig", $resultSet);
            im_log("debig", $cids);
            // 用户无法关联这些消息（消息对用户不可见）
            if (!count($resultSet)) {
                im_log("notice", "尝试标记不可读取或已读的消息. user: $userId, cids: ", array_diff($cids, $resultSet), ", type: $type");
                return;
            }
            // 通过群聊 id 分组
            foreach($resultSet as $item) {
                if (!isset($chatMsg[$item["group_id"]])) {
                    $chatMsg[$item["group_id"]] = [];
                }
                array_push($chatMsg[$item["group_id"]], $item);
            }
            // 逐个分组处理
            foreach($chatMsg as $key => $groupMsg) {
                $groupId = $key;
                $fieldName = "group-$userId-$groupId";
                // 获取在缓存中的已读消息记录
                $readIds = $cache->rawCommand("HGET", $cacheName, $fieldName);
                // 解析格式
                if (!$readIds) {
                    $readIds = [];
                } else {
                    $readIds = json_decode($readIds, true);
                }
                // 获取这一次标记的已读消息 id
                $markIds = array_column($groupMsg, "chat_id");
                im_log("debug", "这次请求标记群聊 $groupId 已读消息 ", $markIds, ", 已标记已读消息 ", $readIds, ", 用户: $userId");
                // 获取新标记的消息 id
                $newIds = array_diff($markIds, $readIds);
                // 没有新标记的 id
                if (!count($newIds)) {
                    return;
                }
                // 整合所有标记的 id, 并重新检查用户是否已经连续收到消息 (在某个消息记录区间没有丢失的消息, 比如消息 id 1~10 之间用户都有收到).
                $readIds = array_unique(array_merge($readIds, $newIds));
                sort($readIds);
                im_log("debug", "群聊 $groupId 已读消息 ", $readIds, ", 用户: $userId");
                // 获取最早一条未读消息
                $oldest = model("chat_group")->getOldestUnreadMessage($userId, $groupId);
                im_log("debug", "群聊 $groupId 最早的未读消息 ", $oldest, ", 用户: $userId");
                // 在最早的已读消息之前还有没读到的消息, 暂存缓存, 下次继续.
                if ($oldest && $oldest["chat_id"] != $readIds[0]) {
                    $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                    return;
                }
                // 查询数据库中的消息记录, 查看已读消息是否为连续区间.
                $idRange = model("chat_group")->getMessageByIdRange($userId, $groupId, $readIds[0], $readIds[count($readIds)-1]);
                // 获取到了数据库中一段连续的消息 id, 如果这段 id 与现在标记的已读消息 id 是相同的, 说明用户完整地接收了消息.
                $idRange = array_column($idRange, "chat_id");
                
                $readId = $unreadIds = null;
                $unreadIds = array_diff($idRange, $readIds);
                im_log("debug", "群聊 $groupId 所有消息 ", $idRange, ", 已读消息", $readIds, ", 用户: $userId");
                // 重新获取到了最早的未读消息 id.
                if (count($unreadIds)) {
                    $unreadId = array_reduce($unreadIds, function($min, $current) {
                        return $current < $min? $current: $min;
                    }, 0);
                        $readId = array_search($unreadId, $unreadIds);
                        // 最后的已读信息 id
                        $readId = $idRange[$readId-1];
                } else { // 如果用户获得了连续完整的消息记录, 就完全更新.
                    $readId = $idRange[count($idRange)-1];
                }
                // 清除连续范围已读的消息标记并更新用户最后已读
                $readIds = array_filter($readIds, function($item) use ($readId){
                    return $item > $readId;
                });
                im_log("debug", "群聊 $groupId 确认消息已读 ", $readId, ", 用户: $userId");
                $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                model("groups")->setLastRead($userId, $groupId, $readId);
            }
        } else  { // 更新好友已读
            // 获取消息信息
            $chatMsg = [];
            $resultSet = model("chat_user")->getUnreadMessageByMsgId($userId, $cids);
            // 用户无法关联这些消息（消息对用户不可见）
            if (!count($resultSet)) {
                im_log("debug", "尝试标记不可读取或已读的消息. user: $userId, cids: ", array_diff($cids, $resultSet), ", type: $type");
                return;
            }
            // 通过好友 id 分组
            foreach($resultSet as $item) {
                $friendId = $item["sender_id"] != $userId?
                $item["sender_id"]: $item["receiver_id"];
                
                if (!isset($chatMsg[$friendId])) {
                    $chatMsg[$friendId] = [];
                }
                array_push($chatMsg[$friendId], $item);
            }
            // 逐个好友处理
            foreach($chatMsg as $key => $friendMsg) {
                $friendId = $key;
                $fieldName = "friend-$userId-$friendId";
                // 获取在缓存中的已读消息记录
                $readIds = $cache->rawCommand("HGET", $cacheName, $fieldName);
                // 解析格式
                if (!$readIds) {
                    $readIds = [];
                } else {
                    $readIds = json_decode($readIds, true);
                }
                // 获取这一次标记的已读消息 id
                $markIds = array_column($friendMsg, "chat_id");
                im_log("debug", "这次请求标记聊天 $friendId 已读消息 ", $markIds, ", 已标记已读消息 ", $readIds, ", 用户: $userId");
                // 获取新标记的消息 id
                $newIds = array_diff($markIds, $readIds);
                // 没有新标记的 id
                if (!count($newIds)) {
                    return;
                }
                // 整合所有标记的 id, 并重新检查用户是否已经连续收到消息 (在某个消息记录区间没有丢失的消息, 比如消息 id 1~10 之间用户都有收到).
                $readIds = array_unique(array_merge($readIds, $newIds));
                sort($readIds);
                im_log("debug", "聊天 $friendId 已读消息 ", $readIds, ", 用户: $userId");
                // 获取最早一条未读消息
                $oldest = model("chat_user")->getOldestUnreadMessage($userId, $friendId);
                im_log("debug", "聊天 $friendId 最早的未读消息 ", $oldest, ", 用户: $userId");
                // 在最早的已读消息之前还有没读到的消息, 暂存缓存, 下次继续.
                if ($oldest["chat_id"] != $readIds[0]) {
                    $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                    return;
                }
                // 查询数据库中的消息记录, 查看已读消息是否为连续区间.
                $idRange = model("chat_user")->getMessageByIdRange($userId, $friendId, $readIds[0], $readIds[count($readIds)-1]);
                // 获取到了数据库中一段连续的消息 id, 如果这段 id 与现在标记的已读消息 id 是相同的, 说明用户完整地接收了消息.
                $idRange = array_column($idRange, "chat_id");
                
                $readId = $unreadIds = null;
                $unreadIds = array_diff($idRange, $readIds);
                im_log("debug", "聊天 $friendId 所有消息 ", $idRange, ", 已读消息", $readIds, ", 用户: $userId");
                // 重新获取到了最早的未读消息 id.
                if (count($unreadIds)) {
                    $unreadId = array_reduce($unreadIds, function($min, $current) {
                        return $current < $min? $current: $min;
                    }, 0);
                        $readId = array_search($unreadId, $unreadIds);
                        // 最后的已读信息 id
                        $readId = $idRange[$readId-1];
                } else { // 如果用户获得了连续完整的消息记录, 就完全更新.
                    $readId = $idRange[count($idRange)-1];
                }
                // 清除连续范围已读的消息标记并更新用户最后已读
                $readIds = array_filter($readIds, function($item) use ($readId){
                    return $item > $readId;
                });
                    im_log("debug", "聊天 $friendId 确认消息已读 ", $readId, ", 用户: $userId");
                    $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                    model("friends")->setLastRead($userId, $friendId, $readId);
            }
        }
        im_log("debug", "readMessage $type end.");
    }
    
//     public function requestCallWithFriend($userId, $userId2, $callType)
//     {
//         $userService = SingletonServiceFactory::getUserService();
//         // 判断是否在线
//         if (!$userService->isOnline($userId2)
//             || !$userService->isOnline($userId)) {
//                 // 发送通话失败的信息
//                 $this->sendToUser(0, $userId, implode([
//                     "json", [
//                         "type"=>"call",
//                         "data"=>[
//                             "result"=>"offline",
//                             "type"=>$callType,
//                             "time"=>0
//                         ]
//                     ]
//                 ]));
//                 $this->sendToUser(0, $userId2, implode([
//                     "json", [
//                         "type"=>"call",
//                         "data"=>[
//                             "result"=>"missed",
//                             "type"=>$callType,
//                             "time"=>0
//                         ]
//                     ]
//                 ]));
//                 return;
//             }
//         $users = model("user")
//             ->getUserById($userId, $userId2);
//         if ($users[0]["id"] !== $userId2) {
//             $user = $users[0];
//             $user2 = $users[1];
//         } else {
//             $user2 = $user[0];
//             $user = $user[1];
//         }
//         // 推送请求通话的消息
//         $gatewayService = SingletonServiceFactory::getGatewayService();
//         $gatewayService::sendToUser($userId2, [
//             "sign"=>implode("-", [$userId, $userId2, time()]),
//             "ctype"=>$callType,
//             "userid"=>$user["id"],
//             "username"=>$user["username"],
//             "useravatar"=>$user["avatar"],
//             "ruserid"=>$user2["id"],
//             "rusername"=>$user2["username"],
//             "ruseravatar"=>$user2["avatar"]
//         ], $gatewayService::COMMUNICATION_ASK_TYPE);
//     }

    public function requestCallWithFriend($userId, $userId2, $callType)
    {
        $redis = RedisModel::getRedis();
        $userOnline1 = $redis->rawCommand("HKEYS","im_call_calling_user_".$userId."_hash");
        if(!empty($userOnline1)){
            throw  new OperationFailureException("你有通话正在进行哦！");
        }
        $userService = SingletonServiceFactory::getUserService();
        if (!$userService->isOnline($userId2)||!$userService->isOnline($userId)) {
            //             echo $userId."----------".$userId2;
            //             exit();
            // 发送通话失败的信息
            //             $this->sendToUser($userId2, $userId,implode([
            //                 "json",json_encode([
            //                     "type"=>"call",
            //                     "data"=>[
            //                         "result"=>"offline",//对方不在线
            //                         "type"=>$callType,
            //                         "time"=>0
            //                     ]
            //                 ])
            //             ]));
            $this->sendToUser($userId, $userId2,implode([
                "json",json_encode([
                    "type"=>"call",
                    "data"=>[
                        "send_result" => "offline",
                        "receiver_result"=>"missed",//未接听
                        "type"=>$callType,
                        "time"=>0
                    ]
                ])
            ]));
            return false;
        }
        $userOnline2 = $redis->rawCommand("HKEYS","im_call_calling_user_".$userId2."_hash");
        if(!empty($userOnline2)){
            if(!in_array($userId,$userOnline1)){
                //                 $this->sendToUser($userId2,$userId,implode([
                //                     "json",json_encode([
                //                         "type"=>"call",
                //                         "data"=>[
                //                             "result"=>"missed",//对方正在通话中
                //                             "type"=>$callType,
                //                             "time"=>0
                //                         ]
                //                     ])
                //                 ]));
                $this->sendToUser($userId, $userId2,implode([
                    "json",json_encode([
                        "type"=>"call",
                        "data"=>[
                            "send_result" => "incall",//对方正在打电话
                            "receiver_result"=>"missed",//未接听
                            "type"=>$callType,
                            "time"=>0
                        ]
                    ])
                ]));
                return false;
            }
            throw  new OperationFailureException("你和对方正在进行通话！");
        }
        $users = model("user")->getUserById($userId, $userId2);
        if ($users[0]["id"] !== $userId2) {
            $user = $users[0];
            $user2 = $users[1];
        } else {
            $user2 = $user[0];
            $user = $user[1];
        }
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $gatewayService::sendToUser($userId2, [
            "sign"=> implode("-", [$userId, $userId2, time()]),
            "ctype"=>$callType,
            "userid"=>$user["id"],
            "username"=>$user["username"],
            "useravatar"=>$user["avatar"],
            "ruserid"=>$user2["id"],
            "rusername"=>$user2["username"],
            "ruseravatar"=>$user2["avatar"]
        ], $gatewayService::COMMUNICATION_ASK_TYPE);
        return true;
    }
    
    public function requestCallWithGroup($userId, $groupId, $callType){
        $redis = RedisModel::getRedis();
        $from = model("user")->getUserById($userId);
        $to = model("groups")->getGroupExist($groupId,$userId);
        if (count($from) == 0 || count($to) == 0) {
            im_log("error", "尝试使用不存在的 id 发送音频. from user $userId to group $groupId: . to: ", $to, ", from: ", $from);
            throw new OperationFailureException("用户或分组不存在.");
        }
        //         $userOnline = $redis->del("im_call_calling_user_".$userId."_hash");
        //         $groups = $redis->del("im_call_calling_gruop_".$groupId."_hash");
        //         exit();
        $userOnline = $redis->rawCommand("HKEYS","im_call_calling_user_".$userId."_hash");
        if(!empty($userOnline)){
            throw  new OperationFailureException("你有通话正在进行哦！");
        }
        $gatewayService = SingletonServiceFactory::getGatewayService();
        
        $groups = $redis->rawCommand("HKEYS","im_call_calling_gruop_".$groupId."_hash");
        if(!empty($groups)){//当前群正在群聊时
            if(!in_array($userId, $groups)){
                $redis->rawCommand("HSET","im_call_calling_gruop_".$groupId."_hash" ,$userId,time());
                foreach($groups as $key){
                    $redis->rawCommand("HSET","im_call_calling_user_".$userId."_hash" ,$key,json_encode([
                        "createTime" => time(),
                        "ctype" => $callType
                    ]));
                    $gatewayService->sendToUser($key,[
                        "type"=> "group",
                        "list"=> model("group")->getMemberList($groupId,true)->toArray(),
                    ],$gatewayService::COMMUNICATION_MEMBER_TYPE);
                }
                return true;
            }
            throw  new OperationFailureException("你有通话正在进行哦！");
        }
        $callListName = config("im.cache_calling_communicating_list_key");
        $redis->rawCommand("LPUSH",$callListName,implode([$userId,"-",time()]));
        $sign = implode("-", [$groupId, $userId, time()]);
        $redis->rawCommand("HSET","im_call_calling_gruop_".$groupId."_hash","group",json_encode([//保存群聊sign
            "sign" => $sign,
            "groupid" => $to["id"],
            "ctype"=>$callType,
        ]));
        $redis->rawCommand("HSET","im_call_calling_gruop_".$groupId."_hash",$userId,time());
        $redis->rawCommand("HSET","im_call_calling_user_".$userId."_hash","group",json_encode([//证明他已经在通话中了
            "sign" => $sign,
            "groupid" => $to["id"],
            "ctype"=>$callType,
        ]));
        $gatewayService->sendToGroup($groupId,[
            "sign"=>$sign,
            "ctype"=>$callType,
            "groupid"=>$to["id"],
            "groupname"=>$to["groupname"],
            "groupavatar"=>$to["avatar"],
            "userid"=>$from[0]["id"],
            "username"=>$from[0]["username"],
            "useravatar"=>$from[0]["avatar"]
        ],$gatewayService::COMMUNICATION_ASK_TYPE);
        
        $this->sendToGroup($userId, $groupId,implode([
            "json",json_encode([
                "type"=>"call",
                "data"=>[
                    "result"=>"jionGroup",//视频邀请
                    "type"=>$callType,
                    "time"=>0
                ]
            ])
        ]));
        return true;
    }
    
    public function requestCallReply($userId,$sign,$replay,$unread){
        //unread重新推送先不做
        $redis = RedisModel::getRedis();
        $hashName = config("im.cache_chat_last_send_time_key");
        $data = $redis->rawCommand("HGET",$hashName ,$sign);
        echo $sign;
        var_dump($data);
        exit();
        if(!$data){
            im_log("error", "$hashName 中的数据只应由 MVC 框架删除.");
            return false;
        }
        $cdata = json_encode($data,true);
        $user1 = $cdata["rawdata"]["payload"]["data"];
        $redis->rawCommand("HDEL",$hashName ,$sign);
        if(isset($user1["ruserid"])){
            return $this->requestCallUserReply($replay, $user1);
        }
        if(isset($user1["groupid"])){
            return $this->requestCallGroupReply($userId,$replay, $user1);
        }
    }
    
    public function requestCallUserExchange($userId,$sign,$description){//未写重发
        $redis = RedisModel::getRedis();
        $hashName = config("im.cache_chat_last_send_time_key");
        $data = $redis->rawCommand("HGET", $hashName, $sign);
        if (!$data) {
            im_log("error", "该通话已中断");
            return false;
        }
        $data = json_decode($data, true);
        $redis->rawCommand("HDEL", $hashName, $sign);
        $callHashName = config("im.cache_calling_communication_info_hash_key");
        $cdata = $redis->rawCommand("HGET",$callHashName ,$data["rawdata"]["payload"]["data"]["sign"]);
        $cdata = json_decode($cdata,true);
        $redis->rawCommand("HSET","im_call_calling_user_".$cdata["userid"]."_hash",$cdata["ruserid"],json_encode([
            "sign" => $cdata["sign"],
            "description" => $description,//请求中
            "createTime" => time(),
            "ctype" => $cdata["ctype"]
        ])
            );
        //		$redis->rawCommand("HSET","im_call_calling_user_".$cdata["ruserid"]."_hash",$cdata["userid"],json_encode([
        //                "sign" => $cdata["sign"],
        //                "description" => $description,
        //                "createTime" => time(),
        //                "ctype" => $cdata["ctype"]
        //            ])
        //        );
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $gatewayService::sendToUser($cdata["ruserid"],[
            "sign" => $cdata["sign"],
            "description" => $description
        ],$gatewayService::COMMUNICATION-COMMAND);
    }
    
    public function requestCallGroupExchange($userId, $GroupId, $usersData){
        $redis = RedisModel::getRedis();
        $ruser = $redis->rawCommand("HGET","im_call_calling_user_".$usersData["userid"]."_hash",$userId);
        $user = $redis->rawCommand("HGET","im_call_calling_user_".$userId."_hash",$usersData["userid"]);
        if(empty($user) && empty($ruser)){
            $callhashName = config("im.cache_calling_communication_info_hash_key");
            $sign = $redis->rawCommand("HGET","im_call_calling_gruop_".$GroupId."_hash","group");
            $sign = json_decode($sign,true);
            $call = $redis->rawCommand("HGET", $callhashName, $sign["sign"]);
            $group = $redis->rawCommand("HGET","im_call_calling_gruop_".$GroupId."_hash",$userId);
            if($group && $sign && $call ){
                $redis->rawCommand("HSET", $callhashName, $user[$usersData["userid"]]["sign"], $call);
                $redis->rawCommand("HSET","im_call_calling_user_".$userId."_hash" , $usersData["userid"], json_encode([
                    "sign" => "",
                    "description" => $usersData["description"],
                    "createTime" => time,
                    "ctype" => ""
                ]));
                
                $redis->rawCommand("HSET","im_call_calling_user_".$usersData["userid"]."_hash" ,$userId , json_encode([
                    "sign" => "",
                    "description" => $usersData["description"],
                    "createTime" => time,
                    "ctype" => ""
                ]));
                $users = model("user")->getUserById($userId, $usersData["userid"]);
                if ($users[0]["id"] == $userId) {
                    $user1 = $users[0];
                    $user2 = $users[1];
                } else {
                    $user2 = $user[0];
                    $user1 = $user[1];
                }
                $gatewayService = SingletonServiceFactory::getGatewayService();
                $gatewayService::sendToUser($user1["id"],[
                    "sign"=> "", // 通信的标识
                    "ctype"=> "", // 通信的类型
                    "userid"=> $user1["id"], // 请求者的 id
                    "username" => $user1["user_login"], // 请求者的名称
                    "useravatar" => $user1["avatar"], // 请求者的名称
                    "ruserid" => $user2["id"], // 接收者的 id,
                    "rusername" => $user2["user_login"],
                    "ruseravatar" => $user2["avatar"],
                ],$gatewayService::COMMUNICATION_EXCHANGE_TYPE);
                $gatewayService::sendToUser($call[$GroupId]["userid"],[
                    "sign" => "",
                    "description" => $call[$GroupId]["description"]
                ],$gatewayService::COMMUNICATION_COMMAND_TYPE);
                $gatewayService::sendToUser($userId,[
                    "sign" => "",
                    "description" => $call[$GroupId]["description"]
                ],$gatewayService::COMMUNICATION_COMMAND_TYPE);
                $gatewayService::sendToUser($user2["id"],[
                    "sign" => "", // 通信的标识
                    "ctype" => "", // 通信的类型
                    "userid" => $user2["id"], // 请求者的 id
                    "username" => $user2["user_login"], // 请求者的名称
                    "useravatar" => $user2["avatar"], // 请求者的名称
                    "ruserid" => $user1["id"], // 接收者的 id,
                    "rusername" => $user1["user_login"],
                    "ruseravatar" => $user1["avatar"],
                ],$gatewayService::COMMUNICATION_EXCHANGE_TYPE);
                
                return true;
            }
        }
        return false;
    }

    public function sendToGroup($fromId, $toId, $content, $ip=null)
    {
        $query = ModelFactory::getQuery();
//         $ownTransaction = false;
        try {
            $from = model("user")->getUserById($fromId);
            $to = model("group")->getGroupById($toId);
            // 数据检查
            if (count($from) == 0 || count($to) == 0) {
                im_log("error", "尝试使用不存在的 id 发送信息. from user $fromId to group $toId: $content. to: ", $to, ", from: ", $from);
                throw new OperationFailureException("用户或分组不存在.");
            }
            $from = $from[0];
            $to = $to[0];
            // 存入记录
            $timestamp = time();
            $chatData = [
                "group_id"=>$toId,
                "sender_id"=>$fromId,
                "send_date"=>$timestamp,
                "send_ip"=>$ip,
                "content"=>$content
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }
            
            // 事务开启
            $query->startTrans();
            
            // 插入聊天记录表
            $msgId = $query->table("im_chat_group")
                ->insert($chatData, false, true);
            im_log("debug", "消息 id ", $msgId);
            // 更新群组关系信息表
            $query->table("im_groups")
                ->where("user_id=:uid AND contact_id=:gid")
                ->bind([
                    "uid"=>[$fromId, \PDO::PARAM_INT],
                    "gid"=>[$toId, \PDO::PARAM_INT]
                ])
                ->update([
                    "last_active_time"=>$timestamp,
                    "last_send_time"=>$timestamp,
                    "last_reads"=>$msgId
                ]);
            
            $query->commit();
            
            
            $reData = [
                "cid"=>$msgId,
                "issystem"=>0,
                "isread"=>0,
                "content"=>$content,
                "date"=>$timestamp,
                "uid"=>$fromId,
                "username"=>$from["username"],
                "avatar"=>$from["avatar"],
                "gid"=> $toId,
                "gavatar"=>$to["avatar"],
                "groupname"=>$to["groupname"]
            ];
            // 推送到群组
            GatewayServiceImpl::msgToGroup($toId, [$reData]);
            return $reData;
        } catch(OperationFailureException $e) {
            $query->rollback();
            
            throw $e;
        } catch(\Exception $e) {
            $query->rollback();
            
            im_log("error", "消息发送失败. from user $fromId to group $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }

    public function sendToUser($fromId, $toId, $content, $ip=null)
    {
        $query = ModelFactory::getQuery();
        try {
            if ($fromId == $toId) {
                im_log("error", "尝试给自己发消息. user: $fromId");
                throw new OperationFailureException("请不要给自己发消息~");
            }
            $user = model("user")->getUserById($fromId, $toId);
            // 数据检查
            if (count($user) != 2) {
                im_log("error", "尝试使用不存在的 id 发送信息. from user $fromId to user $toId: $content. resultSet: ", $user);
                throw new OperationFailureException("用户或分组不存在.");
            }
            // 存入记录
            $timestamp = time();
            $chatData = [
                "receiver_id"=>$toId,
                "sender_id"=>$fromId,
                "send_date"=>$timestamp,
                "send_ip"=>$ip,
                "content"=>$content,
                "visible_sender"=>1,
                "visible_receiver"=>1
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }
            
            // 事务开启
            $query->startTrans();
            
            // 插入聊天记录表
            $msgId = $query->table("im_chat_user")
                ->insert($chatData, false, true);
            if (!$msgId) {
                $query->rollback();
                throw new OperationFailureException("聊天记录保存失败!");
            }
            // 更新群组关系信息表
            $query->table("im_friends")
                ->where("user_id=:fid AND contact_id=:tid")
                ->bind([
                    "fid"=>[$fromId, \PDO::PARAM_INT],
                    "tid"=>[$toId, \PDO::PARAM_INT]
                ])
                ->update([
                    "last_active_time"=>$timestamp,
                    "last_send_time"=>$timestamp,
                    "last_reads"=>$msgId
                ]);
                
            $query->commit();
            
            
            $from = $user[0];
            $to = $user[1];
            
            $reData = [
                "cid"=>$msgId,
                "issystem"=>0,
                "isread"=>0,
                "content"=>$content,
                "date"=>$timestamp,
                "uid"=>$fromId,
                "username"=>$from["username"],
                "avatar"=>$from["avatar"],
                "tid"=> $toId,
                "tavatar"=>$to["avatar"],
                "tusername"=>$to["username"]
            ];
            im_log("debug", "发送消息");
            // 推送到用户
//             if (Gateway::isUidOnline($toId)) {
                SingletonServiceFactory::getGatewayService()->msgToUid($toId, [$reData]);
                SingletonServiceFactory::getGatewayService()->msgToUid($fromId, [$reData]);
//             }
            return $reData;
        } catch(OperationFailureException $e) {
            $query->rollback();
            throw $e;
        } catch(\Exception $e) {
            $query->rollback();
            
            im_log("error", "消息发送失败. from user $fromId to user $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }
}