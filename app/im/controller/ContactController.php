<?php
namespace app\im\controller;

use think\Request;
use app\im\service\IMServiceImpl;
use think\Controller;
use app\im\exception\OperationFailureException;
use think\Db;
use app\im\model\GroupModel;
use app\im\model\GroupsModel;
use think\Cache;
use app\im\service\SingletonServiceFactory;

/**
 * 控制器获取用户信息, 需要用户登录后使用.
 *
 * @author silence
 *        
 */
class ContactController extends Controller
{

    protected $beforeActionList = [
        "checkUserLogin"
    ];

    protected $service = null;

    protected $user = null;

    public function __construct(Request $request)
    {
        $this->user = cmf_get_current_user();
        parent::__construct($request);
        $this->service = IMServiceImpl::getInstance();
        
    }

    protected function checkUserLogin()
    {
        $isLogin = $this->user && $this->user["id"];
        im_log("info", "用户登录验证: ", $isLogin);

        if (!$isLogin) {
            if ($this->request->isAjax()) {
                $this->success("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }
    }
    
    /**
     * 删除分组
     * @param int $id 要删除的分组id
     * @param int $into 新的分组id
     */
    public function deleteFriendGroup($id, $into=null) {
        $reMsg = "";
        $reData = "";
        $failure = false;
        try {
            SingletonServiceFactory::getContactService()->deleteFriendGroup($this->user["id"], $id, $into);
            $reMsg = "操作成功";
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally { 
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
        
//         //检查分组是否存在
//         if($this->checkNullGroups($id, null)){
//             $this->success("没有该分组,请检查您的分组信息", "/", null, 0);
//         }
        
//         Db::startTrans();
//         try {
            
//             //查找将要删除分组下的好友id
//             $contacts_id = Db::table('im_friends')
//             ->where([
//                 'user_id' => $this->user['id'],
//                 'group_id' => $id
//             ])
//             ->field('contact_id')
//             ->select();
            
//             //将好友移动到另一个分组
//             foreach ($contacts_id as $value) {
//                 Db::table('im_friends')
//                 ->where([
//                     'user_id' => $this->user['id'],
//                     'contact_id' => $value['contact_id']
//                 ])
//                 ->update(['group_id' => $into]);
//             }
            
//             //删除分组
//             Db::table('im_friend_groups')
//             ->where([
//                 'id' => $id,
//                 'user_id' => $this->user['id']
//             ])
//             ->delete();
            
//             Db::commit();
//         } catch (\Exception $e) {
//             Db::rollback();
//             $this->success("噢噢~ 遇到问题了,请稍后重", "/", null, 0);
//         }
//         $this->error("删除分组成功", "/", null ,0);
    }
    
    /**
     * 退出群聊
     * @param int $gid 群聊id
     */
    public function deleteMyGroup($gid) {
        $reMsg = "";
        $reData = "";
        $failure = false;
        try {
            SingletonServiceFactory::getContactService()->leaveGroup($this->user["id"], $gid);
            $reMsg = "退出成功";
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
        //         //查询出该群所有管理员的id
        //         $adminIds = $this->queryGroupAdminById($gid);
        
        //         Db::startTrans();
        //         try {
        //             //群聊人数-1
        //             $this->GroupsCount($gid, 0);
        
        //             //删除在群聊表中相关的信息
        //             Db::table('im_groups')
        //             ->where([
        //                 'user_id' => $this->user['id'],
        //                 'contact_id' => $gid
        //             ])
        //             ->delete();
        
        //             //为所有管理员生成成员变动通知
        //             $imId = Db::table('im_msg_box')
        //             ->insertGetId([
        //                 'sender_id' => $this->user['id'],
        //                 'send_date' => time(),
        //                 'send_ip' => $this->user['last_login_ip'],
        //                 'content' => '用户'.$this->user['nike_username'].'已退出群聊',
        //             ]);
        //             foreach ($adminIds as $value) {
        //                 Db::table('im_msg_receive')
        //                 ->insertAll([
        //                     [
        //                         'id' => $imId,
        //                         'receiver_id' => $value['user_id'],
        //                         'send_date' => time()
        //                     ]
        //                 ]);
        //             }
        //             Db::commit();
        //         } catch (\Exception $e) {
        //             Db::rollback();
        //             $this->success("奥奥~ 遇到问题了，请稍后重试", "/", $e->getMessage(), 0);
        //         }
        //         $this->error('已退出群聊', '/', null, 0);
    }
    
    /**
     * 添加好友
     * @param int $id 对方的id
     * @param int $fgId 添加到的好友分组id
     * @param string $content 验证消息内容
     */
    public function postFriend($id, $fgId, $content) {
        $reMsg = "";
        $reData = "";
        $failure = false;
        try {
            $reData = SingletonServiceFactory::getContactService()->addFriendAsk($this->user["id"], $fgId, $id, $content, $this->request->ip());
            $reMsg = "发送成功";
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally { 
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
    }
    
    /**
     * 加入群聊
     * @param int $gid 群聊的id
     * @param int $content 验证信息
     */
    public function postMyGroup($gid, $content) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        
        try {
            SingletonServiceFactory::getContactService()->joinGroupAsk($this->user["id"], $gid, $content, $this->request->ip());
            $reMsg = "发送成功";
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
        
//         //开启事务
//         Db::startTrans();
//         try {
//             //查询出该群所有管理员的id
//             $adminIds = $this->queryGroupAdminById($gid);
            
//             //在im_msg_box表加数据
//             $imId = Db::table('im_msg_box')
//             ->insertGetId([
//                 'sender_id' => $this->user['id'],
//                 'send_date' => time(),
//                 'send_ip' => $this->user['last_login_ip'],
//                 'content' => $content,
//                 'type' => 2,
//                 'corr_id' => $gid
//             ]);
            
//             //在im_msg_receive表加数据
//             foreach ($adminIds as $value) {
//                 Db::table('im_msg_receive')
//                 ->insert([
//                     'id' => $imId,
//                     'receiver_id' => $value['user_id'],
//                     'send_date' => time()
//                 ]);
//             }
            
//             Db::commit();
//         } catch (\Exception $e) {
//             Db::rollback();
//             $this->success('奥奥~ 遇到问题了，请稍后重试', '/', $e->getMessage(), 0);
//         }
//         $this->error('消息发送成功', '/' , null, 0);
    }
    

    /**
     * 新建一个群聊
     *
     * @param string $groupname
     * @param mixed $avatar
     * @param string $description
     */
    public function postGroup(string $groupname, $avatar, string $description)
    {
        $validate = new \app\im\validate\GroupValidate();
        if ($validate->check([
            "groupname"=>$groupname, 
            "avatar"=>$avatar, 
            "description"=>$description])) {
            try {
                $this->service->createGroup($this->user["id"], $groupname, $avatar, $description);
                $this->error("", "/", "群组\"$groupname\"创建成功.", 0);
            } catch (OperationFailureException $e) {
                $this->success("", "/", $e->getMessage(), 0);
            }
        }
        im_log("info", "数据验证失败 !", $validate->getError());
        $this->success("", "/", $validate->getError(), 0);
    }
    
    /**
     * 邀请加入群聊
     * @param int $gid 群聊id
     * @param int $uid 用户id
     */
    public function postGroupMember($gid, $uid) {
        //检查用户是否已经在群聊当中
        $checkUser = Db::table('im_groups')
        ->where([
            'user_id' => $uid,
            'contact_id' => $gid
        ])
        ->find();
        
        //检查邀请人是否在群聊中
        $checkMy = Db::table('im_groups')
        ->where([
            'user_id' => $this->user['id'],
            'contact_id' => $gid
        ])
        ->find();
        if($checkUser){
            throw new OperationFailureException("您邀请的人已在群聊中");
        }
        if(!$checkMy){
            throw new OperationFailureException("您不在当前群聊中");
        }
        
        Db::startTrans();
        try {
            //在im_msg_box插入相关通知
            $imId = Db::table('im_msg_box')
            ->insertGetId([
                'sender_id' => $this->user['id'],
                'send_date' => time(),
                'send_ip' => $this->user['last_login_ip'],
                'content' => $this->user['nick_username'].'邀请您加入群聊',
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
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->success('发送邀请失败，请稍后重试', '/' , $e->getMessage(), 0);
        }
        $this->error('发送邀请成功', '/' , null, 0);
    }
    
    /**
     * 移除群聊成员成员
     * @param int $gid
     * @param int $uid
     */
    public function deleteGroupMember($gid, $uid) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            SingletonServiceFactory::getContactService()->deleteGroupMember($this->user["id"], $gid, $uid);
            $reMsg = "操作成功";
        } catch (OperationFailureException $e) {
            $failure = true;
            $reMsg = $e->getMessage();
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
        
//         Db::startTrans();
//         try {
//             //群聊人数-1
//             $this->GroupsCount($gid, 0);
            
//             //删除im_groups对应关系数据
//             Db::table('im_groups')
//                 ->where([
//                     'user_id' => $uid,
//                     'contact_id' => $gid
//                 ])
//                 ->delete();
            
//             //查询被踢人昵称
//             $uUserName = Db::table('cmf_user')
//             ->where('id', $uid)
//             ->value('nick_username');
            
//             //查询该群所有管理员id
//             $adminIds = $this->queryGroupAdminById($gid);
            
//             //为所有管理员生成成员变动通知
//             $imId = Db::table('im_msg_box')
//             ->insertGetId([
//                 'sender_id' => 0,
//                 'send_date' => time(),
//                 'content' => $uUserName.'已被管理员强制请出该群聊'
//             ]);
//             foreach ($adminIds as $value) {
//                 Db::table('im_msg_receive')
//                 ->insert([
//                     'id' => $imId,
//                     'receiver_id' => $value['user_id'],
//                     'send_date' => time()
//                 ]);
//             }
//             Db::rollback();
//         } catch (\Exception $e) {
//             Db::rollback();
//             im_log("error", $e);
//             $this->success('噢噢~ 遇到问题了，请稍后重试', '/' , $e->getMessage(), 0);
//         }
//         $this->error('已将该成员移除群聊', '/' , null, 0);
    }

    /**
     * 解散群聊
     * @param int $gid 群聊id
     */
    public function deleteGroup($gid) {
        //查询解散者是否有权限
        $checkPower = Db::table('im_group')
        ->where([
            'id' => $gid,
            'admin_id' => $this->user['id']
        ])
        ->find();
        
        if(!$checkPower){
            throw new OperationFailureException("对不起，您没有该权限");
        }
        
        Db::startTrans();
        try {
            //修改群聊解散时间为3天后
            Db::table('im_group')
            ->where('id', $gid)
            ->update(['delete_time' => time()+3*60*60*24]);
            
            //为群聊中所有成员生成群聊解散消息
            $imId = Db::table('im_msg_box')
            ->insertGetId([
                'sender_id' => 0,
                'send_date' => time(),
                'content' => '群聊将在3天后解散'
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
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->success('噢噢~ 遇到问题了，请稍后重试', '/' , $e->getMessage(), 0);
        }
        $this->error('已提交申请，群聊将在3天后解散', '/' , null, 0);
    }
    
    /**
     * 更新群聊成员
     * @param int $gid 群聊id
     * @param int $uid 用户id
     * @param string $alias 用户别名
     * @param boolean $admin 是否为管理员 true:是|fasle:否
     */
    public function putGroupMember($gid, $uid = null, $alias = null, $admin = null) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            // 获取实际要更新的数据
            $data = [];
            if ($alias != null) {
                $data["alias"] = $alias;
            }
            if ($admin != null) {
                $data["admin"] = $admin;
            }
            if ($uid) {
                $data["id"] = $uid;
            } else {
                $data["id"] = $this->user["id"];
            }
            // 更新并获取返回值
            $reData = SingletonServiceFactory::getContactService()->updateGroupMember($this->user["id"], $gid, $data);
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
//         $data = null;
//         //改自己 或 改别人 或 抛出异常数据不正确
//         if($uid && $alias) {
//             $data = $this->updateGroupUserName($gid, $this->user['id'], $alias);
//         } else if(!$uid && !$alias){
//             //判断用户是否为管理员 如果是就判断自己是否是群主
//             if($admin){
//                 if($this->queryMyPermi($gid) == 2){
//                     $data = $this->updateGroupUserName($gid, $uid, $alias);
//                 }else{
//                     throw new OperationFailureException("对不起，您的权限不够");
//                 }
//             }else{
//                 $data = $this->updateGroupUserName($gid, $uid, $alias);
//             }
//         }else{
//             throw new OperationFailureException("对不起，数据有误");
//         }
//         $this->error('修改成功', '/', $data, 0);
    }
    
    /**
     * 修改群聊
     * @param int $gid 群聊 id
     * @param string $name 群聊名称
     * @param string $desc 群聊简介
     * @param string $avatar 群聊图像地址
     * @param int $admin 管理者 id
     */
    public function putGroup($gid, $name = null, $desc = null, $avatar = null, $admin = null){
        $res = null;
        
        if($this->queryMyPermi($gid) != 2){
            throw new OperationFailureException("对不起，您的权限不够");
        }
        
        Db::startTrans();
        try {
            Db::table('im_group')
            ->where(['id' => $gid])
            ->update([
                'groupname' => $name,
                'description' => $desc,
                'avatar' => $avatar,
                'admin_id' => $admin
            ]);
            
            $res = Db::table('im_group')
            ->where(['id' => $gid])
            ->field('id,groupname,description,avatar,create_time AS createtime,admin_id AS admin,
                    admin_count AS admincount,create_time AS createtime,member_count AS membercount')
            ->select();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->success('修改失败', '/', $e->getMessage(), 0);
        }
        $this->success('修改成功', '/', $res, 0);
    }
    
    
    /**
     * 查询联系人
     */
    public function getFriend($keyword = null, $id = null, $no = 1, $count = 10) {
        //如果参数不为空则模糊查询
        if($id){
            $userIds = Db::table('im_friends')
            ->where(['user_id' => $this->user['id']])
            ->field('contact_id')
            ->select();
            $res = Db::table('cmf_user')
            ->whereIn('id', $userIds, 'or')
            ->field('user_nickname AS username,id,avatar,signature AS sign,sex')
            ->page($no, $count)
            ->select()
            ->toArray();
        }else if(is_string($keyword)){
            $res = Db::table('cmf_user')
            ->where(['id' => $keyword])
            ->whereOr('user_nickname','like','%'.$keyword.'%')
            ->field('user_nickname AS username,id,avatar,signature AS sign,sex')
            ->page($no, $count)
            ->select()
            ->toArray();
        }
        $res = $this->checkOnOrOff($res);
        $this->error('', '/', $res, 0);
    }
    
    /**
     * 获取自己的所有好友分组
     */
    public function getFriendGroup($include = null) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            $reData = SingletonServiceFactory::getContactService()->getFriendAndGroup($this->user["id"], $include);
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
    }

    /**
     * 获取群聊, 不给请求参数就是获取自己的群聊
     */
    public function getGroup($keyword = null, $id = null, $no = 1, $count = 10, $include = false)
    {
        try {
            // 查询的群聊
            $group = null;
            
            if (is_numeric($id)) {
                $group = $this->service->getGroupById($id);
            } else if(is_string($keyword)) {
                // 通过关键字查询指定群聊
                $group = SingletonServiceFactory::getContactService()->getGroupByCondition($keyword, $no, $count);
                // 查询自己的群聊信息
            } else {
                $group = SingletonServiceFactory::getContactService()->getGroupByUser($this->user["id"], $include);
            }
            $this->error("", "/", $group, 0);
        } catch(OperationFailureException $e) {
            $this->success($e->getMessage(), "/", null, 0);
        } 
    }
    
    /**
     * 查询群聊成员
     * @param mixed $id 群聊的 id
     */
    public function getGroupMember($id){
        $groupId = $this->request->get('id');
        
        $group = new GroupModel();
        $owner = $group->alias('g')
            ->field("u.id, CASE u.avatar  WHEN '' THEN 'https://i.loli.net/2018/12/10/5c0de4003a282.png' END AS avatar, u.signature AS sign, u.user_nickname AS username")
            ->where(['g.id'=>$groupId])
            ->join(["cmf_user"=> "u"],'u.id = g.creator_id')
            ->limit(0, 1)
            ->select()
            ->toArray();
        
        $groups = new GroupsModel();
        $list = $groups->alias('g')
            ->field("u.id,  CASE u.avatar  WHEN '' THEN 'https://i.loli.net/2018/12/10/5c0de4003a282.png' END AS avatar, u.signature AS sign, u.user_nickname AS username")
            ->where(['g.contact_id'=>$groupId])
            ->join(["cmf_user"=> "u"],'u.id = g.user_id')
            ->select()
            ->toArray();
        
        $data = [
            "owner" =>$owner[0],
            'menbers'=>count($list),
            'list'=>$list,
        ];
        
        $this->error("", "/", $data, 0);
    }

    /**
     * 查找用户信息
     */
    public function getUser() {
        $msg = "";
        $user = "";
        try {
            // 尝试获取请求参数, 优先使用关键字然后才是 id.
            $keyword = isset($_GET["keyword"])? $_GET["keyword"]: null;
            $id = isset($_GET["id"])? $_GET["id"]: null;
            // 查找到的用户
            $user = null;
            if (is_string($keyword)) {
                $user = $this->service->findFriends($keyword);
            } else if ($id && is_numeric($id)) {
                $user = $this->service->getUserById($id);
            } else {
                $user = $this->service->getUserById($this->user["id"]);
            }
            $this->error("", "/", $user, 0);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
        }
        $this->success($msg, "/", null, 0);
    }
    
    /**
     * 为自己添加群聊
     */
//     public function postLinkGroup($id, $content) {
//         $msg = "";
//         try {
//             $ip = $this->request->ip();
//             $this->service->linkGroupMsg($this->user["id"], $id, $content, $ip);
//             $this->error("", "/", null, 0);
//         } catch (OperationFailureException $e) {
//             $msg = $e->getMessage();
//             $this->success($msg, "/", null, 0);
//         }
//         $this->error($msg, "/", null, 0);
//     }
    
    /**
     * 新建一个好友分组
     * @param mixed $groupname
     */
    public function postFriendGroup($groupname) {
        
        //验证特殊符号正则
        $preg_spacial="/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\/|\;|\\' | \`|\-|\=|\\\|\|/isu";
        
        if(!is_string($groupname) || preg_match($preg_spacial, $groupname)){
            $this->success("新分组名不能包含特殊字符", "/", null, 0);
        }
        
        //验证分组是否存在
        if(!$this->checkNullGroups(null,$groupname)){
            $this->success("分组已存在", "/", null, 0);
        }
        
        $msg = "";
        $data = null;
        try {
            $data = $this->service->createFriendGroup(cmf_get_current_user_id(), $groupname);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
            $this->success($msg, "/", null, 0);
        }
        $this->error($msg, "/", $data, 0);
    }
    
    /**
     * 为自己添加好友
     */
//     public function postLinkFriend($id, $friendGroupId, $content)
//     {
//         $msg = "";
//         try {
//             $ip = $this->request->ip();
//             $this->service->linkFriendMsg($this->user["id"], $friendGroupId, $id, $content, $ip);
//             $this->error("", "/", null, 0);
//         } catch (OperationFailureException $e) {
//             $msg = $e->getMessage();
//         }
//         $this->success($msg, "/", null, 0);
//     }

    /**
         * 为自己删除好友
     * @param int $id 好友id
     */
    public function deleteFriend($id) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            SingletonServiceFactory::getContactService()->deleteFriend($this->user["id"], $id);
            $reMsg = "删除成功";
        } catch(OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                $this->success($reMsg, "/", $reData, 0);
            } else {
                $this->error($reMsg, "/", $reData, 0);
            }
        }
//         Db::startTrans();
//         try{
//             //在im_msg_box和im_msg_receive加入系统通知
//             $imId = Db::table('im_msg_box')
//             ->insertGetId([
//                 'sender_id' => 0,
//                 'sender_date' => time(),
//                 'content' => $this->user['nick_username'].'已和您解除好友关系',
//             ]);
//             Db::table('im_msg_receive')
//             ->insert([
//                 'id' => $imId,
//                 'receiver_id' => $id,
//                 'send_date' => time()
//             ]);
            
//             //删除好友信息
//             Db::table('im_friends')
//             ->where([
//                 'user_id' => $this->user['id'],
//                 'contact_id' => $id
//             ])
//             ->delete();
            
//             Db::table('im_friends')
//             ->where([
//                 'user_id' => $id,
//                 'contact_id' => $this->user['id']
//             ])
//             ->delete();
            
//             //查询好友所在分组返回id
//             $oldgroupId = Db::table('im_friends')
//             ->where([
//                 'user_id' => $this->user['id'],
//                 'contact_id' => $id
//             ])
//             ->field('group_id')
//             ->select();
            
//             //旧分组人数-1
//             Db::table('im_friend_groups')
//             ->where([
//                 'id' => $oldgroupId
//             ])
//             ->dec('member_count')
//             ->update();
            
//             Db::commit();
//         }catch (\Exception $e){
//             Db::rollback();
//             $this->success($e->getMessage(), "/", null, 0);
//         }
//         $this->error("删除成功", "/", null, 0);
    }
    
    /**
         * 将好友更换分组
     * @param int $contact 好友id
     * @param int $group 分组id
     */
    public function putFriend($contact, $group=null, $alias=null)
    {
        if (is_string($alias)) {
            try {
                $data = SingletonServiceFactory::getContactService()->updateFriend($this->user["id"], ["id"=>$contact, "alias"=> $alias]);
                $this->error("", "/", $data, 0);
            } catch (OperationFailureException $e) {
                $this->success($e->getMessage(), "/", [], 0);
            }
        }
        
        //检查分组是否存在
        if($this->checkNullGroups($group,null)){
            $this->success("没有该分组,请检查您的分组信息", "/", null, 0);
        }
        
        Db::startTrans();
        try{
            //查询好友所在分组返回id
            $oldgroupId = Db::table('im_friends')
            ->where([
                'user_id' => $this->user['id'],
                'contact_id' => $contact
            ])
            ->value('group_id');
            
            //旧分组人数-1
            Db::table('im_friend_groups')
            ->where([
                'id' => $oldgroupId
            ])
            ->dec('member_count')
            ->update();
            
            //将好友更换分组
            Db::table('im_friends')
            ->where([
                'user_id' => $this->user['id'],
                'contact_id' => $contact
            ])
            ->update([
                'group_id' => $group
            ]);
            
            //新分组人数+1
            Db::table('im_friend_groups')
            ->where([
                'id' => $group
            ])
            ->inc('member_count')
            ->update();
            
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->success("噢噢~ 遇到问题了,请稍后重试", "/", null, 0);
        }
        $this->error("移动成功", "/", null, 0);
        
    }
    
    /**
         * 修改分组信息
     * @param int $id 分组id
     * @param string $name 新的名字
     */
    public function putFriendGroup($id, $name) {
        
        //检查分组是否存在
        if($this->checkNullGroups($id, null)){
            $this->success("没有该分组,请检查您的分组信息", "/", null, 0);
        }
        
        Db::startTrans();
        try {
            
            Db::table('im_friend_groups')
            ->where([
                'id' => $id,
                'user_id' => $this->user['id']
            ])
            ->update(['group_name' => $name]);
            
            Db::commit();
            $this->success("噢噢~ 遇到问题了,请稍后重试", "/", null ,0);
        } catch (\Exception $e) {
            Db::rollback();
        }
        
        $this->error("修改分组名成功", "/", null, 0);
    }
    
    /**
         * 验证分组是否存在 不存在返回true 公共方法
     * @param int $id 分组id
     */
    private function checkNullGroups($id, $groupname) {
        
        if($id){
            $nullGroups = Db::table('im_friend_groups')
            ->where(['id' => $id])
            ->find();
        }else if($groupname){
            
            $nullGroups = Db::table('im_friend_groups')
            ->where([
                'group_name' => $groupname,
                'user_id' => $this->user['id']
            ])
            ->find();
        }
        
        //验证是否有该分组 为空返回假
        if(!$nullGroups){
            return true;
        }
        return false;
    }
    
    /**
     * 根据群聊id查询所有管理员
     * @param int $gid 群聊id
     * @return array 管理员id
     */
    private function queryGroupAdminById($gid) {
        //查询出该群所有管理员的id
        return Db::table('im_groups')
        ->where([
            'contact_id' => $gid,
            'is_admin' => 1
        ])
        ->field('user_id')
        ->select();
    }
    
    /**
     * 群聊人数+1或者-1
     * @param int $gid 群聊id
     * @param integer $str 为空表示逻辑失败,0表示群聊人数-1|1表示群聊人数+1
     * @throws OperationFailureException 如果str为空抛出异常
     */
    private function GroupsCount($gid, $str = null) {
        if(!is_numeric($str)){
            throw new OperationFailureException("噢噢~ 遇到问题了,请稍后重试");
        } else if ($str == 0) {
            Db::table('im_group')
            ->where('id', $gid)
            ->dec('menber_count')
            ->update();
        } else {
            Db::table('im_group')
            ->where('id', $gid)
            ->inc('menber_count')
            ->update();
        }
    }
    
    /**
     * 查询自己的权限
     * @param int $gid
     * @return number 0为成员|1为管理员|2为创建者
     */
    private function queryMyPermi($gid) {
        $isAdmin = Db::table('im_group a,im_groups b')
        ->where('a.id = b.contact_id')
        ->where([
            'b.user_id' => $this->user['id'],
            'a.id' => $gid
        ])
        ->find();
        
        if($isAdmin['creator_id'] == $this->user['id']){
            return 2;
        }else if($isAdmin['is_admin'] == 1){
            return 1;
        }else{
            return 0;
        }
    }
    
    /**
     * 修改群成员名 并返回修改后的信息
     * @param int $gid 群聊id
     * @param int $id 用户id
     * @param string $name 用户名字
     */
    private function updateGroupUserName($gid, $id, $name) {
        //从缓存中取用户id是否在线
        $status = cache('im_chat_online_user_list');
        
        $data = null;
        Db::startTrans();
        try {
            Db::table('im_groups')
            ->where([
                'user_id' => $id,
                'contact_id' => $gid
            ])
            ->update(['user_alias' => $name]);
            
            $data = Db::table('cmf_user a,im_groups b')
                ->where('a.id = b.user_id')
                ->where(['a.id' => $id])
                ->where(['b.contact_id' => $gid])
                ->field('b.user_alias AS username,a.id,a.avatar,a.signature AS sign,a.sex,b.is_admin AS isadmin')
                ->find();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
        
        //默认为不在线
        $data['status'] = 'offline';
        
        //如果缓存中存在用户id就改成在线
        foreach ($status as $value) {
            if($data['id'] == $value){
                $data['status'] = 'online';
                break;
            }
        }
        
        return $data;
    }
    
    /**
     * 验证用户是否在线 并加入status字段
     * @param array $data
     */
    public function checkOnOrOff($data) {
        //从缓存中取用户id是否在线
        $status = cache('im_chat_online_user_list');
        
        //如果缓存中存在用户id就改成在线
        for ($i = 0; $i < count($data); $i++) {
            for ($j = 0; $j < count($status); $j++) {
                if($data[$i]['id'] == $status[$j]){
                    $data[$i]['status'] = 'online';
                    break;
                }else{
                    $data[$i]['status'] = 'offline';
                    continue;
                }
                
            }
        }
        return $data;
    }
}