<?php
namespace app\im\model;

use think\Db;
use think\Model;

class UserModel extends Model implements IUserModel {
    protected $type = [
        'more' => 'array',
    ];
    
    public function existAll(...$userIds): bool {
        // 有效的 id 数
        $count = Db::table("cmf_user")
            ->whereIn("id", $userIds)
            ->count("id");
        if ($count !== count($userIds)) {
            return false;
        }
        return true;
    }
    
    
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
        return Db::table("cmf_user")
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
        
        
        public function doMobile($user)
        {
            $result = $this->where('mobile', $user['mobile'])->find();
            
            
            if (!empty($result)) {
                $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
                $hookParam             = [
                    'user'                    => $user,
                    'compare_password_result' => $comparePasswordResult
                ];
                hook_one("user_login_start", $hookParam);
                if ($comparePasswordResult) {
                    //拉黑判断。
                    if ($result['user_status'] == 0) {
                        return 3;
                    }
                    session('user', $result->toArray());
                    $data = [
                        'last_login_time' => time(),
                        'last_login_ip'   => get_client_ip(0, true),
                    ];
                    $this->where('id', $result["id"])->update($data);
                    $token = cmf_generate_user_token($result["id"], 'web');
                    if (!empty($token)) {
                        session('token', $token);
                    }
                    return 0;
                }
                return 1;
            }
            $hookParam = [
                'user'                    => $user,
                'compare_password_result' => false
            ];
            hook_one("user_login_start", $hookParam);
            return 2;
        }
        
        public function doName($user)
        {
            $result = $this->where('user_login', $user['user_login'])->find();
            if (!empty($result)) {
                $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
                $hookParam             = [
                    'user'                    => $user,
                    'compare_password_result' => $comparePasswordResult
                ];
                hook_one("user_login_start", $hookParam);
                if ($comparePasswordResult) {
                    //拉黑判断。
                    if ($result['user_status'] == 0) {
                        return 3;
                    }
                    session('user', $result->toArray());
                    $data = [
                        'last_login_time' => time(),
                        'last_login_ip'   => get_client_ip(0, true),
                    ];
                    $result->where('id', $result["id"])->update($data);
                    $token = cmf_generate_user_token($result["id"], 'web');
                    if (!empty($token)) {
                        session('token', $token);
                    }
                    return 0;
                }
                return 1;
            }
            $hookParam = [
                'user'                    => $user,
                'compare_password_result' => false
            ];
            hook_one("user_login_start", $hookParam);
            return 2;
        }
        
        public function doEmail($user)
        {
            
            $result = $this->where('user_email', $user['user_email'])->find();
            
            if (!empty($result)) {
                $comparePasswordResult = cmf_compare_password($user['user_pass'], $result['user_pass']);
                $hookParam             = [
                    'user'                    => $user,
                    'compare_password_result' => $comparePasswordResult
                ];
                hook_one("user_login_start", $hookParam);
                if ($comparePasswordResult) {
                    
                    //拉黑判断。
                    if ($result['user_status'] == 0) {
                        return 3;
                    }
                    session('user', $result->toArray());
                    $data = [
                        'last_login_time' => time(),
                        'last_login_ip'   => get_client_ip(0, true),
                    ];
                    $this->where('id', $result["id"])->update($data);
                    $token = cmf_generate_user_token($result["id"], 'web');
                    if (!empty($token)) {
                        session('token', $token);
                    }
                    return 0;
                }
                return 1;
            }
            $hookParam = [
                'user'                    => $user,
                'compare_password_result' => false
            ];
            hook_one("user_login_start", $hookParam);
            return 2;
        }
        
        public function register($user, $type)
        {
            switch ($type) {
                case 1:
                    $result = Db::name("user")->where('user_login', $user['user_login'])->find();
                    break;
                default:
                    $result = 0;
                    break;
            }
            
            $userStatus = 1;
            
            if (cmf_is_open_registration()) {
                $userStatus = 2;
            }
            
            if (empty($result)) {
                $data   = [
                    'user_login'      => empty($user['user_login']) ? '' : $user['user_login'],
                    'user_email'      => empty($user['user_email']) ? '' : $user['user_email'],
                    'mobile'          => empty($user['mobile']) ? '' : $user['mobile'],
                    'user_nickname'   => empty($user['user_login']) ? '' : $user['user_login'],
                    'user_pass'       => cmf_password($user['user_pass']),
                    'last_login_ip'   => get_client_ip(0, true),
                    'create_time'     => time(),
                    'last_login_time' => time(),
                    'user_status'     => $userStatus,
                    'user_type'       => 2,//会员
                    'avatar' => 'https://i.loli.net/2019/04/12/5cafffdaed88f.jpg',  //默认头像
                ];
                $userId = Db::name("user")->insertGetId($data);
                $data   = Db::name("user")->where('id', $userId)->find();
                cmf_update_current_user($data);
                $token = cmf_generate_user_token($userId, 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                return 0;
            }
            return 1;
        }
        
        /**
         * 通过邮箱重置密码
         * @param $email
         * @param $password
         * @return int
         */
        public function emailPasswordReset($email, $password)
        {
            $result = $this->where('user_email', $email)->find();
            if (!empty($result)) {
                $data = [
                    'user_pass' => cmf_password($password),
                ];
                $this->where('user_email', $email)->update($data);
                return 0;
            }
            return 1;
        }
        
        /**
         * 通过手机重置密码
         * @param $mobile
         * @param $password
         * @return int
         */
        public function mobilePasswordReset($mobile, $password)
        {
            $userQuery = Db::name("user");
            $result    = $userQuery->where('mobile', $mobile)->find();
            if (!empty($result)) {
                $data = [
                    'user_pass' => cmf_password($password),
                ];
                $userQuery->where('mobile', $mobile)->update($data);
                return 0;
            }
            return 1;
        }
        
        public function editData($user)
        {
            $userId = cmf_get_current_user_id();
            
            if (isset($user['birthday'])) {
                $user['birthday'] = strtotime($user['birthday']);
            }
            
            $field = 'user_nickname,sex,birthday,user_url,signature,more';
            
            if ($this->allowField($field)->save($user, ['id' => $userId])) {
                $userInfo = $this->where('id', $userId)->find();
                cmf_update_current_user($userInfo->toArray());
                return 1;
            }
            return 0;
        }
        
        /**
         * 用户密码修改
         * @param $user
         * @return int
         */
        public function editPassword($user)
        {
            $userId    = cmf_get_current_user_id();
            $userQuery = Db::name("user");
            if ($user['password'] != $user['repassword']) {
                return 1;
            }
            $pass = $userQuery->where('id', $userId)->find();
            if (!cmf_compare_password($user['old_password'], $pass['user_pass'])) {
                return 2;
            }
            $data['user_pass'] = cmf_password($user['password']);
            $userQuery->where('id', $userId)->update($data);
            return 0;
        }
        
        public function comments()
        {
            $userId               = cmf_get_current_user_id();
            $userQuery            = Db::name("Comment");
            $where['user_id']     = $userId;
            $where['delete_time'] = 0;
            $favorites            = $userQuery->where($where)->order('id desc')->paginate(10);
            $data['page']         = $favorites->render();
            $data['lists']        = $favorites->items();
            return $data;
        }
        
        public function deleteComment($id)
        {
            $userId              = cmf_get_current_user_id();
            $userQuery           = Db::name("Comment");
            $where['id']         = $id;
            $where['user_id']    = $userId;
            $data['delete_time'] = time();
            $userQuery->where($where)->update($data);
            return $data;
        }
        
        /**
         * 绑定用户手机号
         */
        public function bindingMobile($user)
        {
            $userId          = cmf_get_current_user_id();
            $data ['mobile'] = $user['username'];
            Db::name("user")->where('id', $userId)->update($data);
            $userInfo = Db::name("user")->where('id', $userId)->find();
            cmf_update_current_user($userInfo);
            return 0;
        }
        
        /**
         * 绑定用户邮箱
         */
        public function bindingEmail($user)
        {
            $userId              = cmf_get_current_user_id();
            $data ['user_email'] = $user['username'];
            Db::name("user")->where('id', $userId)->update($data);
            $userInfo = Db::name("user")->where('id', $userId)->find();
            cmf_update_current_user($userInfo);
            return 0;
        }
        
        public function login($username, $password)
        {
            $password = cmf_password($password);
            return Db::table('cmf_user')
            ->where('user_login', $username)
            ->where('user_pass', $password)
            ->field('user_nickname AS username,id,avatar,signature AS sign,sex')
            ->find();
        }
    
}