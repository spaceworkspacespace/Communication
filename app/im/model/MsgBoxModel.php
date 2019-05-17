<?php

namespace app\im\model;
use think\Db;
use think\Model;
use app\im\exception\OperationFailureException;

class MsgBoxModel extends Model implements IMessageModel {
    protected $connection = [
        'prefix' => 'im_'
    ];
    
    public function createMessage($data, $receivers) {
        $id = $this->getQuery()
        ->insert($data, false, true);
        // 返回的 id 无效
        if (!is_numeric($id) || $id < 0) {
            return false;
        }
        $data["id"] = $id;
        // 插入接收者消息
        $receiveData = [];
        foreach ($receivers as $id) {
            array_push($receiveData, [
                "id"=> $data["id"],
                "receiver_id"=>$id,
                "send_date"=>$data["send_date"],
            ]);
        }
        
        Db::table("im_msg_receive")
            ->insertAll($receiveData);
        return $data;
    }
    
    public function fillMessageAssociated($messages) {
        // 根据 type 将 id 分类, 分别查询
        $tidy = [];
        foreach($messages as $m) {
            if (!isset($tidy[$m["type"]])) {
                $tidy[$m["type"]] = [];
            }
            array_push($tidy[$m["type"]], $m["id"]);
        }
        // 查询的结果
        $result = [];
        // 遍历分类好的数据进行查询
        foreach($tidy as $type=>$id) {
            $result = array_merge($result, $this->fillMessageAssociatedByType($id, $type));
        }
        
        usort($result, function($l, $r) {
            return $l["date"] > $r["date"]? -1:1;
        });
        return $result;
    }
    
    /**
     * 通过 id 和 type 查询完整的信息
     * @param array $ids 消息的 id
     * @param mixed $type
     * @return array 消息数据数组
     */
    private function fillMessageAssociatedByType($ids, $type) {
        $idStr = implode(",", $ids);
        $sqlParts = $this->getSelectSqlPartByType($type);
        $sql = <<<SQL
SELECT
    `m`.`id`,
    `r`.`send_date` AS `date`,
    `m`.`content`,
    `m`.`type`,
    CASE
        WHEN `m`.`sender_id` = 0 THEN 1
        ELSE 0
    END AS `issystem`,
    `m`.`result`,
    `r`.`treat`,
    `u`.`id` AS `a0id`,
    CASE
        WHEN
            `u`.`user_nickname` IS NOT NULL
                AND `u`.`user_nickname` != ''
        THEN
            `u`.`user_nickname`
        ELSE `u`.`user_login`
    END AS `a0username`,
    `u`.`avatar` AS `a0avatar`
        {$sqlParts["field"]}
FROM
    `im_msg_box` `m`
        INNER JOIN
    `im_msg_receive` `r` ON `m`.`id` = `r`.`id`
        LEFT JOIN
    `cmf_user` `u` ON `m`.`sender_id` = `u`.`id`
        {$sqlParts["join"]}
WHERE
    `r`.`id` IN ($idStr)
        AND `m`.`type` IN ({$sqlParts["type"]})
;
SQL;
        
        im_log("sql", $sql);
        $result = Db::query($sql);
        foreach($result as &$data) {
            // 整理结果格式
            $data["associated"] = [];
            // 发送者的信息总是存在, 即使为 null, 也要占着位置
            array_push($data["associated"] , [
                "id"=>$data["a0id"],
                "username"=>$data["a0username"],
                "avatar"=>$data["a0avatar"]
            ]);
            unset(
                $data["a0id"],
                $data["a0username"],
                $data["a0avatar"]
            );
            switch ($type) {
                case IMessageModel::TYPE_FRIEND_ASK_REFUSE:
                case IMessageModel::TYPE_FRIEND_BE_REMOVED:
                    array_push($data["associated"] , [
                        "id"=>$data["a1id"],
                        "username"=>$data["a1username"],
                        "avatar"=>$data["a1avatar"]
                    ]);
                    unset(
                        $data["a1id"],
                        $data["a1username"],
                        $data["a1avatar"]);
                    break;
                case IMessageModel::TYPE_GROUPMEMBER_BE_REMOVED:
                case IMessageModel::TYPE_GROUP_ASK:
                case IMessageModel::TYPE_GROUP_ASK_REFUSE:
                    array_push($data["associated"] , [
                        "id"=>$data["a1id"],
                        "groupname"=>$data["a1groupname"],
                        "avatar"=>$data["a1avatar"]
                    ]);
                    unset(
                        $data["a1id"],
                        $data["a1groupname"],
                        $data["a1avatar"]);
                    break;
                case IMessageModel::TYPE_GROUP_INVITE:
                case IMessageModel::TYPE_GROUP_INVITE_REFUSE:
                    array_push($data["associated"] , [
                        "id"=>$data["a1id"],
                        "username"=>$data["a1username"],
                        "avatar"=>$data["a1avatar"]
                    ], [
                        "id"=>$data["a2id"],
                        "groupname"=>$data["a2groupname"],
                        "avatar"=>$data["a2avatar"]
                    ]);
                    unset(
                        $data["a1id"],
                        $data["a1username"],
                        $data["a1avatar"],
                        $data["a2id"],
                        $data["a2groupname"],
                        $data["a2avatar"]);
                    break;
                case IMessageModel::TYPE_GROUPMEMBER_REMOVE:
                    array_push($data["associated"] , [
                        "id"=>$data["a1id"],
                        "username"=>$data["a1username"],
                        "avatar"=>$data["a1avatar"]
                    ], [
                        "id"=>$data["a2id"],
                        "username"=>$data["a2username"],
                        "avatar"=>$data["a2avatar"]
                    ],[
                        "id"=>$data["a3id"],
                        "groupname"=>$data["a3groupname"],
                        "avatar"=>$data["a3avatar"]
                    ]);
                    im_log("debug", $data);
                    unset(
                        $data["a1id"],
                        $data["a1username"],
                        $data["a1avatar"],
                        $data["a2id"],
                        $data["a2username"],
                        $data["a2avatar"],
                        $data["a3id"],
                        $data["a3groupname"],
                        $data["a3avatar"]);
                    break;
            }
        }
        return $result;
    }
    
    public function getMessageById(...$mid) {
        $data = Db::table("im_msg_box")
            ->field("id,type")
            ->whereIn("id", $mid)
            ->select()
            ->toArray();
        if (!is_array($data)) {
            return false;
        }
        if (count($data) < 1) {
            return $data;
        }
        // 查询完整数据
        $data = $this->fillMessageAssociatedByType(array_column($data, "id"), $data[0]["type"]);
        // 排序, 保证与输入的 id 顺序一致
        $result = [];
        for ($i=count($mid)-1; $i>=0; $i--) {
            $id = $mid[$i];
            foreach($data as $m) {
                if ($m["id"] == $id) {
                    $result[$i] = $m;
                }
            }
        }
        return $result;
    }
    
    public function getMessageByUser($userId, $pageNo=1, $pageSize=150) {
        
    }
    
    public function getMessageIdAndTypeByUser($userId, $pageNo=1, $pageSize=150) {
        $offset = ($pageNo - 1) * $pageSize;
        $sql = <<<SQL
SELECT
    `m`.`id`,
    `m`.`type`
FROM
    `im_msg_box` `m`
        INNER JOIN
    `im_msg_receive` `r` ON `m`.`id` = `r`.`id`
WHERE
    `r`.`receiver_id` = $userId
ORDER BY `r`.`send_date` DESC
LIMIT $offset , $pageSize
;
SQL;
        return $this->getQuery()->query($sql);
    }
    
    public function getMessageByUserWithType($userId, $type, $pageNo=1, $pageSize=150) {
        
    }
    
    public function getOriginMessageById(...$mid)  {
        $data = Db::table("im_msg_box")
            ->whereIn("id", $mid)
            ->select()
            ->toArray();
//         im_log("debug", $data);
        if (!is_array($data)) {
            return false;
        }
        if (count($data) < 1) {
            return $data;
        }
        // 排序, 保证与输入的 id 顺序一致
        $result = [];
        for ($i=count($mid)-1; $i>=0; $i--) {
            $id = $mid[$i];
            foreach($data as $m) {
                if ($m["id"] == $id) {
                    $result[$i] = $m;
                }
            }
        }
        return $result;
    }
    
    public function getUnreadMsgCountByUser($userId) {
        $sql = <<<SQL
SELECT COUNT(`r`.`id`) FROM `im_msg_receive` `r` INNER JOIN `im_msg_box` `m` ON `r`.`id` = `m`.`id` WHERE `r`.`receiver_id`=$userId AND `r`.`read`=0;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        return $result[0];
    }
    
    /**
     * 获取部分 SELECT 语句有差异的 SQL
     * @param int $type
     * @param array { field,  join, type } 选择字段、连表处、type 限制的差异 SQL
     */
    private function getSelectSqlPartByType($type) {
        $result = [
            "field"=>"",
            "join"=>"",
            "type"=>"0"
        ];
        switch($type) {
            case IMessageModel::TYPE_FRIEND_ASK:
            case IMessageModel::TYPE_GENERAL:
                $result["type"] = implode(",", [
                    IMessageModel::TYPE_FRIEND_ASK, 
                    IMessageModel::TYPE_GENERAL]);
                break;
            case IMessageModel::TYPE_FRIEND_ASK_REFUSE:
            case IMessageModel::TYPE_FRIEND_BE_REMOVED:
                $result["type"] = implode(",", [
                IMessageModel::TYPE_FRIEND_ASK_REFUSE,
                IMessageModel::TYPE_FRIEND_BE_REMOVED]);
                $result["field"] = <<<SQL
,
    `iu`.`id` AS `a1id`,
    CASE
        WHEN
            `iu`.`user_nickname` IS NOT NULL
                AND `iu`.`user_nickname` != ''
        THEN
            `iu`.`user_nickname`
        ELSE `iu`.`user_login`
    END AS `a1username`,
    `iu`.`avatar` AS `a1avatar`
SQL;
                $result["join"] = <<<SQL
        INNER JOIN
    `cmf_user` `iu` ON `m`.`corr_id` = `iu`.`id`
SQL;
                break;
            case IMessageModel::TYPE_GROUP_ASK:
            case IMessageModel::TYPE_GROUPMEMBER_BE_REMOVED:
            case IMessageModel::TYPE_GROUP_ASK_REFUSE:
                $result["type"] = implode(",", [
                    IMessageModel::TYPE_GROUP_ASK,
                    IMessageModel::TYPE_GROUPMEMBER_BE_REMOVED,
                    IMessageModel::TYPE_GROUP_ASK_REFUSE]);
                $result["field"] = <<<SQL
,
    `g`.`id` AS `a1id`,
    `g`.`avatar` AS `a1avatar`,
    `g`.`groupname` AS `a1groupname`
SQL;
                $result["join"] = <<<SQL
        INNER JOIN
    `im_group` `g` ON `m`.`corr_id` = `g`.`id`
SQL;
                break;
            case IMessageModel::TYPE_GROUP_INVITE:
            case IMessageModel::TYPE_GROUP_ASK_REFUSE:
                $result["type"] = implode(",", [
                    IMessageModel::TYPE_GROUP_INVITE,
                    IMessageModel::TYPE_GROUP_ASK_REFUSE]);
                $result["field"] = <<<SQL
,
    `iu`.`id` AS `a1id`,
    CASE
        WHEN
            `iu`.`user_nickname` IS NOT NULL
                AND `iu`.`user_nickname` != ''
        THEN
            `iu`.`user_nickname`
        ELSE `iu`.`user_login`
    END AS `a1username`,
    `iu`.`avatar` AS `a1avatar`,
    `g`.`id` AS `a2id`,
    `g`.`avatar` AS `a2avatar`,
    `g`.`groupname` AS `a2groupname`
SQL;
                $result["join"] = <<<SQL
        INNER JOIN
    `im_group` `g` ON `m`.`corr_id2` = `g`.`id`
        INNER JOIN
    `cmf_user` `iu` ON `m`.`corr_id` = `iu`.`id`
SQL;
                break;
            case IMessageModel::TYPE_GROUPMEMBER_REMOVE:
                $result["type"] = IMessageModel::TYPE_GROUPMEMBER_REMOVE;
                $result["field"] = <<<SQL
,
    `ou`.`id` AS `a1id`,
    CASE
        WHEN
            `ou`.`user_nickname` IS NOT NULL
                AND `ou`.`user_nickname` != ''
        THEN
            `ou`.`user_nickname`
        ELSE `ou`.`user_login`
    END AS `a1username`,
    `ou`.`avatar` AS `a1avatar`,
    `ru`.`id` AS `a2id`,
    CASE
        WHEN
            `ru`.`user_nickname` IS NOT NULL
                AND `ru`.`user_nickname` != ''
        THEN
            `ru`.`user_nickname`
        ELSE `ru`.`user_login`
    END AS `a2username`,
    `ru`.`avatar` AS `a2avatar`,
    `g`.`id` AS `a3id`,
    `g`.`avatar` AS `a3avatar`,
    `g`.`groupname` AS `a3groupname`
SQL;
                $result["join"] = <<<SQL
        INNER JOIN
    `im_group` `g` ON `m`.`corr_id3` = `g`.`id`
        INNER JOIN
    `cmf_user` `ou` ON `m`.`corr_id` = `ou`.`id`
        INNER JOIN
    `cmf_user` `ru` ON `m`.`corr_id2` = `ru`.`id`
SQL;
                break;
        }
        return $result;
    }
    
    public function hasMessage($userId, $msgId) {
        $count = Db::table("im_msg_receive")
            ->where("receiver_id=:uid AND id=:mid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "mid"=>[$msgId, \PDO::PARAM_INT]
            ])
            ->count("id");
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function updateMessageById($userId, $mid, $data) {
        $mData = array_index_pick($data, "result");
        $effect = 0;
        if (count($mData) > 0) {
            $effect += Db::table("im_msg_box")
                ->where("id=:mid")
                ->bind([
                    "mid"=>[$mid, \PDO::PARAM_INT]
                ])
                ->update($mData);
        }
        $rData = array_index_pick($data, "treat", "read", "visible");
        if (count($rData) > 0) {
            $effect += Db::table("im_msg_receive")
                ->where("id=:mid AND receiver_id=:uid")
                ->bind([
                    "mid"=>[$mid, \PDO::PARAM_INT],
                    "uid"=>[$userId, \PDO::PARAM_INT]
                ])
                ->update($rData);
        }
        return $effect;
    }
    
    public function updateMessageReadByUser($userId) {
        return $this->getQuery()
            ->table("im_msg_receive")
            ->where("receiver_id=:uid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT]
            ])
            ->update(["read"=>1]);
    }
    
    //多对一关联
    public function user()
    {
        return $this->belongsTo('UserModel','sender_id');
    }
    
    public function deleteIndex($id)
    {
        Db::table('im_msg_receive')
        ->where(['id' => $id])
        ->update(['visible' => 0]);
    }
    public function postFeedBack($id)
    {
        Db::table('im_msg_receive')
        ->where(['id' => $id])
        ->update(['read' => 1]);
    }


}