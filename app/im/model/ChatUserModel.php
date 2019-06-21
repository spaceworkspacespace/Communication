<?php

namespace app\im\model;

use think\Db;

class ChatUserModel extends IMModel implements IChatFriendModel {
    public function getMaxIdByUser($userId1, $userId2) {
        return Db::table("im_chat_user")
            ->max("id");
    }
    
    /**
     * 获取用户在指定 chat.id 范围内的聊天记录
     * @param mixed $userId
     * @param mixed $friendId
     * @param mixed $minId
     * @param mixed $maxId
     * @return boolean|array
     */
    public function getMessageByIdRange($userId, $friendId, $minId, $maxId) {
        $args = func_get_args();
        foreach($args as $arg) {
            if (!is_numeric($arg)) return false;
        }
        $sql = <<<SQL
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.sender_id = f.user_id AND chat.receiver_id=contact_id
	WHERE chat.sender_id=$userId AND chat.receiver_id=$friendId AND chat.id>=$minId AND chat.id<=$maxId)
UNION
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.receiver_id = f.user_id AND chat.sender_id=contact_id
	WHERE receiver_id=$userId AND chat.sender_id=$friendId AND chat.id>=$minId AND chat.id<=$maxId)
ORDER BY chat_id ASC
;
SQL;
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        return $statement->fetchAll();
    }
    
    /**
     * 获取用户聊天信息
     * @param mixed $userId 用户
     * @param mixed $userId2 用户
     * @param number $pageCount 页码
     * @param number $pageSize 页大小
     */
    public function getMessageByUser($userId, $userId2, $pageCount=1, $pageSize=150) {
        foreach ([$userId, $userId2, $pageCount, $pageSize] as $item) {
            if (!is_numeric($item)) return null;
        }
        // 获取最后的已读消息的 id
        $friends = ModelFactory::getFriendModel()
            ->getOriginFriendByUser($userId, $userId2, "last_reads, last_visible");
        if (count($friends) != 2) {
            return null;
        }
        $lastRead = $friends["last_reads"];
        $lastVisible = $friends["last_visible"];
        $offset = ($pageCount - 1) * $pageSize;
        $sql = <<<SQL
SELECT 
    `chat`.`id` AS `cid`,
    `send_date` AS `date`,
    CASE
        WHEN `chat`.`id` <= $lastRead THEN 1
        ELSE 0
    END AS `isread`,
    `chat`.`issystem`,
    `content`,
    `user`.`id` AS `uid`,
    `user`.`avatar`,
    CASE
        WHEN
            `chat`.`sender_id` != $userId
                AND `fs`.`contact_alias` IS NOT NULL
                AND `fs`.`contact_alias` != ''
        THEN
            `fs`.`contact_alias`
        WHEN
            `user`.`user_nickname` IS NOT NULL
                AND `user`.`user_nickname` != ''
        THEN
            `user`.`user_nickname`
        ELSE `user`.`user_login`
    END AS `username`,
    `to_user`.`id` AS `tid`,
    `to_user`.`avatar` AS `tavatar`,
    CASE
        WHEN
            `chat`.`receiver_id` != $userId
                AND `fs`.`contact_alias` IS NOT NULL
                AND `fs`.`contact_alias` != ''
        THEN
            `fs`.`contact_alias`
        WHEN
            `to_user`.`user_nickname` IS NOT NULL
                AND `to_user`.`user_nickname` != ''
        THEN
            `to_user`.`user_nickname`
        ELSE `to_user`.`user_login`
    END AS `tusername`
FROM
    im_chat_user chat
        LEFT JOIN
    `cmf_user` `user` ON chat.sender_id = user.id
        LEFT JOIN
    `cmf_user` `to_user` ON `chat`.`receiver_id` = `to_user`.`id`
        LEFT JOIN
    `im_friends` `fs` ON `fs`.`user_id` = $userId
        AND `fs`.`contact_id` = $userId2
WHERE
    `chat`.`id` >= $lastVisible
        AND (chat.sender_id = $userId
        AND chat.receiver_id = $userId2
        AND chat.visible_sender = 1
        OR chat.sender_id = $userId2
        AND chat.receiver_id = $userId
        AND chat.visible_receiver = 1)
ORDER BY `chat`.`send_date` DESC
LIMIT $offset , $pageSize
;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getOldestUnreadMessage($userId, $friendId) {
        if (!is_numeric($userId) || !is_numeric($friendId)) {
            return false;
        }
        $sql = <<<SQL
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.sender_id = f.user_id AND chat.receiver_id=contact_id
	WHERE chat.sender_id=$userId AND chat.receiver_id=$friendId AND chat.id > f.last_reads)
UNION
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.receiver_id = f.user_id AND chat.sender_id=contact_id
	WHERE receiver_id=$userId AND chat.sender_id=$friendId AND chat.id > f.last_reads)
ORDER BY chat_id ASC
LIMIT 0,1
;
SQL;
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        $m = $statement->fetchAll();
        if (count($m))
            return $m[0];
        return false;
    }
    
    public function getUnreadMessageByMsgId($userId, $msgIds) {
        if (!is_numeric($userId)) return false;
        foreach($msgIds as $msgId) {
            if (!is_numeric($msgId)) return false;
        }
        $cids = implode(", ", $msgIds);
        $sql = <<<SQL
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.sender_id = f.user_id AND chat.receiver_id=contact_id
	WHERE sender_id=$userId AND chat.id IN ($cids) AND chat.id > f.last_reads)
UNION
(SELECT chat.id AS chat_id, chat.receiver_id, chat.sender_id, chat.send_date, content, f.user_id, f.last_reads
	FROM im_chat_user chat
		JOIN im_friends f ON chat.receiver_id = f.user_id AND chat.sender_id=contact_id
	WHERE receiver_id=$userId AND chat.id IN ($cids) AND chat.id > f.last_reads)
ORDER BY chat_id ASC
;
SQL;
        im_log("SQL", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
//         $statement->bindParam(1, $userId, \PDO::PARAM_INT);
//         $statement->bindParam(2, $msgIds, \PDO::PARAM_INT);
//         $statement->execute([$userId+0, $msgIds, $userId+0, $msgIds]);
        return $statement->fetchAll();
    }
    
    /**
     * 获取用户未读聊天信息
     * @param mixed $userId 用户
     * @param mixed $userId2 用户
     * @param number $pageNo 页码
     * @param number $pageSize 页大小
     */
    public function getUnreadMessageByUser($userId, $userId2, $pageNo=1, $pageSize=150) {
        foreach ([$userId, $userId2, $pageNo, $pageSize] as $item) {
            if (!is_numeric($item)) return null;
        }
        // 获取最后的已读消息的 id
        $friends = ModelFactory::getFriendModel()
            ->getOriginFriendByUser($userId, $userId2, "last_reads, last_visible");
        im_log("debug", "IFriendModel->getOriginFriendByUser: ", $friends);
        if (count($friends) != 2) {
            return null;
        }
        $lastRead = $friends["last_reads"];
        $lastVisible = $friends["last_visible"];
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
    CASE
        WHEN
            `chat`.`sender_id` != $userId
                AND `fs`.`contact_alias` IS NOT NULL
                AND `fs`.`contact_alias` != ''
        THEN
            `fs`.`contact_alias`
        WHEN
            `user`.`user_nickname` IS NOT NULL
                AND `user`.`user_nickname` != ''
        THEN
            `user`.`user_nickname`
        ELSE `user`.`user_login`
    END AS `username`,
    `to_user`.`id` AS `tid`,
    `to_user`.`avatar` AS `tavatar`,
    CASE
        WHEN
            `chat`.`receiver_id` != $userId
                AND `fs`.`contact_alias` IS NOT NULL
                AND `fs`.`contact_alias` != ''
        THEN
            `fs`.`contact_alias`
        WHEN
            `to_user`.`user_nickname` IS NOT NULL
                AND `to_user`.`user_nickname` != ''
        THEN
            `to_user`.`user_nickname`
        ELSE `to_user`.`user_login`
    END AS `tusername`
FROM
    im_chat_user chat
        LEFT JOIN
    `cmf_user` `user` ON chat.sender_id = user.id
        LEFT JOIN
    `cmf_user` `to_user` ON `chat`.`receiver_id` = `to_user`.`id`
        LEFT JOIN
    `im_friends` `fs` ON `fs`.`user_id` = $userId
        AND `fs`.`contact_id` = $userId2
WHERE
    `chat`.`id` > $lastRead
        AND `chat`.`id` >= $lastVisible
        AND (chat.sender_id = $userId
        AND chat.receiver_id = $userId2
        AND chat.visible_sender = 1
        OR chat.sender_id = $userId2
        AND chat.receiver_id = $userId
        AND chat.visible_receiver = 1)
ORDER BY `chat`.`send_date` DESC
LIMIT $offset , $pageSize
;
SQL;
        
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function insertMessage($fromId, $toId, $content, $ip=null) {
        $query = $this->getQuery();
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
        
        $query->startTrans();
        // 插入聊天记录表
        $msgId = $query->table("im_chat_user")
            ->insert($chatData, false, true);
        if (!$msgId) {
            $query->rollback();
            return null;        
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
        $chatData["id"] = $msgId;
        return $chatData;
    }
    
    public function setVisible($userId, $cid, $visible) {
        $args = func_get_args();
        foreach($args as $arg) {
            if (!is_numeric($arg)) return false;
        }
        if ($visible !== 0 && $visible !== 1) return false;
        
        $affectedCount = 0;
        
        $sql = <<<SQL
UPDATE `im_chat_user` SET `visible_sender`=$visible WHERE `sender_id`=$userId AND `id`=$cid;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        if (!$statement) return false;
        $affectedCount += $statement->rowCount();
        
        $sql = <<<SQL
UPDATE `im_chat_user` SET `visible_receiver`=$visible WHERE `receiver_id`=$userId AND `id`=$cid;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        if (!$statement) return false;
        $affectedCount += $statement->rowCount();
        
        return $affectedCount;
    }
    
    public function addInfo($userId1, $userId2, $content) {
        Db::table('im_chat_user')
        ->insert([
            'sender_id'=>$userId1,
            'send_date'=>time(),
            'receiver_id'=>$userId2,
            'content'=>$content,
            'visible_receiver'=>0
        ]);
    }
}