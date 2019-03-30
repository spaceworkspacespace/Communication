<?php
namespace app\im\controller;

use think\Controller;
use app\im\service\IMServiceImpl;

class UserController extends Controller {
    protected $beforeActionList = [
        "checkUserLogin"
    ];
    
    private $service;
    private $userId;
    
    public function _initialize() {
        parent::_initialize();
        $this->service = IMServiceImpl::getInstance();
        $this->userId = cmf_get_current_user_id();
    }
    
    protected function checkUserLogin()
    {
        $isLogin = cmf_get_current_user_id();
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
     * 更新用户信息
     */
    public function postInfo() {
        
        $success = $this->service->updateUser($this->userId, [
            "signature"=> $_POST["sign"]
        ]);
        
        if ($success) {
            $this->error("更新成功 !", "/", null, 0);
        } else {
            $this->success("更新失败 !", "/", null, 0);
        }
    }
}