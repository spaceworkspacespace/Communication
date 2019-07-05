<?php
namespace app\im\service;

use GatewayClient\Gateway;
use app\im\exception\OperationFailureException;
use app\im\model\RedisModel;
use app\im\model\ModelFactory;
use think\Cache;

class ChatService implements IChatService
{

    /**
     * 生成消息字符串
     * @param string $type 消息类型
     * @param array $data 消息内容
     */
    protected function generateMessageString($type, $data) {
        return json_encode([
            "type"=>$type,
            "data"=>$data
        ], JSON_OBJECT_AS_ARRAY);
    }
    
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
                $detail_h = RedisModel::getKeyName("detail_h");
                $callSign = $data['payload']['data']['sign'];
                $callDetail = $data['payload']['data'];
                if (!RedisModel::exists($detail_h)) {
                    RedisModel::hsetJson($detail_h, $callSign, $callDetail);
                }
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
    
    public function requestCallReconnection($sign, $userId, $userId2, $connectId=null) {
        $userKey = RedisModel::getKeyName("user_h", ["userId"=>$userId]);
        $userKey2 = RedisModel::getKeyName("user_h", ["userId"=>$userId2]);
        
        $detail = RedisModel::hget(RedisModel::getKeyName("detail_h"), $sign);
        
        if (!is_array($detail)) {
            throw new OperationFailureException(lang("the call doesn't exist"));
        }
        if (!RedisModel::exists($userKey) 
            || !RedisModel::exists($userKey2)) {
            throw new OperationFailureException(lang("invalid user"));        
        }
        
        // 获取用户信息
        $getUserInfo = function($users, $ruserid) {
            $ruserindex = $users[0]["id"] !== $ruserid? 1: 0;
            $users = array_map_with_index($users, function($value) {
                $user = $value;
                array_key_replace($user, ["id"=>"userid", "avatar"=>"useravatar"]);
                return array_index_pick($user, "userid", "useravatar", "username");
            });
            $users[$ruserindex] = array_map_keys($users[$ruserindex], function($value, $key) {
                return "r$key";
            });
            return array_flatten($users);
        };
        $userModel = ModelFactory::getUserModel();
        $users = $userModel->getUserById($userId, $userId2);
        $getUserInfo = function_curry($getUserInfo)($users);
        
        // 如果是新的重连需求, 生成新的 connectid, 并推送给双方通知客户端重置连接.
        $gateway = SingletonServiceFactory::getGatewayService();
        if (!is_string($connectId) 
            || strlen(trim($connectId)) === 0) {
                
            $connectId = time();
            $gateway->sendToUser($userId2, 
                array_merge(["connectid"=>$connectId], $detail, $getUserInfo($userId2)), 
                IGatewayService::COMMUNICATION_RECONNECT_TYPE);
            $gateway->sendToUser($userId, 
                array_merge(["connectid"=>$connectId], $detail, $getUserInfo($userId)), 
                IGatewayService::COMMUNICATION_RECONNECT_TYPE);
            return;
        }
        
        $cache = Cache::store("redis");
        try {
            if ($cache->lock($userKey) || $cache->lock($userKey2)) {
                throw new OperationFailureException(lang('server busy'));
            }
            
            // 如果双方的 connectId 一致, 就重新连接, 否则就先保存，之后使用
            $userCallInfo2 = RedisModel::hgetJson($userKey2, $userId);
            if (isset($userCallInfo2["connectid"]) 
                && (string)$userCallInfo2["connectid"] === "$connectId") {
                $callService = SingletonServiceFactory::getCallService();
                // RedisModel::hdel();
                $callService->establish($userId, $userId2, $sign);
            } else {
                $userCallInfo = RedisModel::hgetJson($userKey, $userId2);
                $userCallInfo['connectid'] = $connectId;
                RedisModel::hsetJson($userKey, $userId2, $userCallInfo);
            }
        } finally {
            $cache->unlock($userKey);
            $cache->unlock($userKey2);
        }
    }
    
    /**
     * 客户端之间交换 ice 和 desc 的步骤, 要做的事情一样, 就写一起了.
     * @param mixed $args { sign?: string, userId: number, desc?: string, ice?: string, call?: array }
     */
    public function requestCallExchange($args) {
        $callDetailField = RedisModel::getKeyName("cache_calling_communication_info_hash_key");
        $userId = $args["userId"];
        return array_select($args, [
            "call"=>function($value, $key, $array) use ($userId) {
                println("交换信息", $key, ", ", $value);
                if (is_null($value)) return null;
                
                array_for_each($value, function($value, $index) use ($userId) {
                    $callGroupField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$index]);
                    $g = RedisModel::hgetJson($callGroupField, "g");
                    if (!$g || !isset($g["sign"])) {
                        im_log("error", "群聊格式的交换信息, 但是未能发现群聊详细信息.", $callGroupField, ": ", RedisModel::hgetallJson($callGroupField));
                        return;
                    }
                    $sign = $g["sign"];
                    
                    array_for_each($value, function($value) use ($sign, $userId) {
                        $userId2 = $value["userid"];
                        $desc = isset($value["description"])? $value["description"]: null;
                        $ice = isset($value["ice"])? $value["ice"]: null;
                        
                        SingletonServiceFactory::getCallService()
                            ->establish($userId, $userId2, $sign, $desc, $ice);
                    });
                });
            },
            "sign"=> function($value, $key, $array) use ($callDetailField, $userId) {
                println("交换信息", $key, ", ", $value);
                if (is_null($value)) return null;
                
                $data = RedisModel::hgetJson($callDetailField, $value);
                $desc = isset($array["desc"])? $array["desc"]: null;
                $ice = isset($array["ice"])? $array["ice"]: null;
                $userId2 = $data["userid"] != $userId? $data["userid"]:$data["ruserid"];
                return SingletonServiceFactory::getCallService()
                    ->establish($userId, $userId2, $value, $desc, $ice);
            }
        ], false);
    }
    
    public function requestCallWithFriend($userId, $userId2, $callType)
    {
        try {
            $userService = SingletonServiceFactory::getUserService();
            $callService = SingletonServiceFactory::getCallService();
            
            // 判断用户是否在线
            // 当前呼叫者离线了.
            if ( ! $userService->isOnline($userId)) {
                im_log("notice", "用户在线状态可能错误 ! $userId 进行了通话, 但不在在线人员中.");
                throw new OperationFailureException(lang("service unavailable"));
            }
            // 判断是否在通话中
            if ($callService->isCalling($userId)) {
                throw new OperationFailureException("您有通话正在进行");
            }
            
            if (! $userService->isOnline($userId2) // 对方不在线
                || $callService->isCalling($userId2)) { // 对方已经在通话中
                throw new OperationFailureException(lang("the other user u receive"));
            }
            // 返回了聊天的 sign
            return $callService->pushCallRequest($userId, $userId2, $callType);
        } catch (OperationFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            im_log("error", "好友通话失败.", $e);
            throw new OperationFailureException();
        }
    }

    public function requestCallWithGroup($userId, $groupId, $callType)
    {
        try {
            $callService = SingletonServiceFactory::getCallService();
            $userService = SingletonServiceFactory::getUserService();
            $userModel = ModelFactory::getUserModel();
            $groupModel = ModelFactory::getGroupModel();
           
            // 当前呼叫者离线了.
            if ( ! $userService->isOnline($userId)) {
                return ;
            }
            
            if ($callService->isCalling($userId)) {
                throw new OperationFailureException("您有通话正在进行");
            }
            
            // 检测用户和群聊信息是否有效 
            if (!$userModel->existAll($userId) 
                || !$groupModel->existAll($groupId)) {
                im_log("error", "尝试使用不存在的 id 发送音频. from user " , $userId ," to group",$groupId);
                throw new OperationFailureException("用户或分组不存在.");
            }
            return $callService->joinChat($userId, $groupId, $callType);
        } catch (OperationFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            im_log("error", "加入群聊失败 !", $e);
            throw new OperationFailureException();
        }
    }
    
    public function requestCallReply($userId, $sign, $reply, $unread) {
        $callService = SingletonServiceFactory::getCallService();
        $callDetailField = RedisModel::getKeyName("im_chat_calling_communication_hash_key");
        $callUserField = RedisModel::getKeyName("im_calling_comm_user_hash", ["userId"=>$userId]);
        
        // 通话详情
        $data = RedisModel::hgetJson($callDetailField, $sign);
        if (!$data) {
            im_log("error", "通话详情丢失, sign:", $sign);
            RedisModel::del($callUserField);
            throw new OperationFailureException();
        }
        // 同意接听
        if ($reply != false) {
            // 群聊
            if (isset($data["groupid"])) {
                return $callService->joinChat($userId, $data["groupid"], $data["ctype"]);
            } else { // 双人
                $userId2 = boolean_select($userId !== $data["userid"], $data["userid"], $data["ruserid"]);
                return $callService->establish($userId, $userId2, $sign);
            }
        } else { // 拒绝接听
            println($userId , " 拒绝了会话 ", $sign, " ", $data);
            return boolean_select(isset($data["groupid"]), function() use($callUserField) {
                RedisModel::del($callUserField);
            }, function() use ($sign, $userId) {
                println($userId, " 拒绝了会话 ", $sign);
                $this->requestCallFinish($userId, $sign);
            });
        }
    }

    public function requestCallComplete($userId, $sign, $success) {
        // 如果连接失败, 调用断线
        if (!$success) {
            return $this->requestCallFinish($userId, $sign, "连接失败");
        }
        // 设置通话中
        // $detail_h = RedisModel::getKeyName("detail_h");
        $calling_l = RedisModel::getKeyName("calling_l");
        $calling_h = RedisModel::getKeyName("calling_h");
        
        $cache = Cache::store("redis");
        try {
            if (!$cache->lock($calling_l)
                || !$cache->lock($calling_h)) {
                im_log("error", "加锁失败. ", $calling_l, " ", $calling_h);
                throw new OperationFailureException("加锁失败. $calling_l $calling_h");
            }
            RedisModel::lpush($calling_l, $userId);
            RedisModel::hsetJson($calling_h, $userId, [
                "timestamp"=>time(),
                "losecount"=>0,
            ]);
        } catch (OperationFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            im_log("error", $e);
            throw new OperationFailureException();
        } finally {
            $cache->unlock($calling_l);
            $cache->unlock($calling_h);
        }
    }
    
    public function requestCallFinish($userId, $sign, $error=false)
    {
        try {
            println("通话结束 sign: ", $sign, " error: ", $error);
            // 参数检查
            if (is_null($sign) || !is_numeric($userId)) {
                return;
            }
            $callService = SingletonServiceFactory::getCallService();
            $gatewayService = SingletonServiceFactory::getGatewayService();
    
            $userModel = ModelFactory::getUserModel();
            
            $callDetailField = RedisModel::getKeyName("cache_calling_communication_info_hash_key");
            $callDetail = RedisModel::hgetJson($callDetailField, $sign);
           
            $finishIds = $callService->callFinish($userId, $sign);
            println($userId, " 断开了与 ", $finishIds, " 的连接");
            
            // 推送通话结束的信息
            array_for_each($finishIds, function($userId2) use ($userModel, $error, $userId, $callDetail, $callService, $sign, $gatewayService) {
                // 获取用户信息
                $users = $userModel->getUserById($userId, $userId2);
                $users = array_map_with_index($users, function($value) {
                    $user = $value;
                    array_key_replace($user, ["id"=> "userid", "avatar"=>"useravatar"]);
                    return array_index_pick($user, "userid", "username", "useravatar");
                });
                
                $users[1] = array_map_keys($users[1], function($value, $key) {
                    return "r$key";                
                });
                // println("用户信息", $users);
                // println("群聊详情", $callDetail);
                $data = array_merge($callDetail, $users[0], $users[1]);
                // println("推送信息", $data);
                $gatewayService->sendToUser($userId2, $data, IGatewayService::COMMUNICATION_FINISH);
            });
        } catch(\Error | \Exception $e) {
            im_log("error", $e);
            throw new OperationFailureException();
        }
    }
}