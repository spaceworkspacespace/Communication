<?php 
namespace app\im\controller;

use app\im\exception\OperationFailureException;
use think\Controller;
use app\im\model\RedisModel;
use app\im\service\IChatService;
use app\im\service\SingletonServiceFactory;
use think\Cache;

class ChatController extends Controller{
    protected $beforeActionList = [
        "checkUserLogin"=>["except"=>"postofflineprocessing"]
    ];
    
    private $userId;
    
    public function _initialize() {
            parent::_initialize();
            $this->userId = cmf_get_current_user_id();
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
    
    public function deleteMessage($cid, $type) {
        $msg = "";
        $data = null;
        try {
            SingletonServiceFactory::getChatService()
                ->hiddenMessage(cmf_get_current_user_id(), $cid, $type !== "friend"? IChatService::CHAT_GROUP:  IChatService::CHAT_FRIEND);
            $msg = "删除成功";
        } catch(OperationFailureException $e) {
            $msg = $e->getMessage();
            $this->success($msg, "/", $data, 0);
        }
        $this->error($msg, "/", $data, 0);
    }
    
    public function getCallMembers($groupId=null) {
        $reMsg = '';
        $failure=false;
        $reData = null;
        
        $callService = SingletonServiceFactory::getCallService();
        try {
            $callUserField = RedisModel::getKeyName("im_calling_comm_group_hash", ["groupId"=>$groupId]);
            if (!is_null($groupId) && RedisModel::exists($callUserField)) {
                $reData = $callService->getMembersByGroupId(cmf_get_current_user_id(), $groupId);
                $reData = [$reData];
            }
        } catch(OperationFailureException $e) {
            $failure = true;
            $reMsg = $e->getMessage();
        }
        
        if ($failure) {
            $this->success($reMsg, '/', $reData, 0);
        } else {
            $this->error($reMsg, '/', $reData, 0);
        }
    }
    
    public function getIceServer() {
        $user = cmf_get_current_user();
        // var_dump($user);
        $iceConfig = config("im.iceserver");
        $iceConfig["iceServers"] = array_map_with_index($iceConfig["iceServers"], function($value) use($user) {
            if (str_starts_with($value["urls"], "turn")) {
                return array_merge($value, ["username"=>$user["user_nickname"], "credential"=>""]);
            }
            return $value;
        });
        $this->error("", "/", $iceConfig, 0);
    }
    
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
            $service = SingletonServiceFactory::getChatService();
            if (trim($type) != "friend") {
                $data = [
                    "id"=> $this->userId,
                    "records"=>$service->readChatGroup($id, $pageNo, $pageSize)->toArray(),
                ];
            } else {
                $data = [
                    "id"=> $this->userId,
                    "records"=>$service->readChatUser($this->userId, $id, $pageNo, $pageSize)->toArray()
                ];
            }
            $this->error($msg, "/", $data, 0);
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
        }
        $this->success($msg, "/", $data, 0);
    }
    
    /**
     * 获取消息
     * @param int $type 会话的类型 friend | group
     * @param int $id 用户id或群聊id
     * @param int $no 页码
     * @param int $count 页数
     * @param boolean $separately
     */
    public function getMessage($type = null, $id = null, $no = 1, $count = 50, $separately = true) {
        $message = "";
        $reData = [];
        $failure = false;
        
        try {
            if ($type != null ){
                $type = $type != "group"? IChatService::CHAT_FRIEND: IChatService::CHAT_GROUP;
            }
                
            $reData = SingletonServiceFactory::getChatService()->getMessage(
                $this->userId,
                $no,
                $count,
                $type,
                $id);
        } catch(OperationFailureException $e) {
            $failure = true;
            $message = $e->getMessage();
        }
        if ($failure) {
            $this->success($message, "/", $reData, 0);
        } else {
            $this->error('', '/', $reData, 0);
        }
    }
    
    public function postCallReconnect($sign, $userId, $connectid) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        
        $chatService = SingletonServiceFactory::getChatService();
        try {
            $chatService->requestCallReconnection($sign, cmf_get_current_user_id(), $userId, $connectid);
        } catch (OperationFailureException $e) {
            $failure = true;
            $reMsg = $e->getMessage();
        }

        if ($failure) {
            $this->success($reMsg, "/", $reData, 0);
        } else {
            $this->error($reMsg, "/", $reData, 0);
        }
    }
    
    public function postCall($stage=null,
        $id=null, $chatType=null, $type=null,
        $sign=null, $unread=null, $reply=null,
        $description=null, $call=null, $success=null,
        $ice = null, $error = null, $errmsg = null) {
        
        $reMsg = "";
        $reData = null;
        $failure = false;
        
        $chatService = SingletonServiceFactory::getChatService();
        try {
            // 请求通话
            if ($stage == null) {
                if (!is_string($chatType) 
                    || is_null($id) 
                    || !is_string($type)) {
                    throw new OperationFailureException("聊天对象未确定.");
                }
                switch($chatType) {
                    case "group":
                        $reData = $chatService->requestCallWithGroup($this->userId, $id, $type);
                        break;
                    case "friend":
                        $reData = $chatService->requestCallWithFriend($this->userId, $id, $type);
                        break;
                    default:
                        throw new OperationFailureException("聊天对象未确定");
                }
            } else {
                switch ($stage){
                    case "reply"://请求应答
                        $chatService->requestCallReply($this->userId,$sign, $reply, $unread);
                        break;
                    case "exchange": // 交换描述
                    case "exchange-ice": // 交换ice
                        $chatService->requestCallExchange([ 
                            "sign"=>$sign, 
                            "userId"=>cmf_get_current_user_id(), 
                            "desc"=>$description, 
                            "ice"=> $ice, 
                            "call"=> $call ]);
                        break;
                    case "complete"://连接完成
                        $chatService->requestCallComplete(cmf_get_current_user_id(), $sign, $success);
                        break;
                    case "finish": // 通话完成
                        if($error){
                            im_log("notice", "通话完成, 但发生了错误: ", $errmsg);
                        }
                        $chatService->requestCallFinish($this->userId, $sign, $error);
                        break;
                    default:
                        throw new OperationFailureException("请求错误！");
                        break;
                }
            }
        } catch (OperationFailureException $e) {
            $failure = true;
            $reMsg = $e->getMessage();
        } 
        
        if ($failure) {
            $this->success($reMsg, "/", $reData, 0);
        } else {
            $this->error($reMsg, "/", $reData, 0);
        }
    }

    /**
     * 发送聊天信息
     * @param  mixed $id 发送者 id
     * @param mixed $type 消息类型
     * @param string $content 消息内容
     */
    public function postMessage($id, $type, $content){
        $msg = "";
        $reData=null;
        $service = SingletonServiceFactory::getChatService();
        try {
            switch(trim($type)) {
                case "friend":
                    $reData = $service->sendToUser($this->userId, $id, $content, $this->request->ip());
                    break;
                case "group":
                    $reData = $service->sendToGroup($this->userId, $id, $content, $this->request->ip());
                    break;
            }
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
            $this->success($msg, "/", null, 0);
        }
        $this->error($msg, "/", $reData,0);
    }
    
    //掉线处理 结束通话
    public function postOfflineProcessing($userId, $message=null) {
        $reMsg = "";
        $reData = null;
        $failure = false;
        
        $user_h = RedisModel::getKeyName("user_h", ["userId"=>$userId]);
        $calling_l = RedisModel::getKeyName("calling_l");
        $calling_h = RedisModel::getKeyName("calling_h");
        
        $cache = Cache::store("redis");
        im_log("notice", RedisModel::get("test"));
        try {
            
            if (!RedisModel::exists($user_h)) {
                im_log("error", "对 ", $user_h, " 进行掉线处理, 但没有此 redis key.");
                // 删除
              
                if ($cache->lock($calling_l, 15)  && $cache->lock($calling_h, 15)) {
                    println(implode(" ", ["离线处理", $calling_l, $calling_h, $userId]));
                    // println("删除 $calling_l ", RedisModel::lrem($calling_l,  $userId, 0));
                    // println("删除 $calling_h ", RedisModel::hdel($calling_h, $userId));
                    RedisModel::lrem($calling_l,  $userId, 0);
                    RedisModel::hdel($calling_h, $userId);
                    // RedisModel::lrem($calling_l,  $userId, 0);
                    // RedisModel::hdel($calling_h, $userId);
                }
                $cache->unlock($calling_l);
                $cache->unlock($calling_h);
                throw new OperationFailureException();
            }
            if (is_null($message)) {
                $message = lang("network error");
            }
            
            $chatService = SingletonServiceFactory::getChatService();
            // 群聊或双人聊天的掉线分别处理.
            // 群聊有 g 属性
            if (RedisModel::hexists($user_h, "g")) {
                $g = RedisModel::hgetJson($user_h, "g");
                $chatService->requestCallFinish($userId, $g["sign"], $message);
            } else {
                $others = RedisModel::hgetallJson($user_h);
                array_for_each($others, function($other, $key) use ($chatService, $userId, $message) {
                    if (is_numeric($key)) {
                        $chatService->requestCallFinish($userId, $other["sign"], $message);
                        $chatService->requestCallFinish($key, $other["sign"], $message);
                    }
                });
            }
        } catch (OperationFailureException $e) {
            $reMsg = $e->getMessage();
            $failure = true;
        }
        if ($failure) {
            $this->success($reMsg, "/", $reData, 0);
        } else {
            $this->error($reMsg, "/", $reData, 0);
        }
    }
    
    /**
     * 客户端的消息反馈, 说明已经收到信息
     * @param mixed $sign 如果是true对每个人查50条，如果是false 查50条
     */
    public function postFeedback($sign) {
        SingletonServiceFactory::getChatService()->messageFeedback(cmf_get_current_user_id(), $sign);
        return $sign;
    }
}