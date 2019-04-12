<?php
namespace app\im\controller;

use think\Request;
use app\im\service\IMServiceImpl;
use think\Controller;
use app\im\exception\OperationFailureException;

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
     * 上传头像, 群组和用户的.
     *
     * @param mixed $file
     */
    public function postAvatar()
    {
        $file = $this->request->file("file");
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->validate([ 'ext' => 'jpg,png'])->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $this->error("", "/", $url, 0);
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
     * 获取自己的所有好友分组
     */
    public function getFriendGroup() {
        $groups = $this->service->getOwnFriendGroups($this->user["id"]);
        $this->error("", "/", $groups, 0);
    }

    /**
     * 获取群聊, 不给请求参数就是获取自己的群聊
     */
    public function getGroup()
    {
        // 请求失败的响应信息
        $msg = "";
        try {
            // 尝试获取请求参数, 优先使用关键字然后才是 id.
            $keyword = isset($_GET["keyword"])? $_GET["keyword"]: null;
            $id = isset($_GET["id"])? $_GET["id"]: null;
            // 查询的群聊
            $group = null;
            if (is_string($keyword)) {
                $group = $this->service->findGroups($keyword);
            } else if ($id && is_numeric($id)) {
                $group = $this->service->getGroupById($id);
            } else {
                $group = $this->service->findOwnGroups($this->user["id"]);
            }
            $this->error("", "/", $group, 0);
        } catch(OperationFailureException $e) {
            $msg = $e->getMessage();
        }
        $this->success($msg, "/", null, 0);
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
     * 删除群聊
     */
    public function deleteGroup()
    {}

    /**
     * 为自己添加群聊
     */
    public function postLinkGroup($id, $content) {
        $msg = "";
        try {
            $ip = $this->request->ip();
            $this->service->linkGroupMsg($this->user["id"], $id, $content, $ip);
            $this->error("", "/", null, 0);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
            $this->success($msg, "/", null, 0);
        }
        $this->error($msg, "/", null, 0);
    }
    
    /**
     * 新建一个好友分组
     * @param mixed $groupname
     */
    public function postFriendGroup($groupname) {
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
     * 为自己删除群聊
     */
    public function postUnlinkGroup()
    {}

    /**
     * 为自己添加好友
     */
    public function postLinkFriend($id, $friendGroupId, $content)
    {
        $msg = "";
        try {
            $ip = $this->request->ip();
            $this->service->linkFriendMsg($this->user["id"], $friendGroupId, $id, $content, $ip);
            $this->error("", "/", null, 0);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
        }
        $this->success($msg, "/", null, 0);
    }

    /**
     * 为自己删除好友
     */
    public function postUnlinkFriend()
    {
        
    }
}