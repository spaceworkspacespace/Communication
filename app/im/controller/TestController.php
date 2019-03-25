<?php
namespace app\im\controller;
use GatewayClient\Gateway;
use think\cache\driver\Redis;
use think\Config;
use think\Controller;
use cmf\controller\HomeBaseController;
use think\Cache;
use think\Hook;
use app\im\util\AutoSerial;


class TestController extends Controller {
    public function getInfo()
    {
        phpinfo();
    }
    
    public function getIndex() {
        dump(TestBean::getInstance()->test);
    }
    
    public function getBean() {
        $bean = TestBean::getInstance();
        return $bean->__toString();
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

/**
 * 
 * @author silence
 * @method static TestBean getInstance()  获取单例对象
 */
class TestBean extends AutoSerial {
    public  const serialVersionUID = "im_serial_TestBean";
    
    private $number = 10;
    private $tag = "你好啊";
    
    public static $test = 11;
    
    
    public static  function test() {
        return 112;
    }
    
    
    public function setNumber($number) {
        $this->number = $number;
        return $this;
    }
    
    public function setTag($tag) {
        $this->tag = $tag;
        return $tag;
    }
    
    public function __toString() {
        return implode([
            get_class($this),
            ": ",
            $this->tag,
            ", ",
            $this->number,
            "."
        ]);
    }
}