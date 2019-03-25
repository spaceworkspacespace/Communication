<?php
namespace app\im\controller;

use think\Config;
use cmf\controller\HomeBaseController;
use app\im\model\ChatGroupModel;
use GatewayClient\Gateway;
use app\im\service\IMServiceImpl;

class IndexController extends HomeBaseController
{
    private $service = null;

    public function _initialize()
    {
        // $this->chatsModel = new ChatGroupModel();
        parent::_initialize();
        // 如果不是访问首页就检查用户登录
        // if (! preg_match("/(index$|init$|log$)/", $_SERVER["REQUEST_URI"])) {
        // $this->checkUserLogin();
        // }
        // 初始化 gateway
        $remote = Config::get("gateway.remote");
        if (is_string($remote)) {
            Gateway::$registerAddress = $remote;
        } else {
            throw new \Exception("Gateway 地址未设置 !");
        }
        // service
        $this->service = new IMServiceImpl();
    }

    public function index()
    {
        return $this->fetch("/index", []);
        // IndexController::$number
        //  self::$number + 1;
        //  return self::$number;
    }
    
    public function find()
    {
        return $this->fetch("/find");
    }

    public function msgbox()
    {
        return $this->fetch("/msgbox");
    }

    public function chatLog()
    {
        return $this->fetch("/chatlog");
    }

    public function newGroup() {
        return $this->fetch("/newgroup");
    }
    
    /**
     * 获取通信基本信息
     * @access public
     */
    public function init()
    {
        // 查询当前用户的好友以及分组信息.
        $user = cmf_get_current_user();
        $info = null;
        // 从当前会话中获取登录的用户, 补充用户信息.
        if (is_array($user) && is_numeric($user["id"])) {
            im_log("info", "id: ", $user["id"], "用户信息返回.");
            $info = $this->service->init($user["id"]);
            array_index_copy($user, $info["mine"], "user_nickname", "avatar");
            array_key_replace($info["mine"], [
                "user_nickname"=>"username",
                "user_id" => "id"
            ]);
        } else {
            im_log("info", "游客信息返回.");
            $info = [
                "mine" => [
                    "username" => "游客",
                    "id" => time(),
                    "status" => "online",
                    "sign" => "随便看看.",
                    "avatar" => ""
                ]
            ];
        }
        // im 前端框架要求返回 0 为成功, success 方法返回的是 1.
        $this->error("", "/", $info, 0);
    }
	
	public function getgroup(){
        $data['code'] = 0;
        $data['msg'] = '';
        $groupId = $this->request->get('id');
        $group = new GroupModel();
        $owner = $group->alias('g')->where(['g.id'=>$groupId])->join('user_entire u','u.id = g.creator_id')->select()->toArray();
        $groups = new GroupsModel();
        $list = $groups->alias('g')->where(['g.contact_id'=>$groupId])->join('user_entire u','u.id = g.user_id')->select()->toArray();
        $data['data'] = array(
            "owner" => array_map(function ($item) {
                $friends = array_index_pick($item, "user_nickname", "id", "avatar", "sign");
                array_key_replace($friends, [
                    "user_nickname" => "username"
                ]);
                return $friends;
            }, $owner)[0],
            'menbers'=>count($list),
            'list'=>array_map(function ($item) {
                $friends = array_index_pick($item, "user_nickname", "id", "avatar", "sign");
                array_key_replace($friends, [
                    "user_nickname" => "username"
                ]);
                return $friends;
            }, $list),
        );
        
        return json_encode($data);
    }
	
	/**
     * 发送消息
     */
    public function send($str){
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
                        Gateway::sendToUid($id, json_encode($data));
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
                    $str['mine']['mine'] = true;
                    $str['mine']['id'] = $id;
                    $data['data'][] = $str['mine'];
                    $this->friendchat($id,$str['mine']['content'],'group');
                    Gateway::sendToGroup($id, json_encode($data));
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
    
    
    //绑定
    public function bind($client_id){
        $uid = cmf_get_current_user_id();
        Gateway::bindUid($client_id, $uid);
        $group = $this->service->findOwnGroups($uid);
        foreach ($group as $key){
            Gateway::joinGroup($client_id,$key['contact_id']);
        }
        $this->error();
    }
    
    public function contacts() {
        // 获取请求的方法
        $demand = $_POST["demand"];
        // 将要响应的数据
        $resp = null;
        
        switch ($demand) {
            default:
                $this->success("未知资源 !", "/", null, 0);
            break;
            case "query":
                $condition = $_POST["condition"];
                $keyword = $_POST["keyword"];
                if (($condition !== "friends" &&
                    $condition !== "groups") || 
                    !is_string($keyword)) {
                        $this->success("未知资源 !", "/", null, 0);
                    }
                $keyword = addslashes($keyword);
                
                switch ($condition) {
                    case "friends":
                        $resp = $this->service->findFriends($keyword);
                        break;
                    case "groups":
                        $resp = $this->service->findGroups($keyword);
                        break;
                }
                break;
            case "POST":
                break;
        }
        $this->error("", "/", $resp, 0);
    }
    
    public function file() {
        // 获取请求的方法
        $demand = $_POST["demand"];
        // 将要响应的数据
        $resp = null;
        
        switch ($demand) {
            default:
                $this->success("未知资源 !", "/", null, 0);
                break;
        }
    }

    public function members()
    {}

    

    public function imgClick()
    {
        return $this->fetch("/image_click");
    }
}