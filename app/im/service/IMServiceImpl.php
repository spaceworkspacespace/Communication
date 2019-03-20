<?php
namespace app\im\service;

require_once implode([
    __DIR__,
    DIRECTORY_SEPARATOR,
    "IIMService.php"
]);

use app\im\model\ChatGroupModel;
use app\im\model\FriendsModel;

class IMServiceImpl implements IIMService
{
    public function init($userId)
    {
        return [
            "mine" => $this->findUser($userId),
            // 查找出需要的数据, 除去多余的字段并改变字段名为需要的字段名.
            "friend" => array_map(function ($item) {
                $group = array_index_pick($item, "id", "group_name", "list");
                array_key_replace($group, [
                    "group_name" => "groupname"
                ]);
                array_map(function($item) {
                    $friends = array_index_pick($item, "user_nickname", "avatar", "sign", "id");
                    array_key_replace($friends, "user_nickname", "username");
                    return $friends;
                }, $group["list"]);
                return $group;
            }, $this->findOwnFriends($userId)),
            "group" => array_index_pick($this->findOwnGroups($userId), "id", "groupname", "avatar")
        ];
    }

    public function findOwnFriends($userId): array
    {
        // 对参数进行验证. 确保为数字样式.
//         if (! is_numeric($userId))
//             return [];
//         $model = model("friends");
//         $friends = $model->query(implode([
//             "SELECT * FROM im_user_entire user INNER JOIN im_friends friends ON user.id = friends.user_id WHERE friends.user_id=",
//             $userId,
//             ";"
//         ]));

        // $model = model("user_entire");
        // $model->hasOne("friends", "user_id", "id", [], "LEFT OUTER")->
        // $model = new \app\user\model\UserModel();
        // $model->hasOne($model);
        // $model->hasOne("user");
        // $model->get(["user_id"=>$userId]);
//         $groups = model("user_entire")::get(1)->friendGroups;
// $groups = model("user_entire")::get(1)->allFriends;
// $groups = model("user_entire")::get(1)->allFriends;
//             dump($groups->toArray());
// $groups = model("friend_groups")::get(2)->members;
// $friends = model("friend_groups")->belongsToMany("user_entire_model", "user_id", "id")
// dump($groups);
//         $friends = model("im")->query("");
        return model("user_entire")::contacts($userId);
    }

    public function findUser($userId): array
    {
        // 查找用户信息, im 扩展信息. 如果不存在, 更新进去.
        $model = model("user");
        $user = $model->get($userId);
        if (is_null($user) || 
            ! count($user = $user->getData())) {
            $user = [
                "user_id" => $userId,
                "sign" => ""
            ];
            $model->save($user);
        } 
        return $user;
    }
    
    public function findOwnGroups($userId): array
    {
        if (!is_numeric($userId)) return [];
        $model = model("groups");
        $group = $model->query(implode([
            "SELECT * FROM im_groups gs INNER JOIN im_group g ON gs.contact_id=g.id WHERE gs.user_id=",
            $userId,
            ";"
        ]));
        return $group;
    }
    public function findFriends($key): array
    {
        $model = model("user_entire");
        $friends = null;
        // 有效的 id 格式, 加上 id 查询的结果. 
        if (is_numeric($key)) {
            $friends = $model->query(implode([
                "SELECT * FROM im_user_entire WHERE id=",
                $key,
                "UNION SELECT * FROM im_user_entire WHERE user_nickname LIKE '%",
                $key,
                "%';"
            ]));
        } else {
         // 通过名称查找
         $friends = $model->query(implode([
             "SELECT * FROM im_user_entire WHERE user_nickname LIKE '%",
                $key,
                "%';"
         ]));
     }
     return $friends;
    }

    public function findGroups($key): array
    {
        $model = model("group");
        $groups = null;
        // 有效的 id 格式, 加上 id 查询的结果.
        if (is_numeric($key)) {
            $groups = $model->query(implode([
                "SELECT * FROM im_group WHERE id=",
                $key,
                "UNION SELECT * FROM im_group WHERE groupname LIKE '%",
                $key,
                "%';"
            ]));
        } else {
            // 通过名称查找
            $groups = $model->query(implode([
                "UNION SELECT * FROM im_group WHERE groupname LIKE '%",
                $key,
                "%';"
            ]));
        }
        return $groups;
    }
    
    public function getOwnFriendGroups($userId): array
    {
        $groups = model("user_entire")::get($userId)->friendGroups->toArray();
        
        return array_map(function($item) {
            return array_index_pick($item, "id", "group_name");
        }, $groups);
    }


    


}