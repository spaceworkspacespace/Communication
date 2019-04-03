<?php

namespace app\im\model;

use think\Config;
use think\Db;
use think\Model;


class ChatGroupModel extends IMModel {
    protected $pk = "id"; 
    
    /**
     * 通过用户 id 和消息 id 获取参数消息 id 中未读的消息信息.
     * @param mixed $userId
     * @param mixed $msgIds
     * @return array
     */
    public function getUnreadMessageByMsgId($userId, $msgIds) {
        return $this->getQuery()
            ->alias("chat")
            ->field("chat.id AS chat_id, chat.group_id, chat.sender_id, chat.send_date, content, g.user_id, g.last_reads")
            ->join(["im_groups"=>"g"], "chat.group_id=g.contact_id")
            ->whereIn("chat.id", $msgIds)
            ->where("g.user_id=:uid AND g.last_reads < chat.id")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT]
            ])
            ->select()
            ->toArray();
    }
    
    /**
     * 获取在某个消息 id 之后的消息信息
     * @param mixed $userId
     * @param mixed $groupId
     * @param mixed $msgId
     */
    public function getMessageAfterMsgId($userId, $groupId, $msgId) {
        return $this->getQuery()
            ->alias("chat")
            ->field("chat.id AS chat_id, chat.group_id, chat.sender_id, chat.send_date, content, g.user_id, g.last_reads")
            ->join(["im_groups"=>"g"], "chat.group_id=g.contact_id")
            ->where("g.user_id=:uid AND chat.group_id = :gid AND chat.id > :cid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "gid"=>[$groupId, \PDO::PARAM_INT],
                "cid"=>[$msgId, \PDO::PARAM_INT],
            ])
            ->order("chat.id", "ASC")
            ->select()
            ->toArray();
    }
    
    /**
     * 获取 [$min, $max] 范围内的消息.
     * @param mixed $userId
     * @param mixed $groupId
     * @param mixed $minId
     * @param mixed $maxId
     */
    public function getMessageByIdRange($userId, $groupId, $minId, $maxId) {
        return $this->getQuery()
            ->alias("chat")
            ->field("chat.id AS chat_id, chat.group_id, chat.sender_id, chat.send_date, content, g.user_id, g.last_reads")
            ->join(["im_groups"=>"g"], "chat.group_id=g.contact_id")
            ->where("g.user_id=:uid AND chat.group_id = :gid AND chat.id >= :min AND  chat.id <= :max")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "gid"=>[$groupId, \PDO::PARAM_INT],
                "min"=>[$minId, \PDO::PARAM_INT],
                "max"=>[$maxId, \PDO::PARAM_INT],
            ])
            ->order("chat.id", "ASC")
            ->select()
            ->toArray();
    }
    
    public function getOldestUnreadMessage($userId, $groupId) {
        $m = $this->getQuery()
            ->alias("chat")
            ->field("chat.id AS chat_id, chat.group_id, chat.sender_id, chat.send_date, content, g.user_id, g.last_reads")
            ->join(["im_groups"=>"g"], "chat.group_id=g.contact_id")
            ->where("g.user_id=:uid AND chat.group_id=:gid AND g.last_reads < chat.id")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "gid"=>[$groupId, \PDO::PARAM_INT],
            ])
            ->order("chat.id", "ASC")
            ->limit(0, 1)
            ->select()
            ->toArray();
        if (count($m))
            return $m[0];
        return false;
    }
}