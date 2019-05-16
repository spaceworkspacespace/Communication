<?php 
namespace app\im\model;

use think\Db;


class GroupModel extends IMModel implements IGroupModel {
    
    public function deleteGroupMemberById($gid, ...$uid) {
        $uidStr = implode(",", $uid);
        $sql = "DELETE FROM `im_groups` WHERE `contact_id`=$gid AND `user_id` IN ($uidStr);";
        im_log("SQL", $sql);
        
        return Db::execute($sql);
    }
    
    public function getGroupAdminIds($gid) {
        $resultSet = model("groups")
            ->getQuery()
            ->field("user_id")
            ->where("contact_id=:gid AND is_admin=1")
            ->bind([
                "gid"=>[$gid, \PDO::PARAM_INT],
            ])
            ->select();
        $result = $resultSet->toArray();
        return array_column($result, "user_id");
    }
    
    /**
     * 通过关键字查找群聊
     * @param mixed $keyword
     * @param boolean $include
     * @return array
     */
    public function getGroupByKeyword($keyword=null, $include = false): array {
        
    }
    
    public function getGroupById(...$groupId) {
        $groups = $this->getQuery()
            ->field("id,groupname,description,avatar,create_time AS createtime,admin_id AS admin, admin_count AS admincount,member_count AS membercount")
            ->whereIn("id", $groupId)
            ->select()
            ->toArray();
        
        if (count($groupId) == 1) return $groups;
        // 作一次排序, 让结果的顺序和参数 id 顺序一致.
        $result = [];
        for ($i=0; $i<count($groupId); $i++) {
            $id = $groupId[$i];
            foreach ($groups as $g) {
                if ($g["id"] == $id) {
                    $result[$i] = $g;
                    break;
                }
            }
        }
        return $result;
    }
    
    /**
     * 通过用户 id 查找用户的群聊信息
     * @param mixed $userId 用户的 id
     * @param boolean $include 是否包含群聊成员的信息
     * @return array
     */
    public function getGroupByUser($userId, $include = false): array {
        if (!is_numeric($userId) || $userId < 1) {
            return null;
        }
        $sql = "";
        if ($include) {
            $sql = <<<SQL
SELECT
    `g`.`id` AS `gid`,
    `g`.`groupname`,
    `g`.`description`,
    `g`.`avatar` AS `gavatar`,
    `g`.`create_time` AS `createtime`,
    `g`.`admin_id` AS `admin`,
    `g`.`admin_count` AS `admincount`,
    `g`.`member_count` AS `membercount`,
    `u`.`id` AS `uid`,
    CASE
        WHEN
            `gs`.`user_alias` IS NOT NULL
                AND `gs`.`user_alias` != ''
        THEN
            `gs`.`user_alias`
        WHEN
            `u`.`user_nickname` IS NOT NULL
                AND `u`.`user_nickname` != ''
        THEN
            `u`.`user_nickname`
        ELSE `u`.`user_login`
    END AS `username`,
    `u`.`avatar`,
    `u`.`signature` AS `sign`,
    CASE
        WHEN `u`.`sex` = 1 THEN '男'
        WHEN `u`.`sex` = 1 THEN '女'
        ELSE '保密'
    END AS `sex`,
    `gs`.`is_admin` AS `isadmin`
FROM
    `im_groups` `mygs`
        INNER JOIN
    `im_groups` `gs` ON `gs`.`contact_id` = `mygs`.`contact_id`
        INNER JOIN
    `im_group` `g` ON `gs`.`contact_id` = `g`.`id`
        LEFT JOIN
    `cmf_user` `u` ON `gs`.`user_id` = `u`.`id`
WHERE
    `mygs`.`user_id` = $userId
ORDER BY `g`.`id` ASC
;
SQL;
        } else {
            $sql = <<<SQL
SELECT
    `g`.`id` AS `gid`,
    `g`.`groupname`,
    `g`.`description`,
    `g`.`avatar` AS `gavatar`,
    `g`.`create_time` AS `createtime`,
    `g`.`admin_id` AS `admin`,
    `g`.`admin_count` AS `admincount`,
    `g`.`member_count` AS `membercount`
FROM
    `im_groups` `mygs`
        INNER JOIN
    `im_group` `g` ON `mygs`.`contact_id` = `g`.`id`
WHERE
    `mygs`.`user_id` = $userId
ORDER BY `g`.`id` ASC
;
SQL;
        }
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            im_log("debug", "查询结果非数组: ", $result, ", by: ", $sql);
            return [];
        }
        return $result;
    }
    
    /**
     * 通过群聊的 id 和用户的 id 获取群聊中用户的信息
     * @param mixed $gid
     * @param mixed ...$uid
     * @return array
     */
    public function getGroupMemberById($gid, ...$uid) {
        $uids = implode(",", $uid);
        $sql = <<<SQL
SELECT
    `u`.`id` AS `id`,
    CASE
        WHEN
            `gs`.`user_alias` IS NOT NULL
                AND `gs`.`user_alias` != ''
        THEN
            `gs`.`user_alias`
        WHEN
            `u`.`user_nickname` IS NOT NULL
                AND `u`.`user_nickname` != ''
        THEN
            `u`.`user_nickname`
        ELSE `u`.`user_login`
    END AS `username`,
    `u`.`avatar`,
    `u`.`signature` AS `sign`,
    CASE
        WHEN `u`.`sex` = 1 THEN '男'
        WHEN `u`.`sex` = 1 THEN '女'
        ELSE '保密'
    END AS `sex`,
    `gs`.`is_admin` AS `isadmin`
FROM
    `im_groups` `gs`
        LEFT JOIN
    `cmf_user` `u` ON `gs`.`user_id` = `u`.`id`
WHERE
    `gs`.`contact_id` = $gid
        AND `gs`.`user_id` IN ($uids)
;
SQL;
        im_log("sql", $sql);
        $statement = Db::connect(array_merge(config("database")))
            ->connect()
            ->query($sql);
        $resultSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
        
        if (!is_array($resultSet)) {
            return $resultSet;
        }
        
        if (count($resultSet) == 1) {
            return $resultSet;
        }
        
        $result = [];
        for ($i=0; $i<count($uid); $i++) {
            $id = $uid[$i];
            foreach ($resultSet as $g) {
                if ($g["id"] == $id) {
                    $result[$i] = $g;
                    break;
                }
            }
        }
        return $result;
    }
    
    public function getGroupMemberCount($groupId) {
        return Db::table("im_groups")
            ->where("contact_id=:gid")
            ->bind([
                "gid"=>[$groupId, \PDO::PARAM_INT],
            ])
            ->count("contact_id");
    }
    
    public function getOriginGroupByUser($userId, $groupId, $fields="*")  {
        $resultSet = Db::table("im_groups")
            ->field($fields)
            ->where("user_id=:uid AND contact_id=:gid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "gid"=>[$groupId, \PDO::PARAM_INT],
            ])
            ->select()
            ->toArray();
        if (!is_array($resultSet) 
            || count($resultSet) == 0) {
            return [];
        }
        return $resultSet[0];
    }
    
    public function inGroup($uid, $gid) {
        $count = Db::table("im_groups")
            ->where("user_id=:uid AND contact_id=:gid")
            ->bind([
                "gid"=>[$gid, \PDO::PARAM_INT],
                "uid"=>[$uid, \PDO::PARAM_INT],
            ])
            ->count("user_id");
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function setGroup($userId, $groupId) {
        $now = time();
        $maxId = ModelFactory::getChatGroupModel()->getMaxIdByGroup($groupId);
        $effect = Db::table("im_groups")
            ->insert([
                "user_id"=>$userId,
                "contact_id"=>$groupId,
                "contact_date"=>$now,
                "last_active_time"=>$now,
                "last_send_time"=>$now,
                "last_reads"=>0,
                "last_visible"=>$maxId
            ]);
        if ($effect == 1) {
            return true;
        }
        return false;
    }
    
    public function updateGroup($gid, $data) {
        return Db::table("im_group")->where("id=:gid")
            ->bind([
                "gid"=>[$gid, \PDO::PARAM_INT]
            ])
            ->update($data);
    }
    
    /**
     * 更新群聊成员信息
     * @param mixed $gid
     * @param mixed $uid
     * @param mixed $data
     * @return number|string
     */
    public function updateGroupMember($gid, $uid, $data) {
        return model("groups")->getQuery()
            ->where("contact_id=:gid AND user_id=:uid")
            ->bind([
                "gid"=>[$gid, \PDO::PARAM_INT],
                "uid"=>[$uid, \PDO::PARAM_INT],
            ])
            ->update($data);
    }
}