<?php

namespace app\im\service;

use GatewayClient\Gateway;

class GatewayService {
    private static  $_instance = null;
    private $config;
    
    public  function __construct($config) {
        $this->config = $config;
        Gateway::$registerAddress = $config["remote"];
    }
    
    public static function instance($config = null) {
        if (self::$_instance !== null) {
            if ($config !== null) self::$_instance->update($config);
            return self::$_instance;
        }
        return self::$_instance = new self($config);
    }
    
    public function update($config) {
        if (is_array($config))
            $this->config = array_merge($this->config, $config);
    }
  
    
    public function bindUid(string $client_id, string $uid): void {
        Gateway::bindUid($client_id, $uid);
    }
    
    public function sendToClient(): void {
        Gateway::sendToClient($client_id, $message);
    }
}