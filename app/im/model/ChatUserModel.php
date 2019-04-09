<?php

namespace app\im\model;

use think\Db;

class ChatUserModel extends IMModel {
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
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
//         $statement->bindParam(1, $userId, \PDO::PARAM_INT);
//         $statement->bindParam(2, $msgIds, \PDO::PARAM_INT);
//         $statement->execute([$userId+0, $msgIds, $userId+0, $msgIds]);
        return $statement->fetchAll();
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
}