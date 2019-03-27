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
use app\im\util\RSAUtils;
use app\im\service\SecurityService;


class TestController extends Controller {
    public function getInfo()
    {
        phpinfo();
    }
    
    public function getIndex() {
//         $service = SecurityService::getInstance();
//         $text = "213333333333333333333333333333333333333333213333333333333333333333333333333333333333213333333333333333333333333333333333333333213333333333333333333333333333333333333333213333333333333333333333333333333333333333";
//         $str = $service->encrypt("你好吗?");
//         echo RSAUtils::decrypt($str, $service->getPublicKey(), RSAUtils::PUBLIC_KEY_TYPE);
//         $str = RSAUtils::encrypt($text, $service->getPublicKey(), RSAUtils::PUBLIC_KEY_TYPE);
//         echo $service->decrypt($str);
//         var_dump(RSAUtils::genKeyPair());
         $privateKey = <<<"PKEND"
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMTFTGGbwHMqsLCv
km6cca/OFbD25X0rQIqSVxZx49jnbTGMQBiJJQYzYj43oobACsoddhnBOmFxXykX
LrLMx7+K+vEovWXU4jmpuAoH2g9yhGhc0NlgRNE9DgFjN+eDSgcy7lQ1AnAgqTZE
E51vf8ITHOJh4d9WSk2R4j1Xb0C5AgMBAAECgYAH/thesvx78YUieM/jbLn14dLh
0PZ8QpCp0M53HAOdIbI/LCrClHgLq3TXgF07SnxlwBK3czGTGg861TVRkJ6hKKAi
kJDTkjYAx4A8rkmdtgt72M8xDEN+ytVibTF9AN4IfgtojtxdyrFYbUXncgsZ/Zpv
cyXIoHmTYfO9wcbAAQJBAOEl/EKNc1aQSxYPd/QMKwECxWY6kKmXaXSh4ecfUKsI
bzAdie6Px9YpUENiiACQ9oTAl/As/19coKfWtFwq+AECQQDfu9kMst25rK6BGnhD
dRD8NNZdoxVV0e4k4m/Ls6GsapuPd2znqzdtGBBvBQi/ujUjOnJ5qb7GK4CL63R0
ogi5AkEAn7TqM+tSnVzNZmCiniLjflwQ2mtAoowc6fbK379+4VOiS2coqGilQG0d
2i7SelRaCeDz5hKFM4fpDiVm2tpAAQJAaHNGTYtjwD9B6Lv20Wdh2pzAR07PsxUi
3M1p6+uc2uWaYkwa570jTycg5POwtfG0xRGQSARbMCE3DhuKbrkG4QJAZmtLR9Lm
e+1hLu3SasAteeHYMaBRJB0MSR7ed4aPJHmzEeGYoVNnDHJnscj8XjXwuAbs7DsY
dhiLFho0leZ5AA==
-----END PRIVATE KEY-----

PKEND;
 $publicKey = <<<"PKEND"
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDExUxhm8BzKrCwr5JunHGvzhWw
9uV9K0CKklcWcePY520xjEAYiSUGM2I+N6KGwArKHXYZwTphcV8pFy6yzMe/ivrx
KL1l1OI5qbgKB9oPcoRoXNDZYETRPQ4BYzfng0oHMu5UNQJwIKk2RBOdb3/CExzi
YeHfVkpNkeI9V29AuQIDAQAB
-----END PUBLIC KEY-----

PKEND;
 $text = '快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好 友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.,快点同意我的好友申请啦, 求求你.';
        
        $privateText = RSAUtils::encrypt($text, $privateKey, RSAUtils::PRIVATE_KEY_TYPE);
//         var_dump($privateText);
        $privateText = base64_encode($privateText);
        var_dump($privateText);
        $privateText = base64_decode($privateText);
        var_dump(RSAUtils::decrypt($privateText, $publicKey, RSAUtils::PUBLIC_KEY_TYPE));
        
        $publicText = "OMQ/bk9KNUeBeMRpmFgfsdb3H3dJcolt1WzaCaPXp03nOBBSnzKov4+wqy1IxyaDj0VLdfwM/g5aKufhPcp2DJf9BDzoUY1HmZyadrK6gRBS9QZ9IBYpYOV49FMBaAkNnQ6p55K6XrLXjpKzKSES5gbiCfHN6l2qc45q65CFT3ovuHAlHmEkqKiz2acdMGVvfTxP8K9nvybal1/dZeUQKwOSFcnL+Kni6Lm4X7ec0YXODP5Z5RzJCNXOrCEajrBV9rrgE/umLxx2glCWIKd17qWXw8RO223OnUqP7lTHxa0bxeTg/N+mqDFCelpldQkEvJsQnJFinwBIogHF15OH6LnAnGm5wDgccPjxvCuNmBODAcpevralNz2YQ4aY+A8dD1CyiDJToPbuXOqXx156k5bofkuLKrUZ6hwy9XRChabUgAfdeE4zvoSDQZX76CUpIoYkUxHlgF8NEYnpjAwTvLhAMKvJr+LDq1U6wrztU4B4yzSbR+ukAzQ1Q5lP00dVD6eiAQ/lffb6S/gKg0sqh9ZSGE2ouGA7Ewo30pEGCdh57OvwwJpaWbQlF8hiNJ+hCzD7xe0fFPMZWM8xPNik7KWz4WomDZ3dtxJeRB9VmZJrbgelwCNzx3aFfQEQqis80RqviDWDqPG/2WvpiH0oXWTO+6nroBdbw7tfvB4zFRBjJSWFQlLQKwELrb4NodFcR45cOAaXpw1AjJGoSyrXDVssgqyi8AeHQMd4UUnVF1Db5COp+fGuEo1YsVdnn2UlpugtSNkxqfmoz2YvjVa1Kh9Z2Y0FfOQQeCSUpFj3sYvkC+FYhpYo2+FbCZuyC1Ihf1o87+frB5dt8DbYhc0hJm2r/QtTSvhlGAEXKrbIJIeuetTKVWM63b7NUgvUklJaAqTN9KrbpiI+chnQIdjaf29ADo/iFb/S88BZro6PJ1TtqY1s6rbfZVta0HrcFFTNRI8QFLw6GXABxHq1iCEFzfzyee25QgaQd84/RtUwyIVtWqcU6z94TLSkAJYQe09EHGoGtMTtj6tHIzPpjTWiZrSxKpIxeSL+oeQa93xTqp+UYZPDa267Bpx22SaZdYO/SNsTgUawO0RiVp5HTpa3j/2UbhbFJuN3PcEK1DYaeyVqBd7dK6Uq08hFTXgyswWeyipoK7dtoYIf3Ue9LnYZsqSjsPqOFEbwsAHR9f+VH3o=";
        var_dump(RSAUtils::decrypt(base64_decode($publicText), $privateKey, RSAUtils::PRIVATE_KEY_TYPE));
        
    }
    public function postIndex() {
//         var_dump();
        var_dump($_SERVER);
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