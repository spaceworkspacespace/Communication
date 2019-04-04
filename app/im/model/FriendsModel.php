<?php
namespace app\im\model;


use app\im\exception\OperationFailureException;

class FriendsModel extends IMModel {
    
    /**
     * 获取用户的所有好友
     * @param mixed $userId
     * @return \think\Collection
     */
    public function getFriends($userId): \think\Collection {
        return $this->getQuery()
            ->where("user_id=:uid")
            ->bind(["uid"=>[$userId, \PDO::PARAM_INT]])
            ->select();
    }
    
    /**
     * 获取用户的好友分组以及好友信息
     * @param mixed $userId
     * @return \think\Collection
     */
    public function getFriendAndGroup($userId): array {
        $friends = [];
        $resultSet = $this->getQuery()
            ->alias("f")
            ->field("u.id AS id, u.user_nickname AS username, u.avatar, u.signature AS sign, g.group_name AS groupname, g.id AS groupid")
            ->join(["im_friend_groups"=>"g"], "f.group_id=g.id", "RIGHT OUTER")
            ->join(["cmf_user"=>"u"], "f.contact_id=u.id", "LEFT OUTER")
            ->where("g.user_id=:id")
            ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
            ->order("g.priority", "asc")
            ->select()
            ->toArray();
        foreach($resultSet as $res) {
            // 加入分组信息
            if (!isset($friends[$res["groupid"]])) {
                $friends[$res["groupid"]] = [
                    "id"=>$res["groupid"],
                    "groupname"=>$res["groupname"],
                    "list"=>[]
                ];
            }
            // 加入好友信息
            if (isset($res["id"]) && !is_null($res["id"])) {
                array_push($friends[$res["groupid"]]["list"], $res);
            }
        }
        return array_values($friends);
    }
    
    /**
     * 获取和联系人聊天的最后读取消息
     * @param mixed $userId
     * @param mixed $contactId
     * @exception \app\im\exception\OperationFailureException
     */
    public function getLastRead($userId, $contactId) {
        $resultSet = $this->getQuery()
            ->where("user_id=:uid AND contact_id=:cid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "cid"=>[$contactId, \PDO::PARAM_INT],
            ])
            ->select();
//         var_dump($resultSet->toArray());
        if (!$resultSet->count()) {
            im_log("error", "无法读取消息记录, 用户 $userId 与 $contactId 非好友.");
            throw new OperationFailureException("无法读取非好友的聊天记录.");
        }
        return $resultSet[0]["last_reads"];
    }
    
    /**
     * 判断两个用户是否为好友
     * @param mixed $userId
     * @param mixed $userId2
     */
    public function isFriend($userId, $userId2): bool {
        $count = $this->getQuery()
            ->where("user_id=:id3 AND contact_id=:id4")
            ->whereOr("user_id=:id1 AND contact_id=:id2")
            ->bind([
                "id1"=>[$userId, \PDO::PARAM_INT],
                "id2"=>[$userId2, \PDO::PARAM_INT],
                "id3"=>[$userId2, \PDO::PARAM_INT],
                "id4"=>[$userId, \PDO::PARAM_INT],
            ])
            ->count("user_id");
        return $count;
    }
    
    public function setLastRead($userId, $contactId, $msgId) {
        $this->getQuery()
            ->where("user_id=:uid AND contact_id=:fid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "fid"=>[$contactId, \PDO::PARAM_INT],
            ])
            ->update(["last_reads"=>$msgId]);
    }
}