<?php
namespace app\im\service;

use app\im\util\RSAUtils;
use think\Hook;
use app\im\util\AutoSerial;

/**
 * 管理所有密码以及进行加密解密的便利方法.
 * @author silence
 *
 */
class SecurityService extends AutoSerial
{

    // 储存所有密码
    private $keys = [
        "user" => [], // 存放用户 key
        "group" => [] // 存放群组 key
    ];

    private $lastUpdateTime = null;

    private $config = [
        "expiration" => 3000 // 密钥的有效期, 秒, 到期会更新密钥.
    ];

    protected function __construct()
    {}

    /**
     * 获取单例对象
     *
     * @param array $config
     * @return \app\im\service\SecurityService
     */
    public static function getInstance($config = [])
    {
        $obj = parent::getInstance();
//         $obj->configuration($config);
        return $obj;
    }

    /**
     * 获取用户密码
     *
     * @param mixed $userId
     * @return string|mixed
     */
    public function getUserKey($userId)
    {
        if (isset($this->keys["user"][$userId]))
            return $this->keys["user"][$userId];
        else 
            return $this->keys["user"][$userId] = "user-$userId-im-".time();
    }

    public function getGroupKey($groupId)
    {
        if (isset($this->keys["group"][$groupId]))
            return $this->keys["group"][$groupId];
        else 
            return $this->keys["group"][$groupId] = "group-$groupId-im-".time();
    }

    /**
     * 设置用户的密码
     *
     * @param mixed $userId
     * @param string $key
     */
    public function setUserKey($userId, $key)
    {
        $this->keys["user"][$userId] = $key;
    }

    /**
     * 设置群聊的密码
     *
     * @param mixed $userId
     * @param string $key
     */
    public function setGroupKey($groupId, $key)
    {
        $this->keys["group"][$groupId] = $key;
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