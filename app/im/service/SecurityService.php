<?php
namespace app\im\service;

use app\im\util\RSAUtils;
use think\Hook;
use app\im\util\AutoSerial;
use app\im\model\RedisModel;

/**
 * 管理所有密码以及进行加密解密的便利方法.
 * @author silence
 *
 */
class SecurityService  
{
    // 用在 redis hash 中的 key.
    public const KEY_NAME = "im_keys";
    private static $instance;
    
    private  $redis;

    protected function __construct()
    {
        $this->redis = RedisModel::getRedis();
    }

    /**
     * 获取单例对象
     *
     * @param array $config
     * @return \app\im\service\SecurityService
     */
    public static function getInstance($config = [])
    {
        if (!static::$instance) static::$instance = new static();
//         $obj->configuration($config);
        return static::$instance;
    }

    /**
     * 获取用户密码
     *
     * @param mixed $userId
     * @return string|mixed
     */
    public function getUserKey($userId)
    {
        $key = "user-$userId";
//         im_log("debug", $key, ", ", config("im.keys_name"));
        if($value = $this->redis->rawCommand("HGET", static::KEY_NAME, $key)) {
            return $value;
        } else {
            $value = "user-$userId-im-".time();
            $this->redis->rawCommand("HSET", static::KEY_NAME, $key, $value);
            return $value;
        }
    }

    public function getGroupKey($groupId)
    {
        $key = "group-$groupId";
//         im_log("debug", "HGET ".config("im.keys_name")." $key");
        
        if($value = $this->redis->rawCommand("HGET", static::KEY_NAME, $key)) {
            return $value;
        } else {
            $value = "group-$groupId-im-".time();
//             im_log("debug", "HSET ".config("im.keys_name")." $key \"$value\"");
            $this->redis->rawCommand("HSET", static::KEY_NAME, $key, $value);
//             im_log("debug", $result);
            return $value;
        }
    }

    /**
     * 设置用户的密码
     *
     * @param mixed $userId
     * @param string $key
     */
    public function setUserKey($userId, $key)
    {
        $field = "user-$userId";
        $this->redis->rawCommand("HSET", static::KEY_NAME, $field, $key);
    }

    /**
     * 设置群聊的密码
     *
     * @param mixed $userId
     * @param string $key
     */
    public function setGroupKey($groupId, $key)
    {
        $field = "group-$groupId";
        $this->redis->rawCommand("HSET", static::KEY_NAME, $field, $key);
    }

    /**
     * 加密内容
     *
     * @param string $text
     * @return string
     */
    public function encrypt($text, $key): string
    {
        return openssl_encrypt($text, "aes-256-ctr", md5($key), OPENSSL_ZERO_PADDING, str_split(md5($key), 16)[0]);
    }
    
    public function encryptWithUserId($text, $userId): string {
        return $this->encrypt($text, $this->getUserKey($userId));
    }
    
    public function encryptWithGroupId($text, $groupId): string {
        return $this->encrypt($text, $this->getGroupKey($groupId));
    }

    public function decrypt($text, $key): string
    {
        return openssl_decrypt($text, "aes-256-ctr", md5($key), OPENSSL_ZERO_PADDING, str_split(md5($key), 16)[0]);
    }
    
    public function decryptWithUserId($text, $userId): string {
        return $this->decrypt($text, $this->getUserKey($userId));
    }
}