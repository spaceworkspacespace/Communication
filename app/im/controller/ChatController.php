<?php 
namespace app\im\controller;

use app\im\exception\OperationFailureException;
use think\Controller;
use app\im\model\ModelFactory;
use app\im\model\RedisModel;
use app\im\service\IChatService;
use app\im\service\SingletonServiceFactory;

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
                        $chatService->requestCallComplete($sign, $success);
                        break;
                    case "finish": // 通话完成
                        if($error){
                            im_log("notice", "通话完成, 但发生了错误.", $errmsg);
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
    public function postOfflineProcessing($client_id) {
        $i = 0;
        $bool = false;
        $redis = RedisModel::getRedis();
        $model = ModelFactory::getChatFriendModel();
        $service = SingletonServiceFactory::getChatService();
        $isOnline = config("im.cache_chat_online_user_key");
        $userCall = json_decode($redis->rawCommand("HGETALL","im_call_calling_user_".$client_id."_hash"), true);
        if(empty($userCall)){
            return $this->error("成功");
        }
        $userIds = explode("-", $userCall['sign']);
        if($userIds[0] == $client_id){
            $client_id2 = $userIds[1];
        }else{
            $client_id2 = $userIds[0];
        }
        $data = $redis->rawCommand("SMEMBERS", $isOnline);
        foreach ($data as $value) {
            if($value === $client_id){
                $redis->rawCommand("SREM", $isOnline, $i);
                //结束通话
                if(count($userIds) == 3){
                    $bool = $service->requestFinish($client_id, $userIds[2]);
                }else{
                    $bool = $service->requestFinish($client_id, $userIds[1]);
                }
                $model->addInfo($client_id, $client_id2, "通话异常中断");
                $model->addInfo($client_id2, $client_id, "通话异常中断");
                $service->sendToUser($client_id, $client_id2, implode([
                    "json",json_encode([
                        "type"=>"call",
                        "data"=>[
                            "receiver_result"=>"success",//童话异常中断
                            "type"=>$userCall['ctype'],
                            "time"=>time()-$userCall['createTime']
                        ]
                    ])
                ]));
                break;
            }
            $i++;
        }
        return $bool?$this->error("成功"):$this->success("失败");
    }
    
    /**
     * 客户端的消息反馈, 说明已经收到信息
     * @param mixed $sign 如果是true对每个人查50条，如果是false 查50条
     */
    public function postFeedback($sign) {
        SingletonServiceFactory::getChatService()->messageFeedback(cmf_get_current_user_id(), $sign);
        return $sign;
    }
    
    /*
    public function sendMessage($id, $type, $content){
        $data=[];
        $data['type'] = 'chatMessage';
        $id = $str['to']['id'];
        if(isset($id)){
            switch ($str['to']['type']){
                case 'friend':
                    $str['mine']['type'] = $str['to']['type'];
                    $str['mine']['mine'] = false;
                    $data['data'][] = $str['mine'];
                    $this->friendchat($id,$str['mine']['content'],'friend');
                    if(Gateway::isUidOnline($id)){
                        GatewayServiceImpl::msgToUid($id, $data);
                        //聊天记录储存
                        $data = json_encode(['code'=>1,'id'=>$id]);
                    }else{
                        //聊天记录储存
                        $data = json_encode(['code'=>0,'type'=>'friend','id'=>$id]);
                    }
                    break;
                case 'group':
                    $data['uid'] = cmf_get_current_user_id();
                    $str['mine']['type'] = $str['to']['type'];
                    $str['mine']['mine'] = false;
                    $str['mine']['id'] = $id;
                    $data['data'][] = $str['mine'];
                    $this->friendchat($id,$str['mine']['content'],'group');
                    GatewayServiceImpl::msgToGroup($id, $data);
                    //聊天记录储存
                    $data = json_encode(['code'=>1,'type'=>'group','id'=>$id]);
                    break;
                default:
                    break;
            }
            return $data;
        }
    }
    
    //保存好友聊天记录
    public function friendchat($id,$str,$type){
        $arr['send_ip'] = get_client_ip(0, true);
        $arr['sender_id'] = cmf_get_current_user_id();
        $arr['send_date'] = date('Y-m-d H:i:s');
        $arr['content'] = $str;
        if($type == 'group'){
            $arr['group_id'] = $id;
            $chat = new ChatGroupModel();
        }else if($type == 'friend'){
            $arr['receiver_id'] =$id;
            $chat = new ChatUserModel();
        }
        $chat->save($arr);
    }
    */
}