<?php
namespace app\im\controller;

use think\Controller;
use app\im\service\IMServiceImpl;
use GatewayClient\Gateway;
use app\im\service\SecurityService;
use app\im\model\RedisModel;
use app\im\service\SingletonServiceFactory;
use app\im\exception\OperationFailureException;

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
                $this->success("您尚未登录", cmf_url("user/Login/index"));
            } else {
                $this->redirect(cmf_url("user/Login/index"));
            }
        }
    }
    
    /**
     * 绑定 gatewayworker 客户端 id 和 uid, 并响应公钥.
     * @param mixed $client_id
     */
    public function postBind($clientId){
        $uid = cmf_get_current_user_id();
        Gateway::bindUid($clientId, $uid);
        $group = SingletonServiceFactory::getContactService()->getGroupByUser($uid);
        
        // 加密对象
        $security = SecurityService::getInstance();
        // 用户所持有的密钥
        $keys = ["default"=>$security->getUserKey($uid)];
        $keys["u-$uid"] = $keys["default"];
        
        foreach ($group as $key){
            $groupId = $key['id'];
            Gateway::joinGroup($clientId,$groupId);
            $keys["g-$groupId"] = $security->getGroupKey($groupId);
        }
        // 返回给用户持有的密钥
        $this->error("", "/", [
            "id"=>cmf_get_current_user_id(),
            "ks"=>base64_encode(json_encode($keys))
        ], 0);
    }
    
    
    /**
     * 获取用户信息
     */
    public function getInfo() {
        // 在线处理
        $cache = RedisModel::getRedis();
        $cache->rawCommand("SADD", config("im.cache_chat_online_user_key"), $this->userId);
//         var_dump(cmf_get_current_user());
        $user = cmf_get_current_user();
        $user = [
                'birthday'=> $user["birthday"],
                'account'=> $user["last_login_time"], // 账号 user_login
                'username'=>$user["user_nickname"], // 用户名 user_nickname 没有时为 user_login
                'usertype'=>$user["user_type"] !==1?  "会员" : "admin",
                'id'=>$user["id"], // id
                'avatar'=> $user["avatar"], // 头像地址
                'sign'=>$user["signature"], // 签名信息
                'status'=>"online" , // 是否在线
                'sex'=>$user["sex"] !== 1?($user["sex"] !== 0?"女":"保密"): "男",
                'lastlogintime'=>$user["last_login_time"], // 最后登录时间
                'createtime'=>$user["create_time"], // 注册时间
                'useremail'=>$user["user_email"], // 邮箱
                'mobile'=>$user["mobile"], // 手机号码
            ];
        $this->error("", "/", $user, 0);
    }
    
    /**
     * 更新用户信息
     */
    public function putInfo($id, $sign=null) {
        $reMessage = "";
        $reData = [];
        $failure = false;
        try {
            if ($id != $this->userId) {
                $failure = true;
                $reMessage = "修改失败.";
                return;
            }
            $reData = SingletonServiceFactory::getUserService()->updateUser($this->userId,  [
                "sign"=>$sign
            ]);
            
        } catch(OperationFailureException $e){
            $reMessage = $e->getMessage();
            $failure = true;
        } finally {
            if ($failure) {
                // 防止没有设置消息的情况
                if (count($reMessage) == 0) {
                    $reMessage="修改失败.";
                }
                $this->success($reMessage, "/", $reData, 0);
            } else {
                $this->error($reMessage, "/", $reData, 0);
            }
        }
    }
    
}