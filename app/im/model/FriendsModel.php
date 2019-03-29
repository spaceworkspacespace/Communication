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
            ->join(["im_friend_groups"=>"g"], "f.group_id=g.id")
            ->join(["cmf_user"=>"u"], "f.contact_id=u.id")
            ->where("f.user_id=:id")
            ->bind(["id"=>[$userId, \PDO::PARAM_INT]])
            ->order("g.priority", "asc")
            ->select()
            ->toArray();
        foreach($resultSet as $res) {
            if (!isset($friends[$res["groupid"]])) {
                $friends[$res["groupid"]] = [
                    "id"=>$res["groupid"],
                    "groupname"=>$res["groupname"],
                    "list"=>[]
                ];
            }
            array_push($friends[$res["groupid"]]["list"], $res);
        }
        return $friends;
    }
}