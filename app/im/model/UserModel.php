<?php
namespace app\im\model;

use think\Model;
use function Qiniu\json_decode;
use think\Db;

class UserModel extends Model implements IUserModel {
    public function getUserById(...$userId) {
        $users = $this->getQuery()
            ->field("id, avatar, user_nickname AS username, signature AS sign")
            ->whereIn("id", $userId)
            ->select()
            ->toArray();
        
        $result = [];
        for ($i=0; $i<count($userId); $i++) {
            $id = $userId[$i];
            foreach ($users as $u) {
                if ($u["id"] == $id) {
                    $result[$i] = $u;
                    break;
                }
            }
        }
        return $result;
    }
    // 一对多关联
    public function msgbox()
    {
        return $this->hasMany('MsgBoxModel');
    }

    public function friendGroups()
    {
        return $this->hasMany("friend_groups_model", "user_id", "id");
    }

    public function friends()
    {
        return $this->hasMany("friends_model", "user_id", "id");
    }
    
    public static function contacts($userId) {
            return [];
    }
    
    public function getMyFriendInfo($id, $no, $count)
    {
        $userIds = Db::table('im_friends')
        ->where(['user_id' => $id])
        ->field('contact_id')
        ->select();
        
        return Db::table('cmf_user')
        ->whereIn('id', $userIds, 'or')
        ->field('user_nickname AS username,id,avatar,signature AS sign,sex')
        ->page($no, $count)
        ->select()
        ->toArray();
    }
    
    public function getFriendByIdOrName($keyword, $no, $count)
    {
        return Db::table('cmf_user')
        ->where('id', '=', $keyword)
        ->whereOr('user_nickname','LIKE','%'.$keyword.'%')
        ->field('user_nickname AS username,id,avatar,signature AS sign,sex')
        ->page($no, $count)
        ->select()
        ->toArray();
    }
    
    public function getFriendById($id)
    {
        return Db::table(cmf_user)
        ->where(['id' => $id])
        ->find('user_nickname AS username,id,avatar,signature AS sign,sex');
    }




    /*
    public static function contacts($userId)
    {
        // 查找用户分组信息
        $groups = self::get($userId)->friendGroups->toArray();
        // 连表查询所有的用户好友信息
        $friends = self::get($userId)->friends->toArray();
        // im_log("debug", $groups, "; ",$friends);
        // 排序再确定分组和好友的关系
        usort($groups, function ($l, $r) {
            return $l["id"] > $r["id"] ? 1 : - 1;
        });
        usort($friends, function ($l, $r) {
            return $l["group_id"] > $r["group_id"] ? 1 : - 1;
        });
        foreach ($groups as &$group)
            $group["list"] = [];
        $groupIndex = 0;
        $group = &$groups[$groupIndex];
        foreach ($friends as $friend) {
            // 判断联系人是否属于此分组
            $user = self::get($friend['contact_id'])->toArray();
            $friend = array_merge($friend, $user);
            $friend['username'] = $friend['user_nickname'];
            if ($group["id"] == $friend["group_id"]) {
                array_push($group["list"], $friend);
            } else {
                // 用户是否属于下一个用户分组
                if ($groups[$groupIndex + 1]["id"] != $friend["group_id"]) {
                    // im_log("info", "联系人分组遍历查询, 用户: ", $userId, ", 联系人: ", $friend);
                    // 找到此联系人所在的分组.
                    $i = count($groups) - 1;
                    for (; $i >= 0; $i --) {
                        if ($groups[$i]["id"] != $friend["group_id"]) {
                            continue;
                        } else {
                            array_push($groups[$i]["list"], $friend);
                        }
                    }
                    // 判断是否为联系人找到正确的分组.
                    if ($groups[$i + 1]["list"][count($groups[$i + 1]["list"]) - 1] != $friend) {
                        // im_log("notice", "存在未知分组的联系人. \n分组信息: ", $groups, ", 联系人信息: ", $friend);
                    }
                    unset($i);
                    continue;
                }
                $group = &$groups[++ $groupIndex];
                array_push($group["list"], $friend);
            }
        }
        // var_dump($groups);
        // exit(0);
        // var_dump($groups);
        // exit(0);
        // im_log("debug", $groups);
        return $groups;
    }
    */
}