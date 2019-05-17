<?php
namespace app\im\controller;

use think\Request;
use app\im\service\IMServiceImpl;
use think\Controller;
use app\im\exception\OperationFailureException;
use think\Db;
use app\im\model\GroupModel;
use app\im\model\GroupsModel;
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
                $this->error("您尚未登录", cmf_url("user/Login/index"));
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
     * 退出群聊
     * @param int $gid 群聊id
     */
    public function deleteMyGroup($gid) {
        try {
            SingletonServiceFactory::getContactService()->deleteMyGroup($gid, $this->user);
        } catch (\Exception $e) {
            $this->success("退出群聊失败", "/", $e->getMessage(), 0);
        }
        $this->error('已退出群聊', '/', null, 0);
    }
    
    /**
     * 邀请加入群聊
     * @param int $gid 群聊id
     * @param int $uid 用户id
     */
    public function postGroupMember($gid, $uid) {
        try {
            SingletonServiceFactory::getContactService()->postGroupMember($gid, $uid, $this->user);
        } catch (\Exception $e) {
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
        
    }

    /**
     * 解散群聊
     * @param int $gid 群聊id
     */
    public function deleteGroup($gid) {
        try {
            SingletonServiceFactory::getContactService()->deleteGroup($gid, $this->user);
        } catch (\Exception $e) {
            $this->success($e->getMessage(), '/' , null, 0);
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
        try {
            $res = SingletonServiceFactory::getContactService()->putGroup($gid, $name, $desc, $avatar, $admin, $this->user);
        } catch (\Exception $e) {
            $this->success('修改失败', '/', $e->getMessage(), 0);
        }
        $this->success('修改成功', '/', $res, 0);
    }
    
    
    /**
     * 查询联系人
     */
    public function getFriend($keyword = null, $id = null, $no = 1, $count = 10) {
        try {
            $res = SingletonServiceFactory::getContactService()->getFriend($keyword, $id, $no, $count);
            $res = $this->checkOnOrOff($res);
        } catch (\Exception $e) {
            im_log("error", $e);
            $this->success('查询失败', '/', $e->getMessage(), 0);
        }
        $this->error('查询成功', '/', $res, 0);
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
    }
    
    /**
         * 将好友更换分组
     * @param int $contact 好友id
     * @param int $group 分组id
     */
    public function putFriend($contact, $group=null, $alias=null)
    {
        $service = SingletonServiceFactory::getContactService();
        if (is_string($alias)) {
            try {
                $data = $service->updateFriend($this->user["id"], ["id"=>$contact, "alias"=> $alias]);
            } catch (OperationFailureException $e) {
                $this->success($e->getMessage(), "/", [], 0);
            }
            $this->error("修改成功", "/", $data, 0);
        }else{
            try{
                $service->putFriend($contact, $group, $this->user);
            }catch (\Exception $e){
                $this->success($e->getMessage(), "/", null, 0);
            }
            $this->error("移动成功", "/", null, 0);
        }
    }
    
    /**
         * 修改分组信息
     * @param int $id 分组id
     * @param string $name 新的名字
     */
    public function putFriendGroup($id, $name) {
        $rsdate = null;
        
        try {
            $rsdate = SingletonServiceFactory::getContactService()->putFriendGroup($id, $name);
        } catch (\Exception $e) {
            $this->success("修改分组名失败", "/", $e->getMessage() ,0);
        }
        $this->error("修改分组名成功", "/", $rsdate, 0);
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
     * 验证用户是否在线 并加入status字段
     * @param array $data
     */
    private function checkOnOrOff($data) {
        
        if(!$data){
            return null;
        }
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
            if($data[$i]['sex'] == 0){
                $data[$i]['sex'] = '保密';
            }else if($data[$i]['sex'] == 1){
                $data[$i]['sex'] = '男';
            }else{
                $data[$i]['sex'] = '女';
            }
        }
        return $data;
    }
}