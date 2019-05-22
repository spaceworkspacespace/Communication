<?php
namespace app\im\controller;

use think\Controller;
use think\Request;
use app\im\service\GatewayServiceImpl;
use app\im\service\IMServiceImpl;
use app\im\exception\OperationFailureException;
use app\im\service\SingletonServiceFactory;

class MsgboxController extends Controller {
    private $service = null;
    private $user = null;
    
    protected $beforeActionList = [
        "checkUserLogin"
    ];
    
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->user = cmf_get_current_user();
        $this->service = IMServiceImpl::getInstance();
    }
    
    protected function checkUserLogin()
    {
        $isLogin = cmf_get_current_user_id();
        im_log("info", "用户登录验证: ", $isLogin);
        
        if (!$isLogin) {
            if ($this->request->isAjax()) {
                $this->success("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }
    }
    
    public function getIndex($page = 1, $count=150) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            $reData = SingletonServiceFactory::getMessageService()->getMessage($this->user["id"], $page, $count);
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
         * 消息处理
     * @param int $id 消息id
     * @param int $id2 我给发送人设置的分组id
     * @param int $action 0:拒绝||1:同意
     */
    public function postIndex($id, $id2 = null, $_action) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        try {
            switch ($_action) {
                case 0:
                    SingletonServiceFactory::getMessageService()
                        ->negativeHandle($this->user["id"], $id, [$id2]);
                    $reMsg = "处理成功";
                    break;
                case 1:
                    SingletonServiceFactory::getMessageService()
                        ->positiveHandle($this->user["id"], $id, [$id2]);
                    $reMsg = "处理成功";
                    break;
                default:
                    $failure = true;
                    $reMsg = "参数不正确";
                    break;
            }
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
     * 消息删除
     * @param int $id 消息id
     */
    public function deleteIndex($id) {
        try {
            SingletonServiceFactory::getMessageService()->deleteIndex($id);
        } catch (\Exception $e) {
            $this->success('删除失败', '/', null, 0);
        }
        $this->error('删除成功', '/', null, 0);
    }
    
    /**
     * 消息已读处理
     * @param int $id 消息id
     */
    public function postFeedBack($id) {
        //更改im_msg_receive的read为以读
        try {
            SingletonServiceFactory::getMessageService()->postFeedBack($id);
        } catch (\Exception $e) {
            $this->success('失败', '/', null, 0);
        }
        $this->error('成功', '/', null, 0);
    }
    
    public function addToUid($uid, $data) {
        
        GatewayServiceImpl::addToUid($uid, $data);
        
    }
    
    /**
     * 初始化完成. 表示客户端已准备就绪.
     * 初始化接口请求顺序:
     *  init -> bind -> finish
     */
    public function postPull() {
        $userId = cmf_get_current_user_id();
        //         $this->service->pushMsgBoxNotification($userId);
        $this->service->pushAll($userId);
    }
    
    
}