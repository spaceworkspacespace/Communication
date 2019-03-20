<?php
namespace app\im\controller;

use think\Request;
use traits\controller\Jump;
use app\im\service\IMServiceImpl;
use think\Controller;

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

    protected $test = 0;

    public function __construct(Request $request)
    {
        $this->user = cmf_get_current_user();
        parent::__construct($request);
        $this->service = new IMServiceImpl();
        
    }

    public function getTest()
    {
        im_log("debug", "调用 test 成功.");
        return $this->test;
    }

    protected function checkUserLogin()
    {
        $isLogin = $this->user && $this->user["id"];
        im_log("info", "用户登录验证: ", $isLogin, ", user: ", $this->user, ", ", cmf_get_current_user());
//         im_log("debug", $this);
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
     * 新建一个分组
     *
     * @param string $groupname
     * @param mixed $avatar
     * @param string $description
     */
    public function postGroup(string $groupname, $avatar, string $description)
    {}

    /**
     * 获取所有分组
     */
    public function getGroup()
    {
        $groups = $this->service->getOwnFriendGroups($this->user["id"]);
        $this->error("", "/", $groups, 0);
        // return json_encode($groups);
    }

    /**
     * 删除分组
     */
    public function deleteGroup()
    {}

    /**
     * 为自己添加分组
     */
    public function postLinkGroup()
    {}

    /**
     * 为自己删除分组
     */
    public function postUnlinkGroup()
    {}

    /**
     * 为自己添加好友
     */
    public function postLinkFriend()
    {}

    /**
     * 为自己删除好友
     */
    public function postUnlinkFriend()
    {}
}