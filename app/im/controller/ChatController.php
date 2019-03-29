<?php 
namespace app\im\controller;

use app\im\exception\OperationFailureException;
use traits\controller\Jump;
use think\Controller;
use app\im\service\IMServiceImpl;

class ChatController extends Controller{
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
    
//     public function getIndex() {
//         return $this->fetch("/chatlog");
//     }
    
    /**
     * 获取聊天记录
     * @param string $type
     * @param mixed $id
     */
    public function getRecord($type, $id) {
        $msg = "";
        $data = null;
        $pageNo = isset($_GET["no"])? $_GET["no"]: 0;
        $pageSize = isset($_GET["size"])? $_GET["size"]: 100;
        try {
            if (trim($type) !== "friend ") {
                $data = [
                    "id"=> $this->userId,
                    "records"=>$this->service->readChatGroup($id, $pageNo, $pageSize)->toArray(),
                ];
            } else {
                $data = [
                    "id"=> $this->userId,
                    "records"=>$this->service->readChatUser($this->userId, $id, $pageNo, $pageSize)->toArray()
                ];
            }
            $this->error($msg, "/", $data, 0);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
        }
        $this->success($msg, "/", $data, 0);
    }
}