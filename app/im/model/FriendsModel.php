<?php
namespace app\im\model;


class FriendsModel extends IMModel {
    
    /**
     * 获取用户的好友分组以及好友信息
     * @param mixed $userId
     * @return \think\Collection
     */
    public function getFriendAndGroup($userId): array {
        $friends = [];
        $resultSet = $this->getQuery()
            ->alias("f")
            ->field("u.id AS id, u.user_nickname AS username, u.avatar, u.signature AS sign, g.group_name AS groupname, g.id AS groupid")
            ->join(["im_friend_groups"=>"g"], "f.group_id=g.id", "RIGHT OUTER")
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
                    "list"=>[]
                ];
            }
            // 加入好友信息
            if (!isset($res["id"]) && !is_null($res["id"])) {
                array_push($friends[$res["groupid"]]["list"], $res);
            }
        }
        return array_values($friends);
    }
}