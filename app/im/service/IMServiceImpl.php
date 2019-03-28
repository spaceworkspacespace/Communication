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

class IMServiceImpl implements IIMService
{
    public const USER_FIELD = "id, avatar, user_nickname as username, signature as sign";

    public function init($userId)
    {
        return [
            "mine" => $this->getUserById($userId),
            // 查找出需要的数据, 除去多余的字段并改变字段名为需要的字段名.
            "friend" => array_map(function ($item) {
                $group = array_index_pick($item, "id", "group_name", "list");
                array_key_replace($group, [
                    "group_name" => "groupname"
                ]);
                array_map(function ($item) {
                    $friends = array_index_pick($item, "user_nickname", "avatar", "sign", "id");
                    array_key_replace($friends, "user_nickname", "username");
                    return $friends;
                }, $group["list"]);
                return $group;
            }, $this->findOwnFriends($userId)),
            "group" =>$this->findOwnGroups($userId)
        ];
    }

    public function findOwnFriends($userId): array
    {
        return model("user_entire")::contacts($userId);
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
                ->select()
                ->toArray();
        } catch(\Exception $e) {
            im_log("error", "查询用户失败, 用户的 id: $userId; error: ", $e);
            throw new OperationFailureException("查询失败, 请稍后重试~");
        }
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
            im_log("debug", $res);
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

    public function getOwnFriendGroups($userId): array
    {
        $groups = model("user_entire")::get($userId)->friendGroups->toArray();
        
        return array_map(function($item) {
            return array_index_pick($item, "id", "group_name");
        }, $groups);
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
        try {
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



}