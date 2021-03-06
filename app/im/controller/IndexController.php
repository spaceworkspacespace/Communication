<?php
namespace app\im\controller;

use think\Config;
use cmf\controller\HomeBaseController;
use app\im\model\ChatGroupModel;
use GatewayClient\Gateway;
use app\im\service\IMServiceImpl;
use app\im\model\GroupModel;
use app\im\model\GroupsModel;
use app\im\model\ChatUserModel;
use app\im\service\SecurityService;
use app\im\service\GatewayServiceImpl;


class IndexController extends HomeBaseController
{
    
//     private $service = null;

    public function _initialize()
    {
        // $this->chatsModel = new ChatGroupModel();
        parent::_initialize();
        // 如果不是访问首页就检查用户登录
        // if (! preg_match("/(index$|init$|log$)/", $_SERVER["REQUEST_URI"])) {
        // $this->checkUserLogin();
        // }
        // 初始化 gateway
//         $remote = Config::get("gateway.remote");
//         if (is_string($remote)) {
//             Gateway::$registerAddress = $remote;
//         } else {
//             throw new \Exception("Gateway 地址未设置 !");
//         }
        // service
//         $this->service = IMServiceImpl::getInstance();
   //         $this->checkUserLogin();
    }

    public function index() {
        return $this->fetch("/i2");
    }
    
    public function index1()
    {
        // if ($this->request->isMobile()) {
        // return $this->fetch("/mobile", ["socket"=>config("gateway.client_connect")]);
        // }
        // return $this->fetch("/index", ["socket"=>config("gateway.client_connect")]);
        // IndexController::$number
        //  self::$number + 1;
        //  return self::$number;
        return $this->fetch("/i1");
    }
    
    public function index2() {
        return $this->fetch("/i2");
    }
    public function index3() {
        return $this->fetch("/i3");
    }
    
    /**
     * 退出登录
     */
    public function logout()
    {
        var_dump("123");
        session("user", null);//只有前台用户退出
        return redirect($this->request->root() . "/");
    }
    
//     public function find()
//     {
//         if ($this->request->isMobile()) {
//             return $this->fetch("/mobile-find");
//         }
//         return $this->fetch("/find");
//     }

//     public function msgbox()
//     {
//         return $this->fetch("/msgbox");
//     }

//     public function chatLog()
//     {
//         return $this->fetch("/chatlog");
//     }

//     public function newGroup() {
//         return $this->fetch("/newgroup");
//     }
    
//     /**
//      * 获取通信基本信息
//      * @access public
//      */
//     public function init()
//     {
//         // 查询当前用户的好友以及分组信息.
//         $user = cmf_get_current_user();
//         $info = null;
//         // 从当前会话中获取登录的用户, 补充用户信息.
//         if (is_array($user) && is_numeric($user["id"])) {
//             im_log("info", "id: ", $user["id"], "用户信息返回.");
//             $info = $this->service->init($user["id"]);
            
//         } else {
//             im_log("info", "游客信息返回.");
//             $info = [
//                 "mine" => [
//                     "username" => "游客",
//                     "id" => time(),
//                     "status" => "online",
//                     "sign" => "随便看看.",
//                     "avatar" => ""
//                 ]
//             ];
//         }
//         // im 前端框架要求返回 0 为成功, success 方法返回的是 1.
//         $this->error("", "/", $info, 0);
//     }
    

//     public function contacts() {
//         // 获取请求的方法
//         $demand = $_POST["demand"];
//         // 将要响应的数据
//         $resp = null;
        
//         switch ($demand) {
//             default:
//                 $this->success("未知资源 !", "/", null, 0);
//             break;
//             case "query":
//                 $condition = $_POST["condition"];
//                 $keyword = $_POST["keyword"];
//                 if (($condition !== "friends" &&
//                     $condition !== "groups") || 
//                     !is_string($keyword)) {
//                         $this->success("未知资源 !", "/", null, 0);
//                     }
//                 $keyword = addslashes($keyword);
                
//                 switch ($condition) {
//                     case "friends":
//                         $resp = $this->service->findFriends($keyword);
//                         break;
//                     case "groups":
//                         $resp = $this->service->findGroups($keyword);
//                         break;
//                 }
//                 break;
//             case "POST":
//                 break;
//         }
//         $this->error("", "/", $resp, 0);
//     }
    
//     public function file() {
//         // 获取请求的方法
//         $demand = $_POST["demand"];
//         // 将要响应的数据
//         $resp = null;
        
//         switch ($demand) {
//             default:
//                 $this->success("未知资源 !", "/", null, 0);
//                 break;
//         }
//     }

//     public function members()
//     {}

    

//     public function imgClick()
//     {
//         return $this->fetch("/image_click");
//     }
}