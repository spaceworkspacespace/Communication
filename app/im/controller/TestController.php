<?php
namespace app\im\controller;
use GatewayClient\Gateway;
use think\cache\driver\Redis;
use think\Config;
use think\Controller;
use cmf\controller\HomeBaseController;
use think\Cache;

class TestController extends Controller {
    public function getInfo()
    {
        phpinfo();
    }
    
    
    public function getIndex() {
        $str = "你好啊 ?";
        dump(str_split($str, 2));
    }
    

    
    public function getLog()
    {
         return $this->fetch("/log");
    }
    public function postLog($client_id) {
        if (is_string($client_id)) {
            Gateway::bindUid($client_id, Config::get("gateway.remote_log_uid"));
        }
    }
}