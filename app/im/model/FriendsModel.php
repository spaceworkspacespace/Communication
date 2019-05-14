<?php
namespace app\im\model;


use app\im\exception\OperationFailureException;
use app\im\service\SingletonServiceFactory;
use think\Db;

class FriendsModel extends IMModel implements IFriendModel {
    
    public function createFriendGroup($userId, $data)  {
        $data = array_index_pick($data, "group_name", "priority", "member_count", "create_time");
        $data["user_id"] = $userId;
        if(!isset($data["priority"])) {
            // 得到分组优先级
            $priority = Db::table("im_friend_groups")
                ->where("user_id=:id")
                ->bind(["id"=> [$userId, \PDO::PARAM_INT]])
                ->max("priority");
            // 确定新分组的优先级
            if (is_numeric($priority)) $priority+=10;
            else $priority = 10;
            
            $data["priority"] = $priority;
        }
        // 填充成员数量
        if (!isset($data["member_count"])) $data["member_count"] = 0;
        // 填充创建时间
        if (!isset($data["create_time"])) $data["create_time"] = time();
       
        // 新建分组
        $data["id"] = Db::table("im_friend_groups")->insert($data, false, true);
        
        array_key_replace_force($data, [
            "group_name"=>"groupname",
            "create_time"=>"createtime",
            "member_count"=>"membercount"
        ]);
        return $data;
    }
    
    public function createFriendGroupMultiple($data) {
        $inserts = [];
        $priorityMap = [];
        foreach ($data as $d) {
            $d = array_index_pick($d, "user_id", "group_name", "priority", "member_count", "create_time");
            // 数据无效
            if (!isset($d["user_id"]) || !$d["group_name"]) {
                continue;
            }
            // 填充优先级
            if(!isset($d["priority"])) {
                // 已经查询过了
                if (isset($priorityMap[$d["user_id"]])) {
                    $priorityMap[$d["user_id"]] += 10;
                    $d["priority"] = $priorityMap[$d["user_id"]];
                    // 没有查询过
                } else {
                    // 得到分组优先级
                    $priority = Db::table("im_friend_groups")
                        ->where("user_id=:id")
                        ->bind(["id"=> [$d["user_id"], \PDO::PARAM_INT]])
                        ->max("priority");
                    // 确定新分组的优先级
                    if (is_numeric($priority)) $priority+=10;
                    else $priority = 10;
                    $priorityMap[$d["user_id"]] = $d["priority"] = $priority;
                }
            }
            // 填充成员数量
            if (!isset($d["member_count"])) $d["member_count"] = 0;
            // 填充创建时间
            if (!isset($d["create_time"])) $d["create_time"] = time();
            
            // 加入要插入的数据
            array_push($inserts, $d);
        }
        
        $ids = $this->saveAll($inserts);
        // Db::table("im_friend_groups")->insertAll($inserts);
        im_log("debug", "数组 id: ", $ids);
        // 转换变量
        foreach($inserts as &$m) {
            array_key_replace_force($m, [
                "group_name"=>"groupname",
                "create_time"=>"createtime",
                "member_count"=>"membercount"
            ]);
        }
        
        return $inserts;
    }
    
    public function deleteFriendById($uid1, $uid2) {
        foreach (array_merge($uid1, $uid2) as $m) {
            if (!is_numeric($m)) {
                return 0;
            }
        }
        
        $uidStr1 = implode(",", $uid1);
        $uidStr2 = implode(",", $uid2);
        $sql = "DELETE FROM `im_friends` WHERE `contact_id` IN ($uidStr1) AND `user_id` IN ($uidStr2) OR `contact_id` IN ($uidStr2) AND `user_id` IN ($uidStr1)";
        im_log("sql", $sql);
        return Db::execute($sql);
    }
    
    public function deleteFriendGroupById(...$ids) {
        $effect = Db::table("im_friend_groups")
            ->whereIn("id", $ids)
            ->delete();
        return $effect;
    }
    
    public function determineFriendGroupByName($userId, ...$name) {
        // 查询
        $fgroup = Db::table("im_friend_groups")
            ->field("g.group_name AS groupname, g.id, g.priority, g.create_time AS createtime, g.member_count AS membercount, user_id AS uid")
            ->where("user_id=:uid")
            ->bind(["uid"=>[$userId, \PDO::PARAM_INT]])
            ->whereIn("group_name", $name)
            ->select()
            ->toArray();
        // 筛选出不存在的分组
        $result = [];
        $less = [];
        for($i=count($name)-1; $i>=0; $i--) {
            $n = $name[$i];
            foreach($fgroup as $g) {
                if ($g["groupname"] == $n) {
                    $result[$i] = $g;
                }
            }
            if (!isset($result[$i])) {
                array_push($less, $n);
            }
        }
        // 没有要插入的值
        if (count($less) == 0) {
            return $result;
        }
        // 创建分组
        $inserts = [];
        foreach ($less as $n) {
            array_push($inserts, [
                "user_id"=>$userId,
                "group_name"=>$n
            ]);
        }
        $newfg = $this->createFriendGroupMultiple($inserts);
        // 组合结果
        foreach ($newfg as $fg) {
            $index = array_search($fg["groupname"], $name);
            if (is_numeric($index)) {
                $result[$index] = $fg;
            }
        }
        // 返回
        return $result;
    }
    
    public function getFriendGroupById(...$fgId) {
        $data = Db::table("im_friend_groups")
            ->alias("g")
            ->field("g.group_name AS groupname, g.id, g.priority, g.create_time AS createtime, g.member_count AS membercount, user_id AS uid")
            ->whereIn("id", $fgId)
            ->select()
            ->toArray();
        
        // 排序
        $result = [];
        for ($i=count($fgId)-1; $i>=0; $i--) {
            $id = $fgId[$i];
            foreach($data as $g) {
                if ($g["id"] == $id) {
                    $result[$i] = $g;
                }
            }
        }
        return $result;
    }
    
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
     * 获取用户好友的所有 id
     * @param mixed $userId
     * @return \think\Collection
     */
    public function getFriendIds($userId): array {
        $result = $this->getQuery()
            ->field("contact_id")
            ->where("user_id=:uid")
            ->bind(["uid"=>[$userId, \PDO::PARAM_INT]])
            ->select()
            ->toArray();
        return array_column($result, "contact_id");
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
            ->field("u.id AS id, u.user_nickname AS username, u.avatar, u.signature AS sign, u.sex,
             g.group_name AS groupname, g.id AS groupid, g.priority, g.create_time AS createtime, g.member_count AS membercount")
            ->join(["im_friend_groups"=>"g"], "f.group_id=g.id AND g.user_id=f.user_id", "RIGHT OUTER")
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
                    "priority"=>$res["priority"],
                    "createtime"=>$res["createtime"],
                    "membercount"=>$res["membercount"],
                    "list"=>[]
                ];
            }
            // 加入好友信息
            if (isset($res["id"]) && !is_null($res["id"])) {
                $res["status"] = SingletonServiceFactory::getUserService()->isOnline($userId) ? "online":"offline";
                array_push($friends[$res["groupid"]]["list"], $res);
            }
        }
        return array_values($friends);
    }
    
    public function getFriendAndGroupById($userId, ...$uid) {
        foreach(array_merge([$userId], $uid) as $m) {
            if (!is_numeric($m)) {
                return false;
            }
        }
        
        $uidStr = implode(",", $uid);
        $sql = <<<SQL
SELECT `u`.`id` AS `id`,
    CASE
        WHEN `f`.`contact_alias` IS NOT NULL
            AND `f`.`contact_alias` != '' 
        THEN `f`.`contact_alias`
        WHEN `u`.`user_nickname` IS NOT NULL
            AND `u`.`user_nickname` != '' 
        THEN `u`.`user_nickname`
        ELSE `u`.`user_login`
    END AS `username`, 
    `u`.`avatar`, 
    `u`.`signature` AS `sign`, 
    CASE 
        WHEN `u`.`sex`=1
            THEN '男'
        WHEN `u`.`sex`=2
            THEN '女'
        ELSE '保密'
    END AS `sex`,
    `g`.`group_name` AS `groupname`, 
    `g`.`id` AS `groupid`, 
    `g`.`priority`, 
    `g`.`create_time` AS `createtime`, 
    `g`.`member_count` AS `membercount`
FROM `im_friends` `f`
    RIGHT JOIN `im_friend_groups` `g`
        ON `f`.`group_id`=`g`.`id`
            AND `g`.`user_id`=`f`.`user_id`
    LEFT JOIN `cmf_user` `u`
        ON `f`.`contact_id`=`u`.`id`
WHERE `g`.`user_id`=$userId
    AND `f`.`contact_id` IN ($uidStr)
ORDER BY  `g`.`priority` ASC;
SQL;
        im_log("sql", $sql);
        $resultSet = Db::query($sql);
        // 组装结果
        $friends = [];
         foreach($resultSet as $res) {
             // 加入分组信息
             if (!isset($friends[$res["groupid"]])) {
                 $friends[$res["groupid"]] = [
                     "id"=>$res["groupid"],
                     "groupname"=>$res["groupname"],
                     "priority"=>$res["priority"],
                     "createtime"=>$res["createtime"],
                     "membercount"=>$res["membercount"],
                     "list"=>[]
                 ];
             }
             // 加入好友信息
             if (isset($res["id"]) && !is_null($res["id"])) {
                 $res["status"] = SingletonServiceFactory::getUserService()->isOnline($userId) ? "online":"offline";
                 array_push($friends[$res["groupid"]]["list"], $res);
             }
         }
         return array_values($friends);
    }
    
    /**
     * 获取和联系人聊天的最后读取消息的 id
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
    
    public function getOriginFriendByUser($userId1, $userId2, $fields="*")  {
        $resultSet = Db::table("im_friends")
            ->field($fields)
            ->where("user_id=:uid1 AND contact_id=:uid2")
            ->bind([
                "uid1"=>[$userId1, \PDO::PARAM_INT],
                "uid2"=>[$userId2, \PDO::PARAM_INT],
            ])
            ->select()
            ->toArray();
        if (!is_array($resultSet) || count($resultSet) == 0) {
            return [];
        }
        return $resultSet[0];
    }
    
    public function isFriend($userId, $userId2) {
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
    
    public function setFriend($uid, $fgId, $uid1, $fgId1) {
        $now = time();
        $maxId = ModelFactory::getChatFriendModel()->getMaxIdByUser($uid, $uid1);
        $effect = Db::table("im_friends")
            ->insertAll([
                [
                    "user_id"=>$uid,
                    "contact_id"=>$uid1,
                    "group_id"=>$fgId,
                    "contact_date"=>$now,
                    "last_active_time"=>$now,
                    "last_send_time"=>$now,
                    "last_reads"=>0,
                    "last_visible"=>$maxId
                ], [
                    "user_id"=>$uid1,
                    "contact_id"=>$uid,
                    "group_id"=>$fgId1,
                    "contact_date"=>$now,
                    "last_active_time"=>$now,
                    "last_send_time"=>$now,
                    "last_reads"=>0,
                    "last_visible"=>$maxId
                ]
            ]);
        if ($effect == 2) {
            return true;
        }
        return false;
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
    
    public function updateFriend($userId, $data) {
        return $this->getQuery()
            ->where("user_id=:id1 AND contact_id=:id2")
            ->bind([
                "id1"=>[$userId, \PDO::PARAM_INT],
                "id2"=>[$data["contact_id"], \PDO::PARAM_INT],
            ])
            ->update($data);
    }
    
    public function updateFriendByFriendGroup($userId, $fgId, $data)  {
        $data = array_index_pick($data, "group_id");
        if (count($data) == 0) {
            return 0;
        }
        // 更新
        return Db::table("im_friends")
            ->where("user_id=:uid AND group_id=:fgid")
            ->bind([
                "uid"=>[$userId, \PDO::PARAM_INT],
                "fgid"=>[$fgId, \PDO::PARAM_INT],
            ])
            ->update($data);
    }
    
    public function updateFriendGroup($fgId, $data) {
        
        $effect = Db::table("im_friend_groups")
            ->where("id=:fid")
            ->bind([
                "fid"=>[$fgId,\PDO::PARAM_INT]
            ])
            ->update($data);
        if ($effect > 0) {
            return $data;
        }
        return [];
    }
}