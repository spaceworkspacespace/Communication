<?php 
namespace app\im\controller;

use app\im\exception\OperationFailureException;
use think\Controller;
use app\im\service\IChatService;
use app\im\service\SingletonServiceFactory;
use think\Request;

class ChatController extends Controller{
    protected $beforeActionList = [
        "checkUserLogin"
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
    
//     public function getIndex() {
//         return $this->fetch("/chatlog");
//     }
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
//         return $cid;
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
    $sign=null, $unread=null, $replay=null,
    $description=null, $call=null) {
        $failure = false;
        $message = "";
        $data = [];
        //         $bool = false;
        $chatService = SingletonServiceFactory::getChatService();
        try {
            // 请求通话
            if ($stage == null) {
                if (!is_string($chatType) || is_null($id) || !is_string($type)) {
                    throw new OperationFailureException("聊天对象未确定.");
                }
                switch($chatType) {
                    case "group":
                        $bool = $chatService->requestCallWithGroup($this->userId, $id, $type);
                        break;
                    case "friend":
                        $bool = $chatService->requestCallWithFriend($this->userId, $id, $type);
                        break;
                    default:
                        throw new OperationFailureException("聊天对象未确定");
                }
            }else{
                switch ($stage){
                    case "replay"://请求应答
                        $bool = $chatService->requestCallReply($this->userId,$sign, $replay, $unread);
                        break;
                    case "exchange"://交换描述
                        if($call == null){
                            $bool = $chatService->requestCallUserExchange($this->userId, $sign, $description);
                        }else{
                            $key = key($call);
                            $bool = $chatService->requestCallGroupExchange($this->userId,$key, $call[$key]);
                        }
                        break;
                    case "complete"://连接完成
                        $bool = $chatService->requestCallReply($this->userId, $sign, $replay, $unread);
                        break;
                    default:
                        throw new OperationFailureException("请求错误！");
                        break;
                }
            }
            return $bool?$this->error("成功"):$this->success("失败1");
        } catch (OperationFailureException $e) {
            $message = $e->getMessage();
            echo $message;
        }
    }

//     public function postCall($stage=null,
//             $id=null, $chatType=null, $type=null,
//             $sign=null, $unread=null, $replay=null,
//             $description=null, $call=null) {
//             $failure = false;
//             $message = "";
//             $data = [];
//             $chatService = SingletonServiceFactory::getChatService();
//             try {
//                 switch($stage){
//                     //请求通话
//                     case null:
//                         if (!is_string($chatType) || is_null($id) || !is_string($type)) {
//                             throw new OperationFailureException("聊天对象未确定.");
//                         }
//                         switch($chatType) {
//                             case "group":
//                                 $chatService->requestCallWithGroup($this->userId, $id, $type);
//                                 break;
//                             case "friend":
//                                 $chatService->requestCallWithFriend($this->userId, $id, $type);
//                                 break;
//                             default:
//                                 throw new OperationFailureException("聊天对象未确定.");
//                         }
//                         break;
//                     //请求应答
//                     case "reply":
//                         if(!is_null($unread) && !$unread){
//                             throw new OperationFailureException("消息未发送成功，请稍后再试.");
//                         }
                        
//                         if(is_null($replay)){
//                             throw new OperationFailureException("网络请求出现错误.");
//                         }
                        
//                         $chatService->requestResponse($replay);
//                         break;
//                     //交换描述
//                     case "exchange":
//                         break;
//                     //连接完成
//                     case "complete":
//                         break;
//                     default:
//                         throw new OperationFailureException("出现了一个未知的错误.");
//                 }
//             } catch (OperationFailureException $e) {
//                 $message = $e->getMessage();
//                 $failure = true;
//             }
//         }
    
    /**
     * 发送聊天信息
     * @param  mixed $id 发送者 id
     * @param mixed $type 消息类型
     * @param string $content 消息内容
     */
    public function postMessage($id, $type, $content){
        $msg = "";
        // im_log("debug", "新的消息 $id $type $content");
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
    
   
    
    /**
     * 客户端的消息反馈, 说明已经收到信息
     * @param mixed $sign 如果是true对每个人查50条，如果是false 查50条
     */
    public function postFeedback($sign) {
        SingletonServiceFactory::getChatService()->messageFeedback(cmf_get_current_user_id(), $sign);
        return $sign;
    }
    
//     /**
//      * 查询聊天记录
//      * @param string $type 聊天记录类型
//      * @param int $id 用户id 群聊id
//      * @param boolean $separately
//      * @param number $no 页码 
//      * @param number $count 页数
//      * @return array
//      */
//     private function getMessages($type, $id, $separately, $no, $count) {
//         $data = null;
//         //判断类型
//         if($type == 'friend'){
//             //判断id是否存在 存在则指定查询好友 不存在则查询和所有好友的聊天记录
//             if($id){
//                 $data = Db::table('im_chat_user a,cmf_user b,im_friends c')
//                 ->where('a.sender_id = b.id')
//                 ->where('a.sender_id = c.user_id')
//                 ->where('((a.receiver_id = '.$this->userId.' AND a.sender_id = '.$id.') OR (a.receiver_id = '.$id.' AND a.sender_id = '.$this->userId.'))')
//                 ->order('a.send_date', 'desc')
//                 ->field('a.id AS cid,a.send_date AS `date`,a.content,b.id,c.contact_alias AS username,b.avatar')
//                 ->page($no, $count)
//                 ->select();
//             }else{
//                 //如果为true查询所有好友各50条 如果为false查询和所有好友最近的50条
//                 if($separately){
//                     //查询出所有的好友id
//                     $contactids = Db::table('im_friends')
//                     ->where(['user_id' => $this->user['id']])
//                     ->column('contact_id');
                    
//                     $data = null;
//                     foreach ($contactids as $value) {
//                         $res = Db::table('im_chat_user a,cmf_user b,im_friends c')
//                         ->where('a.sender_id = b.id')
//                         ->where('a.sender_id = c.user_id')
//                         ->where('((a.receiver_id = '.$this->user['id'].' AND a.sender_id = '.$value.') OR (a.receiver_id = '.$value.' AND a.sender_id = '.$this->user['id'].'))')
//                         ->order('a.send_date', 'desc')
//                         ->field('a.id AS cid,a.send_date AS `date`,a.content,b.id,c.contact_alias AS username,b.avatar')
//                         ->page($no, $count)
//                         ->select();
                        
//                         //合并数组
//                         $data = array_merge($data, $res);
//                     }
//                 }
//             }
//         }else if($type == 'group'){
//             if($id){
//                 $data = Db::table('im_chat_group a')
//                 ->join(['cmf_user' => 'b'],'b.id = a.sender_id','LEFT')
//                 ->join(['im_groups' => 'c'],'c.contact_id = a.group_id AND b.id = c.user_id','LEFT')
//                 ->join(['im_group' => 'd'],'d.id = a.group_id','LEFT')
//                 ->field('a.id AS cid,a.send_date AS `date`,a.content,b.id AS uid,c.user_alias AS username,d.id AS gid,d.groupname,d.avatar AS gavatar')
//                 ->where(['a.group_id' => $id])
//                 ->order('a.send_date', 'desc')
//                 ->page($no, $count)
//                 ->select();
//             }else{
//                 if($separately){
//                     //查询出所有的群组id
//                     $contactids = Db::table('im_groups')
//                     ->where(['user_id' => $this->user['id']])
//                     ->column('contact_id');
                    
//                     $data = null;
//                     foreach ($contactids as $value) {
//                         $res = $data = Db::table('im_chat_group a')
//                         ->join(['cmf_user' => 'b'],'b.id = a.sender_id','LEFT')
//                         ->join(['im_groups' => 'c'],'c.contact_id = a.group_id AND b.id = c.user_id','LEFT')
//                         ->join(['im_group' => 'd'],'a.group_id = d.id','LEFT')
//                         ->field('a.id AS cid,a.send_date AS `date`,a.content,b.id AS uid,c.user_alias AS username,d.id AS gid,d.groupname,d.avatar AS gavatar')
//                         ->where(['a.group_id' => $value])
//                         ->order('a.send_date', 'desc')
//                         ->page($no, $count)
//                         ->select();
                        
//                         //合并数组
//                         $data = array_merge($data, $res);
//                     }
//                 }
//             }
//         }
//         return $data;
//     }
    

    
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