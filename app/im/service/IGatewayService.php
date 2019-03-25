<?php
namespace app\im\service;


interface IGatewayService {
    public const MESSAGE_TYPE = "MESSAGE";
    public const ASK_TYPE = "ASK";
    public const UPDATE_TYPE = "UPDATE";
    
    public static function updateToUid($uid, $data): void;
    
    public static function askToUid($uid, $data): void ;
    
    public static function askToClient($clientId, $data): void;
    
    /**
     * 发送消息给指定客户端
     * @param mixed $uid
     * @param mixed $data
     */
    public static function msgToUid($uid, $data): void;
    
    public static function msgToClient($clientId, $data): void;
    
    public static function msgToGroup($group, $message): void;
    
}