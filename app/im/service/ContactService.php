<?php
namespace app\im\service;

use GatewayClient\Gateway;
use app\im\exception\OperationFailureException;
use app\im\model\IMessageModel;
use app\im\model\ModelFactory;
use think\Db;
use think\db\Query;

class ContactService implements IContactService {
    const failureMsg = "操作失败, 请稍后重试～";
    
    public function addFriend($id1, $fgId1, $id2, $fgId2) {
        im_log("debug", 2);
        try {
            im_log("debug", "开始添加好友 $id1 $fgId1, $id2 $fgId2");
            // 判断是否已经是好友关系
            if (ModelFactory::getFriendModel()->isFriend($id1, $id2)) {
                throw new OperationFailureException("你们已经是好友了.");
            }
            // 判断是否有指定分组
            $fgs = ModelFactory::getFriendModel()->getFriendGroupById($fgId1, $fgId2);
            if (count($fgs) != 2 
                || $fgs[0]["uid"] != $id1 
                || $fgs[1]["uid"] != $id2) {
                im_log("error", "用户 $id1 无分组 $fgId1 或用户 $id2 无分组 $fgId2");
                throw new OperationFailureException("指定分组不存在.");
            }
            Db::startTrans();
            // 添加好友
            if (!ModelFactory::getFriendModel()->setFriend($id1, $fgId1, $id2, $fgId2)) {
                im_log("error", "好友设置失败.");
                throw new OperationFailureException();
            }
            // 更新分组人数
            ModelFactory::getFriendModel()->updateFriendGroup($fgId1, [
                "member_count"=>$fgs[0]["membercount"] + 1
            ]);
            ModelFactory::getFriendModel()->updateFriendGroup($fgId2, [
                "member_count"=>$fgs[1]["membercount"] + 1
            ]);
            Db::commit();
            // 推送添加命令
            $user = ModelFactory::getUserModel()->getUserById($id1, $id2);
            $user1 = $user[0];
            $user2 = $user[1];
            SingletonServiceFactory::getGatewayService()
                ->addToUid($id2, [[
                    "type"=>"friend",
                    "groupid"=>$fgId2,
                    "id" =>$user1["id"],
                    "avatar"=>$user1["avatar"],
                    "sign"=>$user1["sign"],
                    "username"=>$user1["username"]
                ]]);
            SingletonServiceFactory::getGatewayService()
                ->addToUid($id1, [[
                    "type"=>"friend",
                    "groupid"=>$fgId1,
                    "id" =>$user2["id"],
                    "avatar"=>$user2["avatar"],
                    "sign"=>$user2["sign"],
                    "username"=>$user2["username"]
                ]]);
            // 发送添加成功的消息
            $content = "我们已经是好友了，快来开始聊天吧～";
            SingletonServiceFactory::getChatService()
                ->sendToUser($id1, $id2, $content);
            SingletonServiceFactory::getChatService()
                ->sendToUser($id2, $id1, $content);
            
        } catch (OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            im_log("error", "添加好友失败.", $e);
            throw new OperationFailureException();
        }
    }
    
    public function addFriendAsk($sender, $friendGroupId, $receiver, $content, $ip=null) {
        // 判断自己
        if ($sender == $receiver)  {
            throw new OperationFailureException("您不能添加自己为好友~");
        }
        // 判断已经是好友
        if (model("friends")->isFriend($sender, $receiver)) {
            throw  new OperationFailureException("你们已经是好友了~");
        }
        
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
            // 构成消息数据
            $data = [
                "sender_id"=>$sender,
                "send_date"=>time(),
                "content"=>$content,
                "corr_id"=>$friendGroupId,
                "type"=>IMessageModel::TYPE_FRIEND_ASK
            ];
            if (!is_null($ip)) $data["send_ip"] = $ip;
            ModelFactory::getMessageModel()->createMessage($data, [$receiver]);

            if (Gateway::isUidOnline($receiver)) {
                // 发送消息
                SingletonServiceFactory::getPushService()->pushMsgBoxNotification($receiver);
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息插入失败 !", $e);
            throw new OperationFailureException("消息发送失败 !");
        }
    }
    
    

    public function createFriendGroup($userId, $groupName): array
    {
        try {
            $friendGroup = model("friend_groups")->getQuery();
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
                "create_time"=>time(),
                "member_count"=>0
            ];
            // 新建分组
            $data = ModelFactory::getFriendModel()->createFriendGroup($userId, $data);
            return $data;
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "新建分组失败! 用户: $userId, 分组名称: $groupName", $e);
            throw new OperationFailureException("新建分组失败, 请稍后重试~");
        }
    }
    
    public function createGroup($creator, string $groupName, string $pic, string $desc) {
        // 检查群名称是否已经存在.
        if (count($this->getGroupByName($groupName))) {
            throw new OperationFailureException("名称已经存在.");
        }
        
        $group = model("group")->getQuery();
        $groups = model("groups")->getQuery();
        $chatGroup = model("chat_group")->getQuery();
        
        try {
            $group->startTrans();
            
            // 插入群组表
            $groupId = $group->insert([
                "groupname" => $groupName,
                "description" => $desc,
                "avatar" => $pic,
                "creator_id" => $creator,
                "create_time" => time(),
                "admin_id" => $creator,
                "admin_count" => 1,
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
                "send_date"=>time(),
                "content"=>implode(["用户 ", $creator, " 加入了群聊."])
            ], false, true);
            
            if (!is_numeric($chatId)) {
                im_log("error", "插入群信息失败, id: ", $chatId);
                throw new OperationFailureException("无法获取群聊 id.");
            }
            
            $groups->insert([
                "user_id" => $creator,
                "contact_id" => $groupId,
                "is_admin" => 1,
                "contact_date" => time(),
                "last_active_time" => time(),
                "last_send_time" => time(),
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
    
    public function deleteFriend($userId, $userId2) {
        try {
            // 参数检查
            $friend = ModelFactory::getFriendModel()->getFriendAndGroupById($userId, $userId2);
            $friend2 = ModelFactory::getFriendModel()->getFriendAndGroupById($userId2, $userId);
            if (!is_array($friend) || count($friend) < 1 || !is_array($friend2) || count($friend2) < 1 ) {
                im_log("error", "删除好友失败, 参数错误. user: ", $userId, ", ", $userId2, "; friend: ", $friend, ", ", $friend2);
                throw new OperationFailureException();
            }
            $friend = $friend[0];
            $friend2 = $friend2[0];
            
            Db::startTrans();
            // 删除好友
            $effect = ModelFactory::getFriendModel()->deleteFriendById([$userId], [$userId2]);
            if ($effect != 2) {
                im_log("error", "未能成功删除好友信息, effec: ", $effect, ", userId: $userId, $userId2");
                throw new OperationFailureException();
            }
            // 更新分组人数
            im_log("debug", $friend, $friend2);
            ModelFactory::getFriendModel()->updateFriendGroup($friend["id"], ["member_count"=>$friend["membercount"]-1]);
            ModelFactory::getFriendModel()->updateFriendGroup($friend2["id"], ["member_count"=>$friend2["membercount"]-1]);
            Db::commit();
            // 推送解除好友的命令
            SingletonServiceFactory::getGatewayService()
                ->sendToUser($userId, [$friend["list"][0]], IGatewayService::TYPE_FRIEND_REMOVE);
            SingletonServiceFactory::getGatewayService()
                ->sendToUser($userId2, [$friend2["list"][0]], IGatewayService::TYPE_FRIEND_REMOVE);
            // 创建解除好友的消息
            ModelFactory::getMessageModel()->createMessage([
                "sender_id"=>0,
                "corr_id"=>$userId,
                "send_date"=>time(),
                "content"=>$friend2["list"][0]["username"]."解除了与您的好友关系.",
                "type"=>IMessageModel::TYPE_FRIEND_BE_REMOVED
            ], [$userId2]);
            
            if (Gateway::isUidOnline($userId2)) {
                SingletonServiceFactory::getPushService()->pushMsgBoxNotification($userId2);
            }
        } catch(OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch(\Exception $e) {
            Db::rollback();
            im_log("error", "删除好友失败 ", $e);
            throw new OperationFailureException();
        }
    }
    
    public function deleteFriendGroup($userId, $fgId, $reserve=null)  {
        try {
            // 删除分组和要转移的分组相同
            if ($fgId == $reserve) {
                throw new OperationFailureException("转移分组和删除分组相同");
            }
            $friend = ModelFactory::getFriendModel()->getFriendGroupById($fgId);
            // 检查分组是否存在以及用户是否拥有此分组.
            if (!is_array($friend) 
                || count($friend) < 1 
                || $friend[0]["uid"] != $userId) {
                im_log("error", "用户 ", $userId, " 尝试删除不存在或不属于其的分组 ", $fgId, " 查询结果: ", $friend);
                throw new OperationFailureException("分组不存在");
            }
            $friend = $friend[0];
            // 默认分组不可删除
            if ($friend["groupname"] == "我的好友") {
                throw new OperationFailureException("默认分组不可删除");
            }
            // 获取用于转移用户的分组
            $fgroup = null;
            if ($friend["membercount"] > 0) {
                // 检查用户是否拥有指定的分组用于移动好友
                if ($reserve != null) {
                    $fgroup = ModelFactory::getFriendModel()->getFriendGroupById($reserve);
                }
                if (!is_array($fgroup) || count($fgroup) < 1) {
                    // 获取默认分组
                    $fgroup = ModelFactory::getFriendModel()->determineFriendGroupByName($userId, "我的好友");
                    if (!is_array($fgroup) || count($fgroup) < 1) {
                        im_log("error", "无法获取默认分组 \"我的好友\". userId: $userId, 获取的分组信息: ", $fgroup);
                        throw new OperationFailureException();
                    }
                }
                // 获取的用于转移用户的分组信息
                $fgroup = $fgroup[0];
            }
            Db::startTrans();
            // 转移用户
            if ($fgroup != null) {
                ModelFactory::getFriendModel()->updateFriendByFriendGroup($userId, $fgId, ["group_id"=>$fgroup["id"]]);
            }
            // 删除分组
            ModelFactory::getFriendModel()->deleteFriendGroupById($fgId);
            Db::commit();
        } catch(OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch(\Exception $e) {
            Db::rollback();
            im_log("debug", "删除好友分组失败. ", $e);
            throw new OperationFailureException();
        }
    }
    
    public function deleteGroupMember($userId, $gid, $uid) {
        try {
            foreach([$userId, $gid, $uid] as $m) {
                if (!is_numeric($m) || $m < 1) {
                    throw new OperationFailureException("参数类型错误");
                }
            }
            // 检测群聊是否存在
            $group = model("group")->getGroupById($gid);
            if (!is_array($group) || count($group) == 0) {
                im_log("error", "群组不存在. gid: ", $gid);
                throw new OperationFailureException("群组不存在");
            }
            $group = $group[0];
            // 检测成员是否存在
            $members = model("group")->getGroupMemberById($gid, $userId, $uid);
            if (!is_array($members) || count($members) != 2) {
                im_log("error", "成员 $userId 或 $uid 不存在 $gid 中.");
                throw new OperationFailureException("群聊中无此成员");
            }
            $me = $members[0];
            $he = $members[1];
            
            // 如果用户等级不高于删除用户的等级, 将操作失败
            if (!$me["isadmin"] || $he["isadmin"] 
                && $me["id"] != $group["admin"]) {
                throw new OperationFailureException("权限不足");
            }
            Db::startTrans();
            // 删除用户
            $effect = model("group")->deleteGroupMemberById($gid, $he["id"]);
            im_log("debug", "删除行: ", $effect);
            // 更新群聊人数
            model("group")->updateGroup($gid, [
                "member_count"=>$group["membercount"]-$effect
            ]);
            // throw new \Exception("123");
            // 生成消息通知
            $adminIds = model("group")->getGroupAdminIds($gid);
            $mData = [
                "sender_id"=>0,
                "send_date"=>time(),
                "type"=>IMessageModel::TYPE_GROUPMEMBER_REMOVE,
                "content"=>"",
                "corr_id"=>$me["id"],
                "corr_id2"=>$he["id"],
                "corr_id3"=>$group["id"]
            ];
            $msgData = model("msg_box")->createMessage($mData, $adminIds);
            if (!$msgData) {
                im_log("debug", "消息插入失败.", $msgData);
                throw new OperationFailureException(static::failureMsg);
            }
            // 生成被移除者的消息通知
            $mData = [
                "sender_id"=>0,
                "send_date"=>time(),
                "content"=>"",
                "type"=>IMessageModel::TYPE_GROUPMEMBER_BE_REMOVED,
//                 "corr_id"=>$me["id"],
                "corr_id"=>$group["id"]
            ];
            $result = model("msg_box")->createMessage($mData, [$uid]);
            if (!$result) {
                im_log("debug", "消息插入失败.", $result);
                throw new OperationFailureException(static::failureMsg);
            }
            // 提交
            \think\Db::commit();
            // 发送退出群聊的命令
            SingletonServiceFactory::getGatewayService()->sendToUser($uid, [$group], IGatewayService::TYPE_GROUP_REMOVE);
            // 推送通知
            foreach(array_merge($adminIds, [$uid]) as $id) {
                SingletonServiceFactory::getPushService()->pushMsgBoxNotification($id);
            }
            return true;
        } catch(OperationFailureException $e) {
            \think\Db::rollback();
            throw $e;
        } catch(\Exception $e) {
            \think\Db::rollback();
            im_log("error", "删除群聊成员失败.", $e);
            throw new OperationFailureException(static::failureMsg);
        }
    }
    
    public function getFriendAndGroup($userId): array
    {
        $data = model("friends")->getFriendAndGroup($userId);
        if (!is_array($data) || count($data) == 0) {
            $data = $this->createFriendGroup($userId, "我的好友");
        }
        return $data;
    }
    
    public function getGroupByCondition($condition, $pageNo=1, $pageSize=50): array {
        try {
            return model("group")->getQuery()
            ->where("id", "=", $condition)
            ->union(function(Query $q) use($condition) {
                $q->table("im_group")->where("groupname", "LIKE", "%$condition%");
            })
            ->page($pageNo, $pageSize)
            ->select()
            ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查找分组失败. error: ", $e);
            throw new OperationFailureException("查找失败, 请稍后重试~");
        }
    }
    
    public function getGroupByUser($userId, $include = false): array {
        $groups = model("group")->getGroupByUser($userId, $include);
        if (!is_array($groups)) {
            im_log("error", "用户 id 不符规范: $userId");
            throw new OperationFailureException("查询失败, 请稍后重试～");
        }
        try {
            // 不包含群聊中的用户信息
            if (!$include) {
                foreach ($groups as &$m) {
                    array_key_replace_force($m, [
                        "gid"=>"id",
                        "gavatar"=>"avatar"
                    ]);
                }
                return $groups;
                // 包含群聊中的用户信息, 整理数据格式.
            } else {
                // 返回的值
                $result = [];
                // 当前群聊的 id
                $currentGid = null;
                for ($i=count($groups)-1; $i>=0; $i--) {
                    // 返回的群聊是通过 id 排序的
                    if ($currentGid != $groups[$i]["gid"]) {
                        $currentGid = $groups[$i]["gid"];
                        array_push($result, [
                            "id"=>$groups[$i]["gid"],
                            "groupname"=>$groups[$i]["groupname"],
                            "description"=>$groups[$i]["description"],
                            "avatar"=>$groups[$i]["gavatar"],
                            "createtime"=>$groups[$i]["createtime"],
                            "admin"=>$groups[$i]["admin"],
                            "admincount"=>$groups[$i]["admincount"],
                            "membercount"=>$groups[$i]["membercount"],
                        ]);
                    }
                    // 群成员
                    if (!isset($result[count($result)-1]["list"])) {
                        $result[count($result)-1]["list"] = [];
                    }
                    array_push($result[count($result)-1]["list"], [
                        "id"=>$groups[$i]["uid"],
                        "username"=>$groups[$i]["username"],
                        "avatar"=>$groups[$i]["avatar"],
                        "sign"=>$groups[$i]["sign"],
                        "sex"=>$groups[$i]["sex"],
                        "isadmin"=>$groups[$i]["isadmin"],
                        "status"=>SingletonServiceFactory::getUserService()->isOnline($groups[$i]["uid"])? "online":"offline"
                    ]);
                }
                return $result;
            }
        } catch(\Exception $e) {
            im_log("error", "群聊查找失败.", $e);
            throw new OperationFailureException(static::failureMsg);
        }
    }
    
    public function joinGroup($userId, $groupId, $helloMsg=null) {
//         $ownTransaction = false;
        try {
            foreach([$userId, $groupId] as $m) {
                if (!is_numeric($m) || $m <1) {
                    throw new OperationFailureException("参数错误");
                }
            }
            // 检测成员是否在群聊中
            if (ModelFactory::getGroupModel()->inGroup($userId, $groupId)) {
                throw new OperationFailureException("已在群聊中");
            }
            // 判断用户是否存在
            $user = ModelFactory::getUserModel()->getUserById($userId);
            if (count($user) < 1) {
                throw new OperationFailureException("用户不存在");
            }
            $user = $user[0];
            // 判断群聊是否存在
            $group = ModelFactory::getGroupModel()->getGroupById($groupId);
            if (count($group) < 1) {
                throw new OperationFailureException("群聊不存在");
            }
            $group = $group[0];
            if ($helloMsg == null) {
                $helloMsg = "大家好, 我是".$user["username"];
            }
            im_log("debug", "开始添加群聊 ", $userId, ", ", $groupId);

            // 事务开启
           Db::startTrans();
            
            if (!ModelFactory::getGroupModel()
                ->setGroup($userId, $groupId)) {
                    im_log("error", "群聊设置失败.");
                    throw new OperationFailureException();
                }
           // 更新群聊人数
            ModelFactory::getGroupModel()->updateGroup($groupId, [
                "member_count"=>$group["membercount"]+1
            ]);
            // 推送加群信息
            SingletonServiceFactory::getGatewayService()
                ->addToUid($userId, [[
                        "type"=>"group",
                        "id"=>$group["id"],
                        "avatar"=>$group["avatar"],
                        "groupname"=>$group["groupname"]
                    ]
                ]);
            
            Db::commit();
           
            // 推送消息
            SingletonServiceFactory::getChatService()
                ->sendToGroup($userId, $groupId, $helloMsg);
           
        } catch (OperationFailureException $e) {
            Db::rollback();
            throw $e;
        } catch(\Exception $e) {
            Db::rollback();
            im_log("error", "加入群聊失败.", $e);
            throw new OperationFailureException();
        }
    }
    
    public function joinGroupAsk($sender, $groupId, $content, $ip = null) {
        try {
            // 判断是否已是群成员.
            if (ModelFactory::getGroupModel()->inGroup($sender, $groupId)) {
                throw new OperationFailureException("您已经是群聊成员了~");
            }
            
            // 查询群组管理者 id.
            $receiver = ModelFactory::getGroupModel()->getGroupAdminIds($groupId);
            
            if (!count($receiver)) {
                im_log("error", "加入不存在的群聊 ! 群聊 id: $groupId, 查询结果: ", $receiver);
                throw new OperationFailureException("群聊不存在~");
            }
            
            // 构成数据
            $data = [
                "sender_id"=>$sender,
                "send_date"=>time(),
                "corr_id"=>$groupId,
                "content"=>$content,
                "type"=>IMessageModel::TYPE_GROUP_ASK
            ];
            if (!is_null($ip)) $data["send_ip"] = $ip;
            
            // 插入数据
            ModelFactory::getMessageModel()->createMessage($data, $receiver);
            // 推送消息
            foreach($receiver as $id) {
                if (Gateway::isUidOnline($id)) {
//                     $this->pushMsgBoxNotification($id);
                    SingletonServiceFactory::getPushService()->pushMsgBoxNotification($id);
                }
            }
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "消息插入失败 !", $e);
            throw new OperationFailureException("消息发送失败 !");
        }
    }
    
    /**
     * 更新好友信息
     * @param mixed $userId 用户
     * @param array $friend 好友的属性 { id, group, alias }
     * @return array 更新后的信息
     */
    public function updateFriend($userId, $friend) {
        
        if (!isset($friend["id"])) {
            throw new OperationFailureException("未确定好友.");
        }
        $data = ["contact_id"=>$friend["id"]];
        if (isset($friend["alias"])) {
            $data["contact_alias"] = $friend["alias"];
        }
        // 判断存在分组参数, 且用户拥有此分组
        if (isset($friend["group"]) && model("friend_groups")->getFriendGroup($userId, $friend["group"])) {
            $data["group_id"] = $friend["group"];
        }
        if (count($data) == 0) {
            return [];
        }
        if (model("friends")->updateFriend($userId, $data)) {
            return [
                "id"=>$data["contact_id"],
                "alias"=>$data["contact_alias"]
            ];
        } else {
            return null;
        }
    }
    
    public function updateGroupMember($uid, $gid, $data)  {
        try {
            foreach ([$uid, $gid, $data["id"]] as $m) {
                if (!is_numeric($m) || $m < 0) {
                    throw new OperationFailureException("参数有误!");
                }
            }
            // 检测群聊是否存在
            $group = model("group")->getGroupById($gid);
            if (!is_array($group) || count($group) == 0) {
                im_log("error", "群组不存在. gid: ", $gid);
                throw new OperationFailureException("群组不存在");
            }
            $group = $group[0];
            // 检测要更新的成员是否存在
            $member = model("group")->getGroupMemberById($gid, $uid, $data["id"]);
            if (!is_array($member) || count($member) != 2) {
                im_log("error", "成员 $member[0] 或 $uid 不存在 $gid 中.");
                throw new OperationFailureException("群聊中无此成员");
            }
            $me = $member[0];
            $member = $member[1];
            // 存放更新数据
            $update = [];
            // 检测权限
            if (isset($data["admin"])) {
                // 设置管理员必须是群主才能设置
                if ($me["id"] != $group["admin"]) {
                    throw new OperationFailureException("权限不足");
                }
                // 群主不能设置自己为管理员
                if ($me["id"] == $group["admin"] 
                    && $me["id"] == $member["id"]) {
                    throw new OperationFailureException(static::failureMsg);
                }
                
                if ($data["admin"] == true && $data["admin"] != "false") {
                    $update["is_admin"] = 1;
                } else {
                    $update["is_admin"] = 0;
                }
                if ($update["is_admin"] == $member["isadmin"]) unset($update["is_admin"]);
            }
            if (isset($data["alias"])) {
                // 修改别人的别名
                if ($me["id"] != $member["id"]) {
                    if ($me["id"] != $group["admin"]) {
                        // 自己不是管理员
                        // 自己的等级没有高于对方
                        if (!$me["isadmin"] 
                            || $member["isadmin"]) {
                            throw new OperationFailureException("权限不足");
                        }
                    }
                }
                
                $update["user_alias"] = $data["alias"];
            }
            
            im_log("debug", "修改群聊 $gid 中成员信息. ", $member, ", 修改属性: ", $update);
            if (count($update) == 0) {
                return $member;
            }
            
            // 开始更新
            model("group")->updateGroupMember($gid, $data["id"], $update);
//             if (!$result) {
//                 im_log("debug", "群聊成员信息修改失败. result: ", $result, ".\n gid: $gid. \n member", $member);
//                 throw new OperationFailureException(static::failureMsg);
//             }
            if (isset($update["is_admin"])) $update["is_admin"] != 1? false:true;
            $member = array_merge($member, $update);
            array_key_replace_force($member, [
                "is_admin"=>"isadmin",
                "user_alias"=>"username"
            ]);
            return $member;
        } catch(OperationFailureException $e) {
            throw $e;
        } catch(\Exception $e) {
            im_log("error", "更新群聊成员信息失败. user: $uid, group: $gid, member: ", $member, ", ", $e);
            throw new OperationFailureException(static::failureMsg);
        }
    }
}