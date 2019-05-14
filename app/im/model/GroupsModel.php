<?php
namespace app\im\model;

use app\im\exception\OperationFailureException;

class GroupsModel extends IMModel {
    
    public function getGroups($userId) {
        return model("groups")->getQuery()
            ->where("user_id=:uid")
            ->bind(["uid"=>[$userId, \PDO::PARAM_INT]])
            ->select();
    }
    
    /**
     * 获取用户所有的群聊的 id
     * @param mixed $userId
     * @return Array<number> 群聊的 id 的数组
     */
    public function getGroupIds($userId): array {
        $result = model("groups")->getQuery()
            ->field("contact_id")
            ->where("user_id=:uid")
            ->bind(["uid"=>[$userId, \PDO::PARAM_INT]])
            ->select()
            ->toArray();
        return array_column($result, "contact_id");
    }
    
    public function getUserIdInGroup($giu) {
        $resultSet = $this->getQuery()
            ->field("user_id")
            ->where("contact_id=:gid")
            ->bind(["gid"=>[$giu, \PDO::PARAM_INT]])
            ->select()
            ->toArray();
        return array_column($resultSet, "user_id");
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
        if (!$resultSet->count()) {
            im_log("error", "无法读取消息记录, 用户 $userId 非 $contactId 成员.");
            throw new OperationFailureException("无法读取未加入群聊的聊天记录.");
        }
        return $resultSet[0]["last_reads"];
    }
    
    /**
     * 获取用户所有群聊的最后读取信息
     * @param mixed $userId
     * @return array {contact_id: last_reads, ...}
     */
    public function getAllLastRead($userId) :array {
        $resultSet = $this->getQuery()
            ->field("contact_id, last_reads")
            ->where("user_id=:uid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
            ])
            ->select();
        return array_map(function($item) {
            return [$item["contact_id"]=>$item["last_reads"]];
        }, $resultSet->toArray());
    }
    
    public function setLastRead($userId, $contactId, $msgId) {
        $this->getQuery()
            ->where("user_id=:uid AND contact_id=:gid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "gid"=>[$contactId, \PDO::PARAM_INT],
            ])
            ->update(["last_reads"=>$msgId]);
    }
}