<?php
namespace app\im\service;

require_once implode([
    __DIR__,
    DIRECTORY_SEPARATOR,
    "IIMService.php"
]);

use app\im\model\ChatGroupModel;
use app\im\model\FriendsModel;
use app\im\exception\OperationFailureException;
use think\db\Query;
use GatewayClient\Gateway;
use think\Db;
use app\im\model\UserModel;
use think\db\Connection;
use think\Collection;

class IMServiceImpl implements IIMService, IChatService, IPushService
{
    private static $instance;
    private static  $query;
    public const USER_FIELD = "id, avatar, user_nickname AS username, signature AS sign";

    /**
     * 
     * @return IIMService | ChatService
     */
    public static function getInstance() : IMServiceImpl{
        if (!static::$instance) static::$instance = new static();
        static::$query  = new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
        return static::$instance;
    }
    
    /**
     * 便利方法
     * @return \think\db\Query
     */
    protected static function getQuery() {
        return static::$query;
    }
    
    public function createFriendGroup($userId, $groupName): array {
        try {
            $friendGroup = model("friend_groups")->getQuery();
            $date = date(static::SQL_DATE_FORMAT, time());
            // 得到分组优先级
            $priority = $friendGroup->where("user_id=:id")
            ->bind(["id"=> [$userId, \PDO::PARAM_INT]])
            ->max("priority");
            // 确定新分组的优先级
            if (is_numeric($priority)) $priority+=10;
            else $priority = 10;
            
            $data = [
                "user_id"=> $userId,
                "group_name"=> $groupName,
                "priority"=>$priority,
                "create_time"=>$date,
                "member_count"=>0
            ];
            // 新建分组
            $friendGroup  ->insert($data, false, true);
            return $data;
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "新建分组失败! 用户: $userId, 分组名称: $groupName", $e);
            throw new OperationFailureException("新建分组失败, 请稍后重试~");
        }
    }
    
    public function init($userId)
    {
        $friend = $this->findOwnFriends($userId);
        if (!count($friend)) $this->createFriendGroup($userId, "我的好友"); 
        return [
            "mine"=>$this->getUserById($userId)[0],
            "friend" => $this->findOwnFriends($userId),
            "group" => $this->findOwnGroups($userId)
        ];
    }

    public function findOwnFriends($userId): array
    {
        // return model("user_entire")::contacts($userId);
//         return UserModel::contacts($userId);
        return model("friends")->getFriendAndGroup($userId);
    }

    

    public function findOwnGroups($userId): array
    {
        try {
            $res = model("groups")
                ->getQuery()
                ->field("id, contact_id, groupname, avatar, member_count, description")
                ->join("im_group", "im_groups.contact_id = im_group.id")
                ->where("im_groups.user_id", "=", $userId)
                ->select()
                ->toArray();
            // im_log("debug", $res);
            return $res; 
        } catch(\Exception $e) {
            im_log("error", "分组查询失败了, 用户的 id: $userId; error: ", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }

    public function findFriends($key): array
    {
        try {
            return model("user")->getQuery()
                ->field(self::USER_FIELD)
                ->where("id", "=", $key)
                ->union(function(Query $q) use($key) {
                    $q->table("cmf_user")
                        ->field(self::USER_FIELD)
                        ->where("user_nickname", "LIKE", "%$key%");
                })
                ->select()
                ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查找用户失败. error: ", $e);
            throw new OperationFailureException("查找失败, 请稍后重试~");
        }
    }

    public function findGroups($key): array
    {
        try {
            return model("group")->getQuery()
                ->where("id", "=", $key)
                ->union(function(Query $q) use($key) {
                    $q->table("im_group")->where("groupname", "LIKE", "%$key%"); 
                })
                ->select()
                ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查找分组失败. error: ", $e);
            throw new OperationFailureException("查找失败, 请稍后重试~");
        }
    }
    
    public function getGroupById($id): array
    {
        try {
            $query = model("group")->getQuery();
            return $query->where("id", "=", $id)
            ->select()
            ->toArray();
        } catch(\Exception $e) {
            im_log("error", "分组查询失败 ! id: $id", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }

    public function getOwnFriendGroups($userId): array
    {
        try {
            return model("friend_groups")->getQuery()
                ->alias("friend_g")
                ->field("id, group_name")
                ->where("friend_g.user_id=:id")
                ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
                ->select()
                ->toArray();
        } catch (OperationFailureException $e) {
            throw $e;
        } catch (\Exception $e) {
            im_log("error", "好友分组查询失败. ", $e);
            throw new OperationFailureException("好友分组查询失败, 请稍后重试~");
        }
    }
    
    public function getUnreadMessage($userId, $contactId, $type, $pageNo=0, $pageSize=100): \think\Collection
    {
        $resultSet = null;
        // 查询好友间未读信息
        if ($type === self::CHAT_FRIEND) {
            // 获取最后一条接收到的消息
//             dump([$userId, $contactId]);
            $lastMsgId = model("friends")->getLastRead($userId, $contactId);
            // 获取未读信息
            $resultSet = static::getQuery()->table("im_chat_user")
                ->alias("chat")
                ->field("user.id, avatar, user_nickname AS username, send_date AS date, content, chat.id AS cid, UNIX_TIMESTAMP(chat.send_date) AS send_date")
                ->join(["cmf_user"=>"user"], "chat.sender_id=user.id", "LEFT OUTER")
                ->where("chat.id >= :unReadId AND (chat.sender_id=:sender AND chat.receiver_id=:receiver AND chat.visible_sender=1 OR chat.sender_id=:receiver2 AND chat.receiver_id=:sender2 AND chat.visible_receiver=1)")
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
        }
        // 查询与群组的未读记录
        if ($type === self::CHAT_GROUP) {
            // 获取最后一条接收到的消息
            $lastMsgId = model("groups")->getLastRead($userId, $contactId);
            // 获取未读信息
            $resultSet = static::getQuery()->table("im_chat_group")
                ->alias("chat")
                ->field("user.id, user.avatar, user_nickname AS username, content, chat.id AS cid, chat.group_id, UNIX_TIMESTAMP(chat.send_date) AS send_date")
                ->join(["cmf_user"=>"user"], "chat.sender_id=user.id")
                ->where("chat.id >= :unReadId AND chat.group_id=:gid")
                ->bind([
                    "unReadId"=>[$lastMsgId, \PDO::PARAM_INT],
                    "gid"=>[$contactId, \PDO::PARAM_INT],
                ])
                ->order("chat.id", "DESC")
                ->limit($pageSize*$pageNo, $pageSize)
                ->select();
        }
        return $resultSet;
    }

    public function getUserById($userId): array
    {
        
        // 查找用户信息, im 扩展信息. 如果不存在, 更新进去.
        //         $model = model("user");
        //         $user = $model->get($userId);
        //         if (is_null($user) || ! count($user = $user->getData())) {
        //             $user = [
        //                 "user_id" => $userId,
        //                 "sign" => ""
        //             ];
        //             $model->save($user);
        //         }
        //         return $user;
        
        try {
            return model("user")->getQuery()
            ->field(self::USER_FIELD)
            ->where("id=:id")
            ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
            ->select()
            ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查询用户失败, 用户的 id: $userId; error: ", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function createGroup($creator, string $groupName, string $pic, string $desc): void
    {
        // 检查群名称是否已经存在.
        if (count($this->getGroupByName($groupName))) {
            throw new OperationFailureException("名称已经存在.");
        }
        
        $group = model("group")->getQuery();
        $groups = model("groups")->getQuery();
        $chatGroup = model("chat_group")->getQuery();

        $dateStr = date(self::SQL_DATE_FORMAT);

        try {
            $group->startTrans();

            // 插入群组表
            $groupId = $group->insert([
                "groupname" => $groupName,
                "description" => $desc,
                "avatar" => $pic,
                "creator_id" => $creator,
                "create_time" => $dateStr,
                "member_count"=>1
            ], false, true);
            
            if (!is_numeric($groupId)) {
                im_log("error", "创建群聊失败, id: ", $groupId);
                throw new OperationFailureException("无法获取群聊 id.");
            }
            
            // 插入群聊信息
            $chatId = $chatGroup->insert([
                "group_id"=>$groupId,
                "sender_id"=>0,
                "send_date"=>$dateStr,
                "content"=>implode(["用户 ", $creator, " 加入了群聊."])
            ], false, true);

            if (!is_numeric($chatId)) {
                im_log("error", "插入群信息失败, id: ", $chatId);
                throw new OperationFailureException("无法获取群聊 id.");
            }

            $groups->insert([
                "user_id" => $creator,
                "contact_id" => $groupId,
                "contact_date" => $dateStr,
                "last_active_time" => $dateStr,
                "last_send_time" => $dateStr,
                "last_reads" => $chatId
            ]);
            $group->commit();
            
            // 通知前端做出反应
            GatewayServiceImpl::addToUid($creator, [[
                "type"=>"group",
                "avatar" => $pic,
                "groupname" => $groupName,
                "id"=>$groupId
            ]]);
            im_log("info", "创建群聊成功. 用户: ", $creator, "; 群聊: ", $groupId, ", ", $groupName);
        } catch (\Exception $e) {
            im_log("error", "创建群聊失败 !", "错误信息: ", $e);
            $group->rollback();
            throw new OperationFailureException("群聊创建失败, 请稍后重试~");
        }
    }
    
    public function getGroupByName($name, $exact = true): array
    {
        $group = model("group")->getQuery();
        if ($exact) {
            $operate = "=";
            $condition = $name;
        } else {
            $operate = "LIKE";
            $condition = "%$name%";
        }
        try {
            $res = $group->where("groupname", $operate, $condition)
                ->select();
            return $res->toArray();
        } catch(\ErrorException $e) {
            im_log("error", "查询失败 !", $e);
            throw new OperationFailureException("查询失败了.");
        }
    }
    
    public function linkFriendMsg($sender, $friendGroupId, $receiver, $content, $ip = null): void
    {
        if ($sender == $receiver) 
            throw new OperationFailureException("您不能添加自己为好友~"); 
            
        $dateStr = date(self::SQL_DATE_FORMAT);
        $query = model("msg_box")->getQuery();
        $friendGroup = model("friend_groups")->getQuery();
        try {
            // 检查分组 id 是否有效
            if (!$friendGroup->where([
                    "id"=>$friendGroupId,
                    "user_id"=>$sender
                ])->count()) {
                    // 分组 id 无效, 查找默认分组
                    $friendGroupId = $friendGroup->field("id")
                        ->where("user_id=:id")
                        ->bind(["id"=>[$sender, \PDO::PARAM_INT]])
                        ->order("priority", "asc")
                        ->select()
                        ->column("id");
                }

            $data = [
                "sender_id"=>$sender,
                "sender_friendgroup_id"=>$friendGroupId,
                "send_date"=>$dateStr,
                "receiver_id"=>$receiver,
                "content"=>$content
            ];
            if (!is_null($ip)) $data["send_ip"] = $ip;
            $msgId = $query
                ->insert($data, false, true);
            if (count(Gateway::getClientIdByUid($receiver))) {
                $msg = $query->where("id", "=", $msgId)->select()->toArray();
                // 错误了, 没得信息.
                if (!count($msg)) {
                    im_log("error", "消息盒子信息插入异常. id: $msgId, 内容: ", $msg);
                    throw new OperationFailureException("信息发送异常 !");
                }
                // 发送消息
//                 GatewayServiceImpl::askToUid($receiver, $msg);
                $this->pushMsgBoxNotification($receiver);
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息插入失败 !", $e);
            throw new OperationFailureException("消息发送失败 !");
        }
    }

    public function linkGroupMsg($sender, $groupId, $content, $ip = null): void
    {
        $dateStr = date(self::SQL_DATE_FORMAT);
        $msgBox = model("msg_box")->getQuery();
        $group = model("group")->getQuery();
        $groups = model("groups")->getQuery();
        
        
        try {
            // 判断是否已是群成员.
            if ($groups->where([
                "user_id"=>["=", $sender],
                "contact_id"=>["=", $groupId]
            ])->count()) {
                throw new OperationFailureException("您已经是群聊成员了~");
            }
            
            // 查询群组管理者 id.
            $receiver = $group -> where("id", "=", $groupId)
                ->field("creator_id")
                ->select();
            
            if (!count($receiver) || !isset($receiver[0]["creator_id"])) {
                im_log("error", "加入不存在的群聊 ! 群聊 id: $groupId, 查询结果: ", $receiver);
                throw new OperationFailureException("群聊不存在~");
            }
            // 获取到群聊管理者的 id.
            $receiver = $receiver[0]["creator_id"];
            
            $data = [
                "sender_id"=>$sender,
                "send_date"=>$dateStr,
                "receiver_id"=>$receiver,
                "group_id"=>$groupId,
                "content"=>$content
            ];
            if (!is_null($ip)) $data["send_ip"] = $ip;
            $msgId = $msgBox
                ->insert($data, false, true);
            if (count(Gateway::getClientIdByUid($receiver))) {
                $msg = $msgBox->where("id", "=", $msgId)->select()->toArray();
                // 错误了, 没得信息.
                if (!count($msg)) {
                    im_log("error", "消息盒子信息插入异常. id: $msgId, 内容: ", $msg);
                    throw new OperationFailureException("信息发送异常 !");
                }
                // 发送消息
//                 GatewayServiceImpl::askToUid($receiver, $msg);
                $this->pushMsgBoxNotification($receiver);
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息插入失败 !", $e);
            throw new OperationFailureException("消息发送失败 !");
        }
    }
    
    public function pushAll($uid): bool
    {
        $this->pushMsgBoxNotification($uid);
        $this->pushUnreadMessage($uid);
        return true;
    }
    
    
    public function pushMsgBoxNotification($uid): bool
    {
        try {
            $msgbox = model("msg_box")->getQuery();
            $count = $msgbox->where("receiver_id=:id AND agree IS NULL")
                ->bind("id", $uid, \PDO::PARAM_INT)
                ->count();
                if ($count) {
                    GatewayServiceImpl::askToUid($uid, ["msgCount"=>$count]);
                }
                return true;
        } catch (\Exception $e) {
            im_log("error", "消息盒子检查失败 ! id: $uid", $e);
        }
        return false;
    }

    
    public function pushUnreadMessage($uid,$contactId=null, $type=IChatService::CHAT_FRIEND): bool
    {
        $friend = $group = null;
        if ($contactId) {
            $friend = $this->getUnreadMessage($uid, $contactId, static::CHAT_FRIEND)->toArray();
            $group = $this->getUnreadMessage($uid, $contactId, static::CHAT_GROUP)->toArray();
        } else {
            // 循环执行 sql, 之后再改吧.
            // 获取所有好友和分组
            $friends = model("friends")->getFriends($uid)->toArray();
            $groups = model("groups")->getGroups($uid)->toArray();
            // 查询所有未读消息
            $friend = [];
//             var_dump($friends);
            foreach ($friends as $item) {
//                 var_dump([$uid, $item["contact_id"]]);
                $result = $this->getUnreadMessage($uid, $item["contact_id"], static::CHAT_FRIEND)->toArray();
                $friend = array_merge($friend, $result);
            }
            $group=[];
            foreach ($groups as $item) {
                $result = $this->getUnreadMessage($uid, $item["contact_id"], static::CHAT_GROUP)->toArray();
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
                "require"=> true // 表示前端强制添加此消息, 用于和自己发送的消息相区分
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
                "mine"=>false,
                "fromid"=>$item["id"],
                "timestamp"=>$item["send_date"]*1000,
                "require"=> true
            ];
        }, $friend);
        GatewayServiceImpl::msgToGroup($uid, $group);
        GatewayServiceImpl::msgToUid($uid, $friend);
        return true;
    }
    
    public function readChatGroup($groupId, int $pageNo=0, int $pageSize=100)
    {
        if ($pageSize > 500)$pageSize = 100;
        try {
//             $pageSize=1;
            return model("chat_group")->getQuery()
                ->alias("g")
                ->field("u.id AS id, u.user_nickname AS username, avatar, send_date AS date, content")
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
            $map=[];
            $mapx=[];
            $map['chat.sender_id'] = $Id;
            $map['chat.receiver_id'] = $userId;
            $map['chat.visible_sender']=1;
            $mapx['chat.sender_id'] = $userId;
            $mapx['chat.receiver_id'] = $Id;
            $mapx['chat.visible_receiver']=1;
           
            return Db::table('im_chat_user chat')
                ->where($map)
                ->whereOr($mapx)
                ->join(['cmf_user'=>'user'],'chat.sender_id=user.id')
                ->field('user.id, avatar, user_nickname AS username, send_date AS date, content')
                ->order('date desc')
//                 ->paginate($pageSize);
                ->limit($pageNo*$pageSize, $pageSize)
                ->select();
            
//             return model("chat_user")->getQuery()
//                 ->alias("chat")
//                 ->field("user.id, avatar, user_nickname AS username, send_date AS date, content")
//                 ->join(["cmf_user"=> "user"], "chat.sender_id=user.id ")
//                 ->where("chat.sender_id=:id AND chat.receiver_id=:id2 AND chat.visible_sender=1 OR chat.sender_id=:id2 AND chat.receiver_id=:id AND chat.visible_receiver=1")
//                 ->bind([
//                     "id"=>[ $Id, \PDO::PARAM_INT],
//                     "id2"=>[ $userId, \PDO::PARAM_INT],
//                 ])
//                 ->order("date", "desc")
//                 ->limit($pageNo*$pageSize, $pageSize)
//                 ->select();
        } catch(\Exception $e) {
            im_log("error", "读取聊天信息失败! $userId", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
    }
    
    public function sendToGroup($fromId, $toId, $content, $ip=null)
    {
        $query = static::getQuery();
        try {
            $from = $this->getUserById($fromId);
            $to = $this->getGroupById($toId);
            // 数据检查
            if (!count($from) || !count($to)) {
                im_log("error", "尝试使用不存在的 id 发送信息. from user $fromId to group $toId: $content.");
                throw new OperationFailureException("用户或分组不存在.");
            }
            // 存入记录
            $timestamp = time();
            $date = date(static::SQL_DATE_FORMAT, $timestamp);
            $chatData = [
                "group_id"=>$toId,
                "sender_id"=>$fromId,
                "send_date"=>$date,
                "send_ip"=>$ip,
                "content"=>$content
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }
            
            $query->startTrans();
            // 插入聊天记录表
            $msgId = $query->table("im_chat_group")
                ->insert($chatData, false, true);
            // 更新群组关系信息表
            $query->table("im_groups")
                ->where("user_id=:uid AND contact_id=:gid")
                ->bind([
                    "uid"=>[$fromId, \PDO::PARAM_INT],
                    "gid"=>[$toId, \PDO::PARAM_INT]
                ])
                ->update([
                    "last_active_time"=>$date,
                    "last_send_time"=>$date,
                    "last_reads"=>$msgId
                ]);
            $query->commit();
//             im_log("debug", $from);
//             im_log("debug", $from["username"]);
            $from =$from [0];
            // 推送到群组
            GatewayServiceImpl::msgToGroup($toId, [[
                "username"=>$from["username"],
                "avatar"=>$from["avatar"],
                "id"=>$toId,
                "type"=>"group",
                "content"=>$content,
                "cid"=>$msgId,
                "mine"=>false,
                "fromid"=>$fromId,
                "timestamp"=>$timestamp*1000
            ]]);
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            $query->rollback();
            im_log("error", "消息发送失败. from user $fromId to group $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }
    
    public function sendToUser($fromId, $toId, $content, $ip=null)
    {
        $query = static::getQuery();
        try {
            if ($fromId == $toId) {
                im_log("error", "尝试给自己发消息. user: $fromId");
                throw new OperationFailureException("请不要给自己发消息~");
            }
            $user = model("user")->getUserById($fromId, $toId);
            // 数据检查
            if (!$user->count()) {
                im_log("error", "尝试使用不存在的 id 发送信息. from user $fromId to user $toId: $content. resultSet: ", $user);
                throw new OperationFailureException("用户或分组不存在.");
            }
            // 存入记录
            $timestamp = time();
            $date = date(static::SQL_DATE_FORMAT, $timestamp);
            $chatData = [
                "receiver_id"=>$toId,
                "sender_id"=>$fromId,
                "send_date"=>$date,
                "send_ip"=>$ip,
                "content"=>$content,
                "visible_sender"=>1,
                "visible_receiver"=>1
            ];
            if (is_string($ip)) {
                $chatData["send_ip"] = $ip;
            }
            
            $query->startTrans();
            // 插入聊天记录表
            $msgId = $query->table("im_chat_user")
                ->insert($chatData, false, true);
            // 更新群组关系信息表
            $query->table("im_friends")
                ->where("user_id=:fid AND contact_id=:tid")
                ->bind([
                    "fid"=>[$fromId, \PDO::PARAM_INT],
                    "tid"=>[$toId, \PDO::PARAM_INT]
                ])
                ->update([
                    "last_active_time"=>$date,
                    "last_send_time"=>$date,
                    "last_reads"=>$msgId
                ]);
            $query->commit();
            
            $from=[];
//             $to=[];
            $user = $user->toArray();
            if ($user[0]["id"] !== $fromId) {
//                 $to = $user[0];
                $from = $user[1];
            } else {
                $from = $user[0];
//                 $to = $user[1];
            }
            // 推送到用户
            if (Gateway::isUidOnline($toId)) {
                GatewayServiceImpl::msgToUid($toId, [[
                    "username"=>$from["username"],
                    "avatar"=>$from["avatar"],
                    "id"=>$fromId,
                    "type"=>"friend",
                    "content"=>$content,
                    "cid"=>$msgId+0,
                    "mine"=>false,
                    "fromid"=>$fromId,
                    "timestamp"=>$timestamp*1000
                ]]);
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息发送失败. from user $fromId to user $toId: $content. ", $e);
            throw new OperationFailureException("消息发送失败, 请稍后重试~");
        }
    }
    

    
    public function updateUser($userId, $data): bool {
        try {
            $query = new Query(Db::connect(array_merge(config("database"), ['prefix'   => ''])));
            $affected = $query->table("cmf_user")
                ->where("id=:id")
                ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
                ->update($data);
            im_log("debug", "更新用户信息 $userId, 受影响行数 $affected.");
            return true;
        } catch(\Exception $e) {
            im_log("error", "更新用户信息失败! $userId, 信息: ", $data, ", error: ", $e);
//             throw new OperationFailureException("更新失败, 请稍后重试~");
            return false;
        }
    }
    
}