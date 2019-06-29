<?php
namespace app\im\controller;
use GatewayClient\Gateway;
use think\cache\driver\Redis;
use think\Config;
use think\Controller;
use think\Db;
use cmf\controller\HomeBaseController;
use think\Cache;
use think\Hook;
use app\im\util\AutoSerial;
use app\im\util\RSAUtils;
use app\im\service\SecurityService;
use app\im\service\IMServiceImpl;
use app\im\model\RedisModel;
use think\Queue;
use app\im\service\SingletonServiceFactory;
use app\im\model\ModelFactory;


class TestController extends Controller {
    
    public function getArray() {
//         $array = [1, 2, 3, 4];
//         $result = array_combination($array, 3);
        $array = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
//         $array = [1, 2, 3, 4, 5, 6];
//         $array = [1, 2, 3, 4];
        $m = 6;
        // 取组合
        $result = array_combination($array, $m);
        echo "size: ".count($result);
        echo "<br />";
        echo json_encode($result);
        echo "<br />";
        echo "<br />";
        // 取排列
        $result = array_permutation($array, $m);
        echo "size: ".count($result);
        echo "<br />";
        echo json_encode($result);
    }
    
    public function getCache() {
        Cache::store("redis")->set("test", "123");
        var_dump(Cache::store("redis"));
        var_dump(Cache::store("redis")->handler());
        
        var_dump(Cache::store("redis") === Cache::store("redis"));
        
        var_dump(RedisModel::getRedis(true));
        var_dump(RedisModel::getRedis(true)->rawCommand("GET", "test"));
    }
    
    public function getInfo()
    {
        phpinfo();
    }
    
    public function getFun() {
//         return config("im.im_calling_comm_user_hash");
        $plus = function($l, $r) {
            return $l + $r;
        };
        
        $max = function($a, $b, $c) {
            return $a > $b? ($a > $c? $a: $c): ($b > $c? $b: $c);            
        };
        $minus = function ($l, $r) {
            return $l - $r;
        };
        
        $cminus = function_curry($minus);
        
        $m3 = $cminus(F_P_, 3);
//         var_dump($m3);
        echo $m3(6);
        echo "<br />";
        echo $cminus(2, 4);
        echo "<br />";
        echo $cminus(F_P_)(3)(1);
        echo "<br />";
//         $cplus = function_curry($plus);
//         var_dump($p);
//         $p = $cplus(1);
//         var_dump($p(2));
//         var_dump($p(3));
//         var_dump($p(1));
//         var_dump($p(-1));
//         var_dump($cplus(3, 2));
//         var_dump($cplus(4, 5));
//         $cmax = function_curry($max);
//         var_dump($cmax(4, 3, 2));
//         $m = $cmax(5);
//         var_dump($m(3, 6));
//         var_dump($m(7)(-1));
    }
    public function getLang() {
        return lang("invalid user");
    }
    
    public function getCall() {
        var_dump(SingletonServiceFactory::getCallService()->test(1));
    }
    
    public function getQstr() {
        var_dump($_GET["ary"]);
    }
    
    public function postJson() {
        var_dump(file_get_contents('php://input'));
    }
    
    public function getDb() {
//         var_dump(Db::connect(config("database")) === Db::connect(config("database")));
        var_dump(ModelFactory::getUserModel()->existAll(1, 2, 100));
    }
    public function getHeader (){
//         var_dump(getenv("REMOTE_ADDR"));
//         var_dump(getenv("REMOTE_PORT"));
        var_dump(request()->url());
    }
    public function putT($name, $id=null) {
        if ($id) return $id;
        return $name;
    }
    
    public function getReq() {
        var_dump($this->request->param("_ajax"));
        var_dump($_GET["_ajax"]);
    }
    public function getSql() {
//         var_dump(IMServiceImpl::getInstance()->readChat
//         $res = IMServiceImpl::getInstance()->getUnreadMessage(1, 2, IMServiceImpl::CHAT_FRIEND)->toJson();
//         var_dump(IMServiceImpl::getInstance()->getUnreadMessage(1, 2, IMServiceImpl::CHAT_FRIEND));
//         var_dump(IMServiceImpl::getInstance()->getUnreadMessage(1, 1, IMServiceImpl::CHAT_GROUP));
//         return $res;
        // var_dump(model("chat_group")->getUnreadMessageByMsgId(1, [34, 33, 35, 36] ));
//         var_dump(model("chat_user")->getUnreadMessageByMsgId(1, [0, 55, 66, 24, 33, 71, 68, 74]));
        var_dump(model("friends")->isFriend(2, 1));
    }
    public function getUser() {
//         echo microtime(true);
        var_dump(is_numeric("321"));
    }
    
    public function getRedis() {
        
        /**
         * @var \app\im\util\RedisCacheDriverImpl $cache
         */
        $cache = Cache::store("redis");
        
        $v = $cache->hvals("test-h");
        var_dump($v);
        // sleep(6);
//         $v = $cache->unlock("g");
//         var_dump($v);
        
    }
    
    public function getDecrypt() {
        $security = SecurityService::getInstance();
//         $text = "fiN7s+ftMGRePqx+uOAeP1EVv6jHKhYBDwz8M9DfOa/jn4kgxnOz3UAvsR45DRSIkZh+BxB2mBtdrL+FPURJgb6TvG4F8ekdGoMCeswcgTNdD6qbuiFoug3p/oq+FSxDwPdN9k/k4hejuBZHb+xJFIqLN6wIzshJu1U5nVB6MwWDUbHSSX5scrQhmYO9rfnB0zlkOWkg86YPBezXg8NZ/N7YCaPaMf8nL9C5WIPLBEoZfrtQUnVaCLJxYilIIepFVw==";
//         var_dump($security->decryptWithUserId($text, 1));
        $text = '{"type":"MESSAGE","data":"{\"type\":\"chatMessage\",\"uid\":2,\"data\":[{\"username\":\"user\",\"avatar\":\"\",\"id\":\"21\",\"mine\":true,\"content\":\"321\",\"type\":\"group\"}]}"}';
        $key = "group-21-im-1553910450";
        $encrypt = $security->encrypt($text, $security->getGroupKey(21));
        $encryptGroupId = $security->encryptWithGroupId($text,  21);
        var_dump($encrypt);
        var_dump($encryptGroupId);
        $decrypt = $security->decrypt($encrypt, $key);
        var_dump($decrypt);
    }
    
    public function getIndex() {
//         $security = SecurityService::getInstance();
//         $text = "NjM++aW5IW9Of/81msw8Ww==";
//         $key = "user-1-im-1553751780";
//         var_dump($security->getUserKey(1));
//         var_dump($security->decrypt($text, $key));
        
    }
    
    public function postIndex() {
//         var_dump();
        var_dump($_SERVER);
        $key = "123456";
        $text = '快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好 友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.';
        //         $encrypted = "U2FsdGVkX19GO3Y+qyNYc9Wgmzxb841vIQEyXti87j5Liaht3QtOgTiVN0VC9MnNqloqBIY+UArdX4kyeyEKIIsN/N2nQ+p4ffI4qMeQtafD00uLqqPEwPGKIFsVd+3ozFTGRhGHS+rJXSfbiwKt4xJs+8yYNTsnhEH9JgysBwCwUeg/KH4k6gP4TzJfWq14Z315G0LlvOIKBX8emPIzRlCQ+a56Dksl8Qgli2Z8dlDwnLapZ/tjtqtVZHN26BqRNam2cbNE/UJfYG57U3heZTe+89eASiqZzNKoJv/rul5FrABDcAL00reS63j3HzT9opRf6qbGZuwBwpk2Xbw6O9zSjq0nopP5YHntLEQzHx+iVRMqUylMXOtHwKQyN2oSGDRApa1hD/3VJkcydgAgBiq7SSSxMt2i4XDPfY0oRg1DFbhNYbUcyH+9OZCPr68o0qMTqmGTX5VoyAM3LsD7KMG6iTrt+eQ8Pmy8JF3+NCbGfBgIee9rPilLltTi/OE8pqvTMdiN8auk/g6uuIIV+FRrzLNFsm0tgBBHDlNelBSXjE8+67BBg/CnPtShB8PtsAQHF68q2/sIXoakUvCocECddZd872JlvOQvL/ario7jemzlwYJsRKl9jDOCvw7Gd4cSTipBC9WA9N/628XkL2pCp6Uv6yfmcILxJ37RKIp+6XYeM4Skd+4lzfipsOsStyDrlQTA7O9K0Y0d7EhTaDw/QAsGwxG7birDOL6CfO+xS4YFgKHbwcg9ei6q1Jnzi6mNkznGuBLu4TBY+96o8RuSS5/3PitpSQZFCv8S5X5ij0bO+rUiAwWbj8cZqKVzMR9oFGC3THfFe8B/d3cm7mZQ7UcY5Hs4PorwbzkXxvd2Q5iS0AyPr3DLI79UnGIYy7S6gK6inkzXRxe/ktdVx4WxvhtlocmClb9Zp1xas/Uj/UDv8mtb3KQTtnjxTCCd";
        $encrypted = openssl_encrypt($text, "aes-256-ctr", md5($key), OPENSSL_ZERO_PADDING, str_split(md5($key), 16)[0]);
        //
        var_dump($encrypted);
        $text = openssl_decrypt($encrypted, "aes-256-ctr", md5($key), OPENSSL_ZERO_PADDING, str_split(md5($key), 16)[0]);
        
        var_dump($text);
        //         return json_encode(openssl_get_cipher_methods());
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
   
    public function getClassName() {
        var_dump(static::class);
        var_dump(self::class);
        var_dump(get_class($this));
    }

    public function test() {
//         var_dump(serialVersionUID);
    }
    public function println() {
        var_dump("hello");
    }
    
    public function isDeclareConstant(string $name): bool {
        $class = get_class($this);
        $declareClass = (new \ReflectionClass($this))
            ->getReflectionConstant($name)
            ->getDeclaringClass()
            ->getName();
        return $class === $declareClass;
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

/**
 *
 * @author silence
 * @method static TestBean2 getInstance()  获取单例对象
 */
class TestBean2 extends TestBean {
    public $name = 10;
    public function __construct() {
        
    }
    public function setName($name) {
        $this->name = $name;
    }
    public function println() {
        var_dump("Hi");
    }
}
