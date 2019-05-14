<?php

namespace app\im\model;

use think\Config;
use think\Db;
use think\Model;


class ChatGroupModel extends IMModel implements  IChatGroupModel {
    protected $pk = "id"; 
    
    public function getMaxIdByGroup($gid) {
        return Db::table("im_chat_group")
            ->max("id");
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
    
    /**
     * 获取用户聊天信息
     * @param mixed $userId 用户
     * @param mixed $groupId 群聊
     * @param number $pageNo 页码
     * @param number $pageSize 页大小
     */
    public function getMessageByUser($userId, $groupId, $pageNo=1, $pageSize=150) {
        foreach ([$userId, $groupId, $pageNo, $pageSize] as $item) {
            if (!is_numeric($item)) return null;
        }
        
        // 获取最后的已读消息的 id
        $groups = ModelFactory::getGroupModel()
            ->getOriginGroupByUser($userId, $groupId, "last_reads,last_visible");
            if (count($groups) != 2) {
            return null;
        }
        $lastRead = $groups["last_reads"];
        $lastVisible = $groups["last_visible"];
        $offset = ($pageNo - 1) * $pageSize;
        
        $sql = <<<SQL
SELECT 
	`chat`.`id` AS `cid`,
    `send_date` AS `date`,
    CASE 
		WHEN `chat`.`id`<=$lastRead
			THEN 1
        ELSE 0
	END AS `isread`,
    `chat`.`issystem`,
    `content`,
	`user`.`id` AS `uid`,
    `user`.`avatar`,
    `user_nickname` AS `username`,
    `chat`.`group_id` AS `gid`,
    `g`.`groupname` AS `groupname`,
    `g`.`avatar` AS `gavatar`
FROM im_chat_group chat 
LEFT JOIN cmf_user user ON chat.sender_id=`user`.`id`
INNER JOIN `im_group` `g` ON `chat`.`group_id`=`g`.`id`
WHERE chat.group_id=$groupId AND `chat`.`id` >= $lastVisible
ORDER BY `chat`.`send_date` DESC LIMIT $offset,$pageSize 
;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
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
     * 通过用户获取其未读信息
     * @param mixed $userId
     * @param mixed $groupId
     * @param number $pageNo 页码
     * @param number $pageSize 页大小
     */
    public function getUnreadMessageByUser($userId, $groupId, $pageNo=1, $pageSize=150) {
        foreach ([$userId, $groupId, $pageNo, $pageSize] as $item) {
            if (!is_numeric($item)) return null;
        }
        
        // 获取最后的已读消息的 id
        $groups = ModelFactory::getGroupModel()
        ->getOriginGroupByUser($userId, $groupId, "last_reads,last_visible");
        if (count($groups) != 2) {
            return null;
        }
        $lastRead = $groups["last_reads"];
        $lastVisible = $groups["last_visible"];
        $offset = ($pageNo - 1) * $pageSize;
        
        $sql = <<<SQL
SELECT
	`chat`.`id` AS `cid`,
    `send_date` AS `date`,
    0 AS `isread`,
    `chat`.`issystem`,
    `content`,
	`user`.`id` AS `uid`,
    `user`.`avatar`,
    `user_nickname` AS `username`,
    `chat`.`group_id` AS `gid`,
    `g`.`groupname` AS `groupname`,
    `g`.`avatar` AS `gavatar`
FROM im_chat_group chat
LEFT JOIN cmf_user user ON chat.sender_id=`user`.`id`
INNER JOIN `im_group` `g` ON `chat`.`group_id`=`g`.`id`
WHERE chat.group_id=$groupId AND `chat`.`id` > $lastRead AND `chat`.`id` >= $lastVisible
ORDER BY `chat`.`send_date` DESC LIMIT $offset,$pageSize
;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}