<?php
namespace app\im\model;

use think\Db;

class FriendGroupsModel extends IMModel {
    
    public function members() {
        return $this->belongsToMany("friends_model");
    }
    
    /**
     * 查找用户的好友分组信息
     * @param mixed $userId 用户 id
     * @param mixed $fgId 分组 id
     * @return null | array 如果没有对应的分组, 返回 null
     */
    public function getFriendGroup($userId, $fgId) {
        if (!is_numeric($userId) || !is_numeric($fgId)) {
            return false;
        }
        $sql = <<<SQL
SELECT  `id`, `group_name` AS `groupname`, `priority`, `create_time` AS `createtime`, `member_count` AS `membercount`
	FROM `im_friend_groups`
	WHERE `id`=$fgId AND `user_id`=$userId;
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
}