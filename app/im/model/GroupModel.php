<?php 
namespace app\im\model;

use think\Db;
use app\im\service\SingletonServiceFactory;


class GroupModel extends IMModel implements IGroupModel {
    
    public function createGroup($userId, $data) {
        $now = time();
        $data = array_index_pick($data, "groupname", "description",  "avatar");
        $data = array_merge($data, [
            "create_time"=>$now,
            "admin_id"=>$userId,
            "admin_count"=>1,
            "creator_id"=>1,
            "member_count"=>1
        ]);
        $data["id"] = Db::table("im_group")
            ->insert($data, false, true);
        
        if (!is_numeric($data["id"])) {
            return null;
        }
        
        Db::table("im_groups")
            ->insert([
                "user_id"=>$userId,
                "contact_id"=>$data["id"],
                "is_admin"=>1,
                "contact_date"=>$now,
                "last_active_time"=>$now,
                "last_send_time"=>$now,
                "last_reads"=>0,
                "last_visible"=>0
            ]);
        array_key_replace_force($data, [
            "create_time"=> "createtime",
            "admin_id"=> "admin", 
            "admin_count" => "admincount",
            "member_count" => "membercount"
        ]);
        return $data;
    }
    
    public function queryMyPermi($id, $gid)
    {
        $isAdmin = Db::table('im_group a,im_groups b')
        ->where('a.id = b.contact_id')
        ->where([
            'b.user_id' => $id,
            'a.id' => $gid
        ])
        ->find();
        
        if($isAdmin['creator_id'] == $id){
            return 2;
        }else if($isAdmin['is_admin'] == 1){
            return 1;
        }else{
            return 0;
        }
    }
    
    public function deleteGroupMemberById($gid, ...$uid) {
        $uidStr = implode(",", $uid);
        $sql = "DELETE FROM `im_groups` WHERE `contact_id`=$gid AND `user_id` IN ($uidStr);";
        im_log("SQL", $sql);
        
        return Db::execute($sql);
    }
    
    public function existsGroupByName($name) {
        $count = Db::table("im_group")->where("groupname=:gname")
            ->bind([
                "gname"=>[$name, \PDO::PARAM_STR]
            ])
            ->count("groupname");
        if ($count == 0) {
            return 0;
        }
        return 1;
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
    
    public function updateGroupMember($gid, $uid, $data) {
        return model("groups")->getQuery()
            ->where("contact_id=:gid AND user_id=:uid")
            ->bind([
                "gid"=>[$gid, \PDO::PARAM_INT],
                "uid"=>[$uid, \PDO::PARAM_INT],
            ])
            ->update($data);
    }
    
    public function deleteMyGroup($gid, $user)
    {
        
        //查询出该群所有管理员的id
        $adminIds = $this->queryGroupAdminById($gid);
        
        //删除在群聊表中相关的信息
        Db::table('im_groups')
        ->where([
            'user_id' => $user['id'],
            'contact_id' => $gid
        ])
        ->delete();
        
        //为所有管理员生成成员变动通知
        $imId = Db::table('im_msg_box')
        ->insertGetId([
            'sender_id' => $user['id'],
            'send_date' => time(),
            'send_ip' => $user['last_login_ip'],
            'content' => '用户'.$user['user_nickname'].'已退出群聊',
        ]);
        foreach ($adminIds as $value) {
            Db::table('im_msg_receive')
            ->insertAll([
                [
                    'id' => $imId,
                    'receiver_id' => $value['user_id'],
                    'send_date' => time()
                ]
            ]);
        }
    }

    public function GroupsCount($gid, $str)
    {
        if ($str == 0) {
            Db::table('im_group')
            ->where('id', $gid)
            ->dec('member_count')
            ->update();
        } else if($str == 0) {
            Db::table('im_group')
            ->where('id', $gid)
            ->inc('member_count')
            ->update();
        }
    }
    
    public function queryGroupAdminById($gid)
    {
        return Db::table('im_groups')
        ->where([
            'contact_id' => $gid,
            'is_admin' => 1
        ])
        ->field('user_id')
        ->select();
    }
    
    public function postGroupMember($gid, $uid, $user)
    {
        //在im_msg_box插入相关通知
        $imId = Db::table('im_msg_box')
        ->insertGetId([
            'sender_id' => $user['id'],
            'send_date' => time(),
            'send_ip' => $user['last_login_ip'],
            'content' => $user['user_nickname'].'邀请您加入群聊',
            'type' => 3,
            'corr_id' => $uid,
            'corr_id2' => $gid
        ]);
        
        //在im_msg_receive插入相关通知
        Db::table('im_msg_receive')
        ->insert([
            'id' => $imId,
            'receiver_id' => $uid,
            'send_date' => time(),
        ]);
    }
    
    public function putGroup($gid, $name, $desc, $avatar, $admin)
    {
        Db::table('im_group')
        ->where(['id' => $gid])
        ->update([
            'groupname' => $name,
            'description' => $desc,
            'avatar' => $avatar,
            'admin_id' => $admin
        ]);
        
        return Db::table('im_group')
        ->where(['id' => $gid])
        ->field('id,groupname,description,avatar,create_time AS createtime,admin_id AS admin,
        admin_count AS admincount,create_time AS createtime,member_count AS membercount')
        ->select();
    }
    
    public function deleteGroup($gid)
    {
        //修改群聊解散时间为3天后
        Db::table('im_group')
        ->where('id', $gid)
        ->update(['delete_time' => time()+3*60*60*24]);
        
        $groupname = Db::table('im_group')
        ->where('id', '=', $gid)
        ->value('groupname');
        
        //为群聊中所有成员生成群聊解散消息
        $imId = Db::table('im_msg_box')
        ->insertGetId([
            'sender_id' => 0,
            'send_date' => time(),
            'type' => 0,
            'content' => $groupname.'群聊将在3天后解散'
        ]);
        
        //查询群聊所有成员
        $groupUsersId = Db::table('im_groups')
        ->where([
            'contact_id' => $gid
        ])
        ->field('user_id')
        ->select();
        
        foreach ($groupUsersId as $value) {
            Db::table('im_msg_receive')
            ->insert([
                'id' => $imId,
                'receiver_id' => $value['user_id'],
                'send_date' => time()
            ]);
            //为所有群成员推送通知
            SingletonServiceFactory::getPushService()->pushMsgBoxNotification($value['user_id']);
        }
    }
    
    public function deleteGroups($gid)
    {
        
        $groupname = Db::table('im_group')
        ->where('id', '=', $gid)
        ->value('groupname');
        
        //为群聊中所有成员生成群聊解散消息
        $imId = Db::table('im_msg_box')
        ->insertGetId([
            'sender_id' => 0,
            'send_date' => time(),
            'type' => 0,
            'content' => '群聊'.$groupname.'已解散'
        ]);
        
        //查询群聊所有成员
        $groupUsersId = Db::table('im_groups')
        ->where([
            'contact_id' => $gid
        ])
        ->field('user_id')
        ->select();
        
        foreach ($groupUsersId as $value) {
            Db::table('im_msg_receive')
            ->insert([
                'id' => $imId,
                'receiver_id' => $value['user_id'],
                'send_date' => time()
            ]);
            //为所有群成员推送通知
            SingletonServiceFactory::getPushService()->pushMsgBoxNotification($value['user_id']);
        }
        
        Db::table('im_group')
        ->where('id', '=', $gid)
        ->delete();
    }
    
    public function queryGroupDissolve($gid)
    {
        return Db::table('im_group')
        ->where('id', '=', $gid)
        ->value('delete_time');
    }
    
    public function getGroupByName($groupName)
    {
        return Db::table('im_group')
        ->where('groupname', '=', $groupName)
        ->select()
        ->toArray();
    }
    
    public function queryDeleteGroup()
    {
        return Db::table('im_group')
        ->where('delete_time IS NOT NULL')
        ->field('id, delete_time as deletetime')
        ->select()
        ->toArray();
    }


}