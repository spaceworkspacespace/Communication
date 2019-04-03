<?php 
namespace app\im\controller;

use app\im\exception\OperationFailureException;
use traits\controller\Jump;
use think\Controller;
use app\im\service\IMServiceImpl;
use app\im\service\GatewayServiceImpl;
use GatewayClient\Gateway;

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
            if (trim($type) != "friend") {
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
    
    /**
     * 发送聊天信息
     * @param  mixed $id 发送者 id
     * @param mixed $type 消息类型
     * @param string $content 消息内容
     */
    public function postMessage($id, $type, $content){
        $msg = "";
        // im_log("debug", "新的消息 $id $type $content");
        try {
            switch(trim($type)) {
                case "friend":
                    $this->service->sendToUser($this->userId, $id, $content, $this->request->ip());
                    break;
                case "group":
                    $this->service->sendToGroup($this->userId, $id, $content, $this->request->ip());
                    break;
            }
        } catch (OperationFailureException $e) {
            $msg = $e->getMessage();
            $this->success($msg, "/", null, 0);
        }
    }
    
    /**
     * 聊天图片, 群组和用户的.
     * @param mixed $file
     */
    public function postMessagePicture()
    {
        $file = $this->request->file("file");
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->validate([ 'ext' => 'jpg,png,gif'])->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $this->error("", "/", ["src"=> $url], 0);
    }
    
    /**
     * 客户端的消息反馈, 说明已经收到信息
     * @param mixed $sign
     */
    public function postMessageFeedback($sign) {
        $this->service->messageFeedback(cmf_get_current_user_id(), $sign);
        return $sign;
    }
    
    /**
     * 聊天文件, 群组和用户的.
     * @param mixed $file
     */
    public function postMessageFile()
    {
        $file = $this->request->file("file");
//         im_log("debug", $file->getInfo()["name"]);
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $fileName = isset($file->getInfo()["name"])?
            $file->getInfo()["name"]:$info->getSaveName();
        $this->error("", "/", ["src"=>$url, "name"=>$fileName], 0);
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