<?php
namespace app\im\service;

use GatewayClient\Gateway;
use app\im\exception\OperationFailureException;
use app\im\model\RedisModel;
use app\im\model\ModelFactory;

class ChatService implements IChatService
{

    public function getMessage($userId, $pageNo = 1, $pageSize = 50, $chatType = null, $id = null)
    {
        foreach ([
            $userId,
            $pageNo,
            $pageSize
        ] as $item) {
            if (! is_numeric($item)) {
                throw new OperationFailureException("参数类型错误");
            }
        }
        $result = [];
        // 查询所有用户和好友的聊天记录
        if ($chatType != IChatService::CHAT_FRIEND && $chatType != IChatService::CHAT_GROUP) {
            $friendIds = model("friends")->getFriendIds($userId);
            // 查询所有的好友聊天记录
            foreach ($friendIds as $fid) {
                $ms = model("chat_user")->getMessageByUser($userId, $fid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 所有群聊的 id
            $groupIds = model("groups")->getGroupIds($userId);
            foreach ($groupIds as $gid) {
                $ms = model("chat_group")->getMessageByUser($userId, $gid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 查询好友消息
        } else if ($chatType != IChatService::CHAT_GROUP) {
            // 查询单个用户的聊天记录
            if ($id != null) {
                $result = model("chat_user")->getMessageByUser($userId, $id, $pageNo, $pageSize);
                // 查询所有用户的聊天记录
            } else {
                $friendIds = model("friends")->getFriendIds($userId);
                // 查询所有的好友聊天记录
                foreach ($friendIds as $fid) {
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
                foreach ($groupIds as $gid) {
                    $ms = model("chat_group")->getMessageByUser($userId, $gid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
            }
        }
        return $result;
    }

    public function getUnreadMessage($userId, $pageNo = 1, $pageSize = 50, $chatType = null, $id = null)
    {
        foreach ([
            $userId,
            $pageNo,
            $pageSize
        ] as $item) {
            if (! is_numeric($item)) {
                throw new OperationFailureException("参数类型错误");
            }
        }
        $result = [];
        // 查询所有用户和好友的聊天记录
        if ($chatType != IChatService::CHAT_FRIEND && $chatType != IChatService::CHAT_GROUP) {
            $friendIds = model("friends")->getFriendIds($userId);
            // 查询所有的好友聊天记录
            foreach ($friendIds as $fid) {
                $ms = model("chat_user")->getUnreadMessageByUser($userId, $fid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 所有群聊的 id
            $groupIds = model("groups")->getGroupIds($userId);
            foreach ($groupIds as $gid) {
                $ms = model("chat_group")->getUnreadMessageByUser($userId, $gid, $pageNo, $pageSize);
                $result = array_merge($result, $ms);
            }
            // 查询好友消息
        } else if ($chatType != IChatService::CHAT_GROUP) {
            // 查询单个用户的聊天记录
            if ($id != null) {
                $result = model("chat_user")->getUnreadMessageByUser($userId, $id, $pageNo, $pageSize);
                // 查询所有用户的聊天记录
            } else {
                $friendIds = model("friends")->getFriendIds($userId);
                // 查询所有的好友聊天记录
                foreach ($friendIds as $fid) {
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
                foreach ($groupIds as $gid) {
                    $ms = model("chat_group")->getUnreadMessageByUser($userId, $gid, $pageNo, $pageSize);
                    $result = array_merge($result, $ms);
                }
            }
        }
        return $result;
    }

    public function hiddenMessage($userId, $cid, $type)
    {
        try {
            $result = null;
            switch ($type) {
                case static::CHAT_FRIEND:
                    $result = model("chat_user")->setVisible($userId, $cid, 0);
                    break;
            }
            // var_dump($result);
            im_log("debug", "隐藏信息. user: $userId, cid: $cid, result: ", $result);
        } catch (\Exception $e) {
            im_log("error", "隐藏聊天信息失败, ", $e);
            throw new OperationFailureException("删除失败，请稍后重试~");
        }
    }

    public function messageFeedback($userId, $sign)
    {
        $cache = RedisModel::getRedis();
        $hashName = config("im.cache_chat_last_send_time_key");
        $data = $cache->rawCommand("HGET", $hashName, $sign);
        if (! $data) {
            im_log("error", "$hashName 中的数据只应由 MVC 框架删除.");
            return;
        }
        $cdata = json_decode($data, true);
        // 获取聊天记录信息
        $data = $cdata["rawdata"];
        if (! $data) {
            im_log("error", "缓存消息格式错误. ", $cdata);
            return;
        }
        switch ($data["payload"]["type"]) {
            case IGatewayService::MESSAGE_TYPE:
                $msgs = $data["payload"]["data"];
                // 根据 type 将消息分类
                $tidy = [];
                foreach ($msgs as $msg) {
                    $chatType = isset($msg["gid"]) ? "group" : "friend";
                    if (! isset($tidy[$chatType])) {
                        $tidy[$chatType] = [];
                    }
                    array_push($tidy[$chatType], $msg);
                }
                // 执行已读操作
                $types = array_keys($tidy);
                foreach ($types as $type) {
                    $cids = array_column($tidy[$type], "cid");
                    $type = $type !== "group" ? IChatService::CHAT_FRIEND : IChatService::CHAT_GROUP;
                    im_log("debug", "标记已读消息, user: $userId, cids: ", $cids, ", type: $type");
                    $this->readMessage($userId, $cids, $type);
                }
                break;
            case IGatewayService::COMMUNICATION_ASK_TYPE:
                $cache->rawCommand("HSET", config("im.im_chat_calling_communication_hash_key"), $data['payload']['data']['sign'], json_encode($data['payload']['data']));
                break;
            default:
                im_log("notice", "反馈了未实现处理方式的消息: ", $data);
                break;
        }

        // 清除缓存
        $cache->rawCommand("HDEL", $hashName, $sign);
    }

    public function readChatGroup($groupId, int $pageNo = 0, int $pageSize = 100)
    {
        if ($pageSize > 500)
            $pageSize = 100;
        try {
            // $pageSize=1;
            return model("chat_group")->getQuery()
                ->alias("g")
                ->field("u.id AS id, u.user_nickname AS username, avatar, send_date AS date, content, g.id AS chat_id")
                ->join([
                "cmf_user" => "u"
            ], "g.sender_id = u.id")
                ->where("group_id=:gid")
                ->bind("gid", $groupId, \PDO::PARAM_INT)
                ->order("date", "desc")
                ->
            // ->paginate($pageSize);
            limit($pageNo * $pageSize, $pageSize)
                ->select();
        } catch (\Exception $e) {
            im_log("error", "读取群组聊天信息失败! $groupId", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }

    public function readChatUser($Id, $userId, int $pageNo, int $pageSize): \think\Collection
    {
        if ($pageSize > 500)
            $pageSize = 100;
        try {
            im_log("debug", "查找用户聊天信息 Id and $userId");
            // $map=[];
            // $mapx=[];
            // $map['chat.sender_id'] = $Id;
            // $map['chat.receiver_id'] = $userId;
            // $map['chat.visible_sender']=1;
            // $mapx['chat.sender_id'] = $userId;
            // $mapx['chat.receiver_id'] = $Id;
            // $mapx['chat.visible_receiver']=1;

            // return Db::table('im_chat_user chat')
            // ->where($map)
            // ->whereOr($mapx)
            // ->join(['cmf_user'=>'user'],'chat.sender_id=user.id')
            // ->field('user.id, avatar, user_nickname AS username, send_date AS date, content')
            // ->order('date desc')
            // ->paginate($pageSize);
            // ->limit($pageNo*$pageSize, $pageSize)
            // ->select();

            return model("chat_user")->getQuery()
                ->alias("chat")
                ->field("user.id, avatar, user_nickname AS username, send_date AS date, content, chat.id AS chat_id")
                ->join([
                "cmf_user" => "user"
            ], "chat.sender_id=user.id ")
                ->where("chat.sender_id=:id AND chat.receiver_id=:id2 AND chat.visible_sender=1 OR chat.sender_id=:id3 AND chat.receiver_id=:id4 AND chat.visible_receiver=1")
                ->bind([
                "id" => [
                    $Id,
                    \PDO::PARAM_INT
                ],
                "id2" => [
                    $userId,
                    \PDO::PARAM_INT
                ],
                "id3" => [
                    $userId,
                    \PDO::PARAM_INT
                ],
                "id4" => [
                    $Id,
                    \PDO::PARAM_INT
                ]
            ])
                ->order("date", "desc")
                ->limit($pageNo * $pageSize, $pageSize)
                ->select();
        } catch (\Exception $e) {
            im_log("error", "读取聊天信息失败! $userId", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }

    public function readMessage($userId, $cids, $type)
    {
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
            if (! count($resultSet)) {
                im_log("notice", "尝试标记不可读取或已读的消息. user: $userId, cids: ", array_diff($cids, $resultSet), ", type: $type");
                return;
            }
            // 通过群聊 id 分组
            foreach ($resultSet as $item) {
                if (! isset($chatMsg[$item["group_id"]])) {
                    $chatMsg[$item["group_id"]] = [];
                }
                array_push($chatMsg[$item["group_id"]], $item);
            }
            // 逐个分组处理
            foreach ($chatMsg as $key => $groupMsg) {
                $groupId = $key;
                $fieldName = "group-$userId-$groupId";
                // 获取在缓存中的已读消息记录
                $readIds = $cache->rawCommand("HGET", $cacheName, $fieldName);
                // 解析格式
                if (! $readIds) {
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
                if (! count($newIds)) {
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
                $idRange = model("chat_group")->getMessageByIdRange($userId, $groupId, $readIds[0], $readIds[count($readIds) - 1]);
                // 获取到了数据库中一段连续的消息 id, 如果这段 id 与现在标记的已读消息 id 是相同的, 说明用户完整地接收了消息.
                $idRange = array_column($idRange, "chat_id");

                $readId = $unreadIds = null;
                $unreadIds = array_diff($idRange, $readIds);
                im_log("debug", "群聊 $groupId 所有消息 ", $idRange, ", 已读消息", $readIds, ", 用户: $userId");
                // 重新获取到了最早的未读消息 id.
                if (count($unreadIds)) {
                    $unreadId = array_reduce($unreadIds, function ($min, $current) {
                        return $current < $min ? $current : $min;
                    }, 0);
                    $readId = array_search($unreadId, $unreadIds);
                    // 最后的已读信息 id
                    $readId = $idRange[$readId - 1];
                } else { // 如果用户获得了连续完整的消息记录, 就完全更新.
                    $readId = $idRange[count($idRange) - 1];
                }
                // 清除连续范围已读的消息标记并更新用户最后已读
                $readIds = array_filter($readIds, function ($item) use ($readId) {
                    return $item > $readId;
                });
                im_log("debug", "群聊 $groupId 确认消息已读 ", $readId, ", 用户: $userId");
                $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                model("groups")->setLastRead($userId, $groupId, $readId);
            }
        } else { // 更新好友已读
                 // 获取消息信息
            $chatMsg = [];
            $resultSet = model("chat_user")->getUnreadMessageByMsgId($userId, $cids);
            // 用户无法关联这些消息（消息对用户不可见）
            if (! count($resultSet)) {
                im_log("debug", "尝试标记不可读取或已读的消息. user: $userId, cids: ", array_diff($cids, $resultSet), ", type: $type");
                return;
            }
            // 通过好友 id 分组
            foreach ($resultSet as $item) {
                $friendId = $item["sender_id"] != $userId ? $item["sender_id"] : $item["receiver_id"];

                if (! isset($chatMsg[$friendId])) {
                    $chatMsg[$friendId] = [];
                }
                array_push($chatMsg[$friendId], $item);
            }
            // 逐个好友处理
            foreach ($chatMsg as $key => $friendMsg) {
                $friendId = $key;
                $fieldName = "friend-$userId-$friendId";
                // 获取在缓存中的已读消息记录
                $readIds = $cache->rawCommand("HGET", $cacheName, $fieldName);
                // 解析格式
                if (! $readIds) {
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
                if (! count($newIds)) {
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
                $idRange = model("chat_user")->getMessageByIdRange($userId, $friendId, $readIds[0], $readIds[count($readIds) - 1]);
                // 获取到了数据库中一段连续的消息 id, 如果这段 id 与现在标记的已读消息 id 是相同的, 说明用户完整地接收了消息.
                $idRange = array_column($idRange, "chat_id");

                $readId = $unreadIds = null;
                $unreadIds = array_diff($idRange, $readIds);
                im_log("debug", "聊天 $friendId 所有消息 ", $idRange, ", 已读消息", $readIds, ", 用户: $userId");
                // 重新获取到了最早的未读消息 id.
                if (count($unreadIds)) {
                    $unreadId = array_reduce($unreadIds, function ($min, $current) {
                        return $current < $min ? $current : $min;
                    }, 0);
                    $readId = array_search($unreadId, $unreadIds);
                    // 最后的已读信息 id
                    $readId = $idRange[$readId - 1];
                } else { // 如果用户获得了连续完整的消息记录, 就完全更新.
                    $readId = $idRange[count($idRange) - 1];
                }
                // 清除连续范围已读的消息标记并更新用户最后已读
                $readIds = array_filter($readIds, function ($item) use ($readId) {
                    return $item > $readId;
                });
                im_log("debug", "聊天 $friendId 确认消息已读 ", $readId, ", 用户: $userId");
                $cache->rawCommand("HSET", $cacheName, $fieldName, json_encode($readIds));
                model("friends")->setLastRead($userId, $friendId, $readId);
            }
        }
        im_log("debug", "readMessage $type end.");
    }

    public function sendToGroup($fromId, $toId, $content, $ip = null)
    {
        $query = ModelFactory::getQuery();
        // $ownTransaction = false;
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
                "group_id" => $toId,
                "sender_id" => $fromId,
                "send_date" => $timestamp,
                "send_ip" => $ip,
                "content" => $content
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }

            // 事务开启
            $query->startTrans();

            // 插入聊天记录表
            $msgId = $query->table("im_chat_group")->insert($chatData, false, true);
            im_log("debug", "消息 id ", $msgId);
            // 更新群组关系信息表
            $query->table("im_groups")
                ->where("user_id=:uid AND contact_id=:gid")
                ->bind([
                "uid" => [
                    $fromId,
                    \PDO::PARAM_INT
                ],
                "gid" => [
                    $toId,
                    \PDO::PARAM_INT
                ]
            ])
                ->update([
                "last_active_time" => $timestamp,
                "last_send_time" => $timestamp,
                "last_reads" => $msgId
            ]);

            $query->commit();

            $reData = [
                "cid" => $msgId,
                "issystem" => 0,
                "isread" => 0,
                "content" => $content,
                "date" => $timestamp,
                "uid" => $fromId,
                "username" => $from["username"],
                "avatar" => $from["avatar"],
                "gid" => $toId,
                "gavatar" => $to["avatar"],
                "groupname" => $to["groupname"]
            ];
            // 推送到群组
            GatewayServiceImpl::msgToGroup($toId, [
                $reData
            ]);
            return $reData;
        } catch (OperationFailureException $e) {
            $query->rollback();

            throw $e;
        } catch (\Exception $e) {
            $query->rollback();

            im_log("error", "消息发送失败. from user $fromId to group $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }

    public function sendToUser($fromId, $toId, $content, $ip = null)
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
                "receiver_id" => $toId,
                "sender_id" => $fromId,
                "send_date" => $timestamp,
                "send_ip" => $ip,
                "content" => $content,
                "visible_sender" => 1,
                "visible_receiver" => 1
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }

            // 事务开启
            $query->startTrans();

            // 插入聊天记录表
            $msgId = $query->table("im_chat_user")->insert($chatData, false, true);
            if (! $msgId) {
                $query->rollback();
                throw new OperationFailureException("聊天记录保存失败!");
            }
            // 更新群组关系信息表
            $query->table("im_friends")
                ->where("user_id=:fid AND contact_id=:tid")
                ->bind([
                "fid" => [
                    $fromId,
                    \PDO::PARAM_INT
                ],
                "tid" => [
                    $toId,
                    \PDO::PARAM_INT
                ]
            ])
                ->update([
                "last_active_time" => $timestamp,
                "last_send_time" => $timestamp,
                "last_reads" => $msgId
            ]);

            $query->commit();

            $from = $user[0];
            $to = $user[1];

            $reData = [
                "cid" => $msgId,
                "issystem" => 0,
                "isread" => 0,
                "content" => $content,
                "date" => $timestamp,
                "uid" => $fromId,
                "username" => $from["username"],
                "avatar" => $from["avatar"],
                "tid" => $toId,
                "tavatar" => $to["avatar"],
                "tusername" => $to["username"]
            ];
            // 推送到用户
            if (Gateway::isUidOnline($toId)) {
                GatewayServiceImpl::msgToUid($toId, [
                    $reData
                ]);
                GatewayServiceImpl::msgToUid($fromId, [
                    $reData
                ]);
            }
            return $reData;
        } catch (OperationFailureException $e) {
            $query->rollback();
            throw $e;
        } catch (\Exception $e) {
            $query->rollback();

            im_log("error", "消息发送失败. from user $fromId to user $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }
    
    public function requestCallWithFriend($userId, $userId2, $callType)
    {
        $sign = implode("-", [
            $userId,
            $userId2,
            time()
        ]);
        $bool = true;
        try {
            $model = ModelFactory::getChatFriendModel();
            $redis = RedisModel::getRedis();
            $userOnline1 = $redis->rawCommand("HKEYS", "im_call_calling_user_" . $userId . "_hash");
            if (! empty($userOnline1)) {
                throw new OperationFailureException("你有通话正在进行哦！");
            }
            $userService = SingletonServiceFactory::getUserService();
            if (! $userService->isOnline($userId2) || ! $userService->isOnline($userId)) {
                // echo $userId."----------".$userId2;
                // exit();
                // 发送通话失败的信息
                // $this->sendToUser($userId2, $userId,implode([
                // "json",json_encode([
                // "type"=>"call",
                // "data"=>[
                // "result"=>"offline",//对方不在线
                // "type"=>$callType,
                // "time"=>0
                // ]
                // ])
                // ]));
//                 $this->sendToUser($userId, $userId2, implode([
//                     "json",
//                     json_encode([
//                         "type" => "call",
//                         "data" => [
//                             "send_result" => "offline",
//                             "receiver_result" => "missed", // 未接听
//                             "type" => $callType,
//                             "time" => 0
//                         ]
//                     ])
//                 ]));
                $model->addInfo($userId2, $userId, "未接来电");
                $model->addInfo($userId, $userId2, "无人接听");
                throw new OperationFailureException("未接听");
            }
            $userOnline2 = $redis->rawCommand("HKEYS", "im_call_calling_user_" . $userId2 . "_hash");
            if (! empty($userOnline2)) {
                if (! in_array($userId, $userOnline1)) {
                    // $this->sendToUser($userId2,$userId,implode([
                    // "json",json_encode([
                    // "type"=>"call",
                    // "data"=>[
                    // "result"=>"missed",//对方正在通话中
                    // "type"=>$callType,
                    // "time"=>0
                    // ]
                    // ])
                    // ]));
//                     $this->sendToUser($userId, $userId2, implode([
//                         "json",
//                         json_encode([
//                             "type" => "call",
//                             "data" => [
//                                 "send_result" => "incall", // 对方正在打电话
//                                 "receiver_result" => "missed", // 未接听
//                                 "type" => $callType,
//                                 "time" => 0
//                             ]
//                         ])
//                     ]));
                    $model->addInfo($userId, $userId2, "对方正忙");
                    $model->addInfo($userId2, $userId, "未接来电");
                }
                throw new OperationFailureException("你和对方正在进行通话！");
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
            $gatewayService->sendToUser($userId2, [
                "sign" => $sign,
                "ctype" => $callType,
                "userid" => $user["id"],
                "username" => $user["username"],
                "useravatar" => $user["avatar"],
                "ruserid" => $user2["id"],
                "rusername" => $user2["username"],
                "ruseravatar" => $user2["avatar"]
            ], $gatewayService::COMMUNICATION_ASK_TYPE);
        } catch (\Exception $e) {
            $bool = false;
            $this->callOver($userId, $userId2, null, $sign);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallWithGroup($userId, $groupId, $callType)
    {
        $bool = true;
        $sign = null;
        try {
            $redis = RedisModel::getRedis();
            $gatewayService = SingletonServiceFactory::getGatewayService();
            $callingIdListName = config("im.im_calling_id_list_key");
            $callingIdTimeHashName = config("im.im_calling_idtime_hash_key");
            $callingCommunicationhashName = config("im.im_chat_calling_communication_hash_key");
            $to = model("groups")->getGroupExist($groupId, $userId);
            // 获取群聊所有在线成员
            $userInfo = model("user")->getUserById($userId);
            if (empty($userInfo) || empty($to)) {
                im_log("error", "尝试使用不存在的 id 发送音频. from user " . $userInfo[0]['id'] . " to group $groupId: . to: ", $to, ", from: ", $userInfo[0]);
                throw new OperationFailureException("用户或分组不存在.");
            }
            $userOnline = $redis->rawCommand("HKEYS", "im_call_calling_user_" . $userInfo[0]['id'] . "_hash");
            // $callingIdList = $redis->rawCommand("LRANGE", $callingIdListName, 0, -1);
            if (! empty($userOnline)) {
                $bool = false;
                throw new OperationFailureException("你有通话正在进行哦！");
            }

            $groups = $redis->rawCommand("HKEYS", "im_call_calling_gruop_" . $groupId . "_hash");
            if (empty($groups)) { // 当前群没有在群聊
                                  // 加入信息
                $sign = $groupId . "-" . time();
                //验证唯一性
                $idList = $redis->rawCommand("LRANGE", $callingIdListName, 0, -1);
                foreach ($idList as $value) {
                    if($value === $userInfo[0]['id']){
                        throw new OperationFailureException("你有通话正在进行哦！");
                    }
                }
                $redis->rawCommand("RPUSH", $callingIdListName, $userInfo[0]['id']);
                $redis->rawCommand("HSET", $callingIdTimeHashName, $userInfo[0]['id'], json_encode([
                    'timestamp' => time()
                ]));
                // 通话标识sign
                $redis->rawCommand("HSET", "im_call_calling_gruop_" . $groupId . "_hash", "g", json_encode([
                    'sign' => $sign
                ]));
                $redis->rawCommand("HSET", "im_call_calling_gruop_" . $groupId . "_hash", $userInfo[0]['id'], json_encode([
                    'joinTime' => time()
                ]));

                $data = [
                    "sign" => $sign,
                    "ctype" => $callType,
                    "userid" => $userInfo[0]['id'],
                    "username" => $userInfo[0]['username'],
                    "useravatar" => $userInfo[0]['avatar'],
                    "groupid" => $to["id"],
                    "groupname" => $to["groupname"],
                    "groupavatar" => $to["avatar"]
                ];

                // 向群聊推送通话邀请信息
                $gatewayService->sendToGroup($groupId, $data, $gatewayService::COMMUNICATION_ASK_TYPE);
                $redis->rawCommand("HSET", $callingCommunicationhashName, $sign, json_encode($data));
            } else { // 当前群正在群聊
                $sign = json_decode($redis->rawCommand("HGET", "im_call_calling_gruop_" . $groupId . "_hash", "g"), true)['sign'];
                $data = json_decode($redis->rawCommand("HGET", $callingCommunicationhashName, $sign), true);
                $this->requestCallGroupReply($userId, true, $data, $sign);
            }
        } catch (\Exception $e) {
            $bool = false;
            $this->callOver($userId, null, $groupId, $sign);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }
    
    public function requestCallReply($userId, $sign, $replay, $unread)
    {
        $hashName = config("im.im_chat_calling_communication_hash_key");
        $redis = RedisModel::getRedis();
        $data = json_decode($redis->rawCommand("HGET", $hashName, $sign), true);
        if (! $data) {
            im_log("error", "$hashName 中的数据只应由 MVC 框架删除.");
            return false;
        }
        if (isset($data["ruserid"])) {
            return $this->requestCallUserReply($userId, $replay, $data, $sign);
        } else if (isset($data["groupid"])) {
            return $this->requestCallGroupReply($userId, $replay, $data, $sign);
        }
    }

    public function requestCallUserReply($userId, $replay, $userdata, $sign)
    {
        $bool = true;
        $redis = RedisModel::getRedis();
        try {
            if (empty($replay)) { // 拒绝
                throw new OperationFailureException("已拒绝");
            }
            // unread重新推送先不做
            $userList = config("im.im_call_calling_communicating_list_key");
            // 数组
            $redis->rawCommand("LPUSH", $userList, $sign);
            $userIds = explode("-", $sign);
            $user = ModelFactory::getUserModel()->getUserById($userIds[0], $userIds[1]);
            if ($user[0]['id'] == $userId) {
                $user1 = $user[0];
                $user2 = $user[1];
            } else {
                $user1 = $user[1];
                $user2 = $user[0];
            }

            $data1 = json_encode([
                'sign' => $sign,
                'description' => 0,
                'ice' => 0,
                'createTime' => $userIds[2],
                'ctype' => $userdata['ctype']
            ]);
            $data2 = json_encode([
                'sign' => $sign,
                'description' => 0,
                'ice' => 0,
                'createTime' => $userIds[2],
                'ctype' => $userdata['ctype']
            ]);

            // 接收人
            $redis->rawCommand("HSET", "im_call_calling_user_" . $user1['id'] . "_hash", $user2['id'], $data2);
            // 发送人
            $redis->rawCommand("HSET", "im_call_calling_user_" . $user2['id'] . "_hash", $user1['id'], $data1);
            $gatewayService = SingletonServiceFactory::getGatewayService();

            // 推送
            $gatewayService->sendToUser($user1['id'], [
                'sign' => $sign,
                'ctype' => $userdata['ctype'],
                'userid' => $user1['id'],
                'username' => $user1['username'],
                'useravatar' => $user1['avatar'],
                'ruserid' => $user2['id'],
                'rusername' => $user2['username'],
                'ruseravatar' => $user2['avatar']
            ], $gatewayService::COMMUNICATION_EXCHANGE_TYPE);

            $gatewayService->sendToUser($user2['id'], [
                'sign' => $sign,
                'ctype' => $userdata['ctype'],
                'userid' => $user2['id'],
                'username' => $user2['username'],
                'useravatar' => $user2['avatar'],
                'ruserid' => $user1['id'],
                'rusername' => $user1['username'],
                'ruseravatar' => $user1['avatar']
            ], $gatewayService::COMMUNICATION_EXCHANGE_TYPE);
        } catch (\Exception $e) {
            im_log("error", $e->getMessage());
            $bool = false;
            $this->callOver($userdata['userid'], $userdata['ruserid'], null, $sign);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallGroupReply($userId, $replay, $userdata, $sign)
    {
        $bool = true;
        $redis = RedisModel::getRedis();
        $user1 = model("user")->getUserById($userId)[0];
        $callhashName = config("im.im_chat_calling_communication_hash_key");
        $callingIdListName = config("im.im_calling_id_list_key");
        $callingIdTimeHashName = config("im.im_calling_idtime_hash_key");
        $gatewayService = SingletonServiceFactory::getGatewayService();
        try {
            if (! $replay) {
                throw new OperationFailureException("已拒绝");
            }
            if ($userId === $userdata['userid']) {
                throw new OperationFailureException("您不能和自己通话");
            }
            $redis->rawCommand("RPUSH", $callingIdListName, $userId);
            $redis->rawCommand("HSET", $callingIdTimeHashName, $userId, json_encode([
                'timestamp' => time()
            ]));
            $redis->rawCommand("HSET", "im_call_calling_gruop_" . $userdata['groupid'] . "_hash", $userId, json_encode([
                'joinTime' => time()
            ]));
            $gatewayService->sendToGroup($userdata['groupid'], [
                "type" => $userdata['ctype'],
                "list" => model("group")->getMemberList($userdata["groupid"], true)
                    ->toArray()
            ], $gatewayService::COMMUNICATION_MEMBER_TYPE);

            $call = $redis->rawCommand("HGET", $callhashName, $sign);
            $groupKeys = $redis->rawCommand("HKEYS", "im_call_calling_gruop_" . $userdata['groupid'] . "_hash");
            if ($call) {
                for ($i = 0; $i < count($groupKeys); $i ++) {
                    if ($groupKeys[$i] !== "g") {
                        $user2 = model("user")->getUserById($groupKeys[$i])[0];
                        $ruser = $redis->rawCommand("HGET", "im_call_calling_user_" . $user1['id'] . "_hash", $user2['id']);
                        $user = $redis->rawCommand("HGET", "im_call_calling_user_" . $user2['id'] . "_hash", $user1['id']);
                        if (empty($user) && empty($ruser)) {
                            if($user1 !== $user2){
                                $data1 = [
                                    "sign" => $sign, // 通信的标识
                                    "ctype" => $userdata['ctype'], // 通信的类型
                                    "userid" => $user1['id'], // 请求者的 id
                                    "username" => $user1["username"], // 请求者的名称
                                    "useravatar" => $user1["avatar"], // 请求者的名称
                                    "groupid" => $userdata['groupid'], // 群聊的 id
                                    "groupname" => $userdata['groupname'], // 群聊的名称
                                    "groupavatar" => $userdata['groupavatar'], // 群聊的图像
                                    "ruserid" => $user2["id"], // 接收者的 id,
                                    "rusername" => $user2["username"],
                                    "ruseravatar" => $user2["avatar"]
                                ];
                                
                                $data2 = [
                                    "sign" => $sign, // 通信的标识
                                    "ctype" => $userdata['ctype'], // 通信的类型
                                    "userid" => $user2['id'], // 请求者的 id
                                    "username" => $user2["username"], // 请求者的名称
                                    "useravatar" => $user2["avatar"], // 请求者的名称
                                    "groupid" => $userdata['groupid'], // 群聊的 id
                                    "groupname" => $userdata['groupname'], // 群聊的名称
                                    "groupavatar" => $userdata['groupavatar'], // 群聊的图像
                                    "ruserid" => $user1["id"], // 接收者的 id,
                                    "rusername" => $user1["username"],
                                    "ruseravatar" => $user1["avatar"]
                                ];
                                
                                // 推送消息
                                $gatewayService->sendToUser($user2['id'], $data1, $gatewayService::COMMUNICATION_EXCHANGE_TYPE);
                                $gatewayService->sendToUser($user1['id'], $data2, $gatewayService::COMMUNICATION_EXCHANGE_TYPE);
                            }
                        } else {
                            throw new OperationFailureException("您当前正在通话中！");
                        }
                    }
                }
            } else {
                throw new OperationFailureException("群聊通话已结束！");
            }
        } catch (\Exception $e) {
            $bool = false;
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallUserExchange($userId, $sign, $description)
    { // 未写重发
        $bool = true;
        try {
            $redis = RedisModel::getRedis();
            $hashName = config("im.im_chat_calling_communication_hash_key");
            $callingIdList = config("im.im_calling_id_list_key");
            $callingIdTimeHash = config("im.im_calling_idtime_hash_key");
            // 移除所有等于value的元素
            $redis->rawCommand("LREM", $callingIdList, 0, $userId);
            $redis->rawCommand("RPUSH", $callingIdList, $userId);
            $redis->rawCommand("HSET", $callingIdTimeHash, $userId, json_encode([
                "timestamp" => time()
            ]));
            $data = json_decode($redis->rawCommand("HGET", $hashName, $sign), true);
            $str = explode("-", $sign);
            if ($str[0] == $userId) {
                $userId1 = $str[0];
                $userId2 = $str[1];
            } else {
                $userId1 = $str[1];
                $userId2 = $str[0];
            }
            $redis->rawCommand("HSET", "im_call_calling_user_" . $userId2 . "_hash", $userId1, json_encode(array(
                'sign' => $sign,
                'description' => $description,
                'ice' => 0,
                'createTime' => $str[2],
                'ctype' => $data['ctype']
            )));
            $res = json_decode($redis->rawCommand("HGET", "im_call_calling_user_" . $userId1 . "_hash", $userId2), true);
            if (! $res) {
                im_log("error", "该通话已中断");
                $bool = false;
                $this->callOver($userId1, $userId2, null, $sign);
                throw new OperationFailureException("该通话已中断");
            }

            $gatewayService = SingletonServiceFactory::getGatewayService();
            $gatewayService->sendToUser($userId2, [
                "sign" => $data["sign"],
                "description" => $description
            ], $gatewayService::COMMUNICATION_COMMAND_TYPE);
        } catch (\Exception $e) {
            im_log("error", $e->getMessage());
            $bool = false;
            throw $e;
        } catch (OperationFailureException $e) {
            $bool = false;
            throw new OperationFailureException($e);
        }
        return $bool;
    }
    
    public function requestCallGroupExchange($userId, $groupId, $call)
    {
        $bool = true;
        $redis = RedisModel::getRedis();
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $callingCommunicationhashName = config("im.im_chat_calling_communication_hash_key");
        $sign = null;
        try {
            $user1 = model("user")->getUserById($userId)[0];
            $sign = json_decode($redis->rawCommand("HGET", "im_call_calling_gruop_" . $groupId . "_hash", "g"), true);
            $data = json_decode($redis->rawCommand("HGET", $callingCommunicationhashName, $sign['sign']), true);
            
            for ($i = 0; $i < count($call); $i ++) {
                $user2 = model("user")->getUserById($call[$i]['userid'])[0];
                
                if ($user1['id'] !== $user2['id']) {
                    
                    $reTime = time();
                    $redis->rawCommand("HSET", "im_call_calling_user_" . $user1['id'] . "_hash", $user2['id'], json_encode([
                        "sign" => $sign['sign'],
                        "description" => $call[$i]['description'],
                        "createTime" => $reTime,
                        "ctype" => $data['ctype']
                    ]));
                    
                    $data1 = [
                        'sign' => $sign['sign'],
                        'description' => $call[$i]['description'],
                        "ctype" => $data['ctype'], // 通信的类型
                        "userid" => $user1['id'], // 请求者的 id
                        "username" => $user1["username"], // 请求者的名称
                        "useravatar" => $user1["avatar"], // 请求者的名称
                        "groupid" => $data['groupid'], // 群聊的 id
                        "groupname" => $data['groupname'], // 群聊的名称
                        "groupavatar" => $data['groupavatar'], // 群聊的图像
                        "ruserid" => $user2["id"], // 接收者的 id,
                        "rusername" => $user2["username"],
                        "ruseravatar" => $user2["avatar"]
                    ];
                    
                    $gatewayService->sendToUser($user2['id'], $data1, $gatewayService::COMMUNICATION_COMMAND_TYPE);
                }
            }
        } catch (\Exception $e) {
            $bool = false;
            $this->callOver($userId, null, $groupId, $sign['sign']);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallUserExchangeIce($userId, $sign, $ice)
    {
        $bool = true;
        try {
            $redis = RedisModel::getRedis();
            $str = explode("-", $sign);
            if ($str[0] == $userId) {
                $userId1 = $str[0];
                $userId2 = $str[1];
            } else {
                $userId1 = $str[1];
                $userId2 = $str[0];
            }
            $data = json_decode($redis->rawCommand("HGET", "im_call_calling_user_" . $userId2 . "_hash", $userId1), true);
            if (empty($data)) {
                $bool = false;
                $this->callOver($userId1, $userId2, null, $sign);
                throw new OperationFailureException("该通话已中断");
            }
            $redis->rawCommand("HSET", "im_call_calling_user_" . $userId2 . "_hash", $userId1, json_encode(array(
                'sign' => $data['sign'],
                'description' => $data['description'],
                'ice' => $ice,
                'createTime' => $data['createTime'],
                'ctype' => $data['ctype']
            )));

            $gatewayService = SingletonServiceFactory::getGatewayService();
            $gatewayService->sendToUser($userId2, [
                "sign" => $data["sign"],
                "ice" => $ice
            ], $gatewayService::COMMUNICATION_ICE_TYPE);
        } catch (\Exception $e) {
            im_log("error", $e->getMessage());
            $bool = false;
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallGroupExchangeIce($userId, $groupId, $call)
    {
        $sign = null;
        $bool = true;
        $redis = RedisModel::getRedis();
        $gatewayService = SingletonServiceFactory::getGatewayService();
        $callingCommunicationhashName = config("im.im_chat_calling_communication_hash_key");
        try {
            $user1 = model("user")->getUserById($userId)[0];
            $sign = json_decode($redis->rawCommand("HGET", "im_call_calling_gruop_" . $groupId . "_hash", "g"), true);
            $data = json_decode($redis->rawCommand("HGET", $callingCommunicationhashName, $sign['sign']), true);
            
            for ($i = 0; $i < count($call); $i ++) {
                im_log("error", $call);
                $user2 = model("user")->getUserById($call[$i]['userid'])[0];
                
                if ($user1['id'] !== $user2['id']) {
                    
                    $data1 = [
                        'sign' => $sign['sign'],
                        'ice' => $call[$i]['ice'],
                        "ctype" => $data['ctype'], // 通信的类型
                        "userid" => $user1['id'], // 请求者的 id
                        "username" => $user1["username"], // 请求者的名称
                        "useravatar" => $user1["avatar"], // 请求者的名称
                        "groupid" => $data['groupid'], // 群聊的 id
                        "groupname" => $data['groupname'], // 群聊的名称
                        "groupavatar" => $data['groupavatar'], // 群聊的图像
                        "ruserid" => $user2["id"], // 接收者的 id,
                        "rusername" => $user2["username"],
                        "ruseravatar" => $user2["avatar"]
                    ];
                    
                    $gatewayService->sendToUser($user2['id'], $data1, $gatewayService::COMMUNICATION_ICE_TYPE);
                }
            }
        } catch (\Exception $e) {
            $bool = false;
            $this->callOver($userId, null, $groupId, $sign['sign']);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallComplete($sign, $success)
    {
        $bool = true;
        try {
            $redis = RedisModel::getRedis();
            $hashName = config("im.im_chat_calling_communication_hash_key");
            $data = json_decode($redis->rawCommand("HGET", $hashName, $sign), true);
            if ($success) { // 当连接成功时
                im_log("error", "连接成功");
                return $bool;
            }
            if (empty($data)) {
                im_log("error", "该通话已中断");
                throw new OperationFailureException("该通话已中断");
            }
            $redis->rawCommand("HDEL", $hashName, $data["sign"]);
            if (isset($data["userid"])) {
                return $this->requestCallUserComplete($data);
            }
            if (isset($data["groupid"])) {
                return $this->requestCallGroupComplete($this->userId, $sign, $data);
            }
        } catch (\Exception $e) {
            im_log("error", $e->getMessage());
            $bool = false;
            $this->callOver($data['userid'], $data['ruserid'], null, $sign);
            throw $e;
        } catch (OperationFailureException $e) {
            throw new OperationFailureException($e);
        }
        return $bool;
    }

    public function requestCallUserComplete($userdata)
    {
        $redis = RedisModel::getRedis();
        $redis->del("im_call_calling_user_" . $userdata["userid"] . "_hash");
        $redis->del("im_call_calling_user_" . $userdata["ruserid"] . "_hash");
        $callListName = config("im.im_call_calling_communicating_list_key");
        $list = $redis->rawCommand("LRANGE", $callListName, 0, - 1);
        foreach ($list as $key) {
            if (strpos($key, $userdata["userid"] . "-") !== false || strpos($key, $userdata["ruserid"] . "-") !== false) {
                $redis->rawCommand("LREM", $callListName, 0, $key);
            }
        }
        $this->sendToUser($userdata["ruserid"], $userdata["userid"], implode([
            "json",
            json_encode([
                "type" => "call",
                "data" => [
                    "result" => "over", // 视频已结束
                    "type" => $userdata["ctype"],
                    "time" => 0
                ]
            ])
        ]));
        $this->sendToUser($userdata["userid"], $userdata["ruserid"], implode([
            "json",
            json_encode([
                "type" => "call",
                "data" => [
                    "result" => "over", // 视频已结束
                    "type" => $userdata["ctype"],
                    "time" => 0
                ]
            ])
        ]));
        return true;
    }
    
    public function requestCallGroupComplete($userId, $sign, $userdata)
    {
        $this->callOver($userId, null, $userdata['groupid'], $sign);
    }

    public function requestFinish($userId, $sign)
    {
        $redis = RedisModel::getRedis();
        $callingInfoHashName = config("im.im_chat_calling_communication_hash_key");
        $data = json_decode($redis->rawCommand("HGET", $callingInfoHashName, $sign), true);
        if(empty($data)){
            return true;
        }
        if (! empty($data['groupid'])) {
            return $this->callOver($userId, null, $data['groupid'], $sign);
        } else {
            return $this->callOver($data['userid'], $data['ruserid'], null, $sign);
        }
    }

    public function callOver($client_id1 = null, $client_id2 = null, $groupId = null, $sign = null)
    {
        $redis = RedisModel::getRedis();
        $callHashName = config("im.im_chat_calling_communication_hash_key");
        $callingIdtimeHashName = config("im.im_calling_idtime_hash_key");
        if (!empty($client_id1) && !empty($client_id2)) {
            $redis->del("im_call_calling_user_" . $client_id1 . "_hash");
            $redis->del("im_call_calling_user_" . $client_id2 . "_hash");
            if (! empty($sign)) {
                $data = json_decode($redis->rawCommand("HGET", $callHashName, $sign), true);
                if (! empty($data)) {
                    $redis->rawcommand("HDEL", $callHashName, $sign);
                }
            }
        }
        
        if (!empty($groupId)) {
            $sign = json_decode($redis->rawCommand("HGET", "im_call_calling_gruop_" . $groupId . "_hash", "g"), true);
            $redis->rawCommand("HDEL", "im_call_calling_gruop_" . $groupId . "_hash", $client_id1);
            $redis->del("im_call_calling_user_" . $client_id1 . "_hash");
            $redis->del("im_call_calling_user_" . $client_id2 . "_hash");
            $keys = $redis->rawCommand("HKEYS", "im_call_calling_gruop_" . $groupId . "_hash");
            if (count($keys) <= 1) { // 群里没人在群聊
                im_log("error", $sign['sign']);
                $redis->rawCommand("HDEL", $callHashName, $sign['sign']);
                $redis->del("im_call_calling_gruop_" . $groupId . "_hash");
            } else { // 群内还有人在群聊
                $redis->del("im_call_calling_user_" . $client_id1 . "_hash");
                $redis->rawCommand("HDEL", $callingIdtimeHashName, $client_id1);
                $redis->rawCommand("HDEL", "im_call_calling_gruop_" . $groupId . "_hash", $client_id1);
            }
        }
        return true;
    }
}