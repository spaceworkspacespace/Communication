<?php

namespace app\im\service;

use app\im\util\RSAUtils;
use think\Hook;
use app\im\util\AutoSerial;

class SecurityService extends AutoSerial {
    public const serialVersionUID = "im_serial_SecurityService";
    private $priKey = null;
    private $pubKey = null;
    private $lastUpdateTime = null;
    private $config = [
        "expiration" => 3000, // 密钥的有效期, 秒, 到期会更新密钥.
    ];
    
    protected function __construct() {
//         RSAUtils::updateKeyPair();
        $this->updateKeyPair();
    }
    
    /**
     * 获取单例对象
     * @param array $config
     * @return \app\im\service\SecurityService
     */
    public static function getInstance($config=[]) {
        $obj = parent::getInstance();
        $obj->configuration($config);
        return $obj;
    }
    
    /**
     * 获取公钥
     * @return string|mixed
     */
    public function getPublicKey() {
        return $this->pubKey;
    }
    
    /**
     * 使用私钥加密内容
     * @param string $text
     * @return string
     */
    public function encrypt($text): string {
        return RSAUtils::encrypt($text, $this->priKey);
    }
    
    public function decrypt($text): string {
        return RSAUtils::decrypt($text, $this->priKey, RSAUtils::PRIVATE_KEY_TYPE);
    }
    
//     public static function encrypt($text): string {
        
//     }
    
    /**
     * 更新配置信息, 主要是过期时间
     * @param array $config
     */
    public function configuration(array $config) {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * 检查 keys 是否过期.
     * @return boolean 表示是否更新.
     */
    public function checkExpiration() {
        $now = time();
        if ($this->lastUpdateTime + $this->config["expiration"] < $now) {
            $this->updateKeyPair();
            return true;
        }
        return false;
    }
    
    /**
     * 立即更新密钥
     */
    public function updateKeyPair() {
        $keys = RSAUtils::genKeyPair();
        $this->priKey = $keys[0];
        $this->pubKey = $keys[1];
        $this->lastUpdateTime = time();
        Hook::listen("keys_update", $keys);
    }
}