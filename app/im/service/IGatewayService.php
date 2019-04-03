<?php
namespace app\im\service;


interface IGatewayService {
    public const MESSAGE_TYPE = "SEND";
    public const ASK_TYPE = "ASK";
    public const UPDATE_TYPE = "UPDATE";
    public const ADD_TYPE = "ADD";
    
    /**
     * 暂存消息记录到缓存, 用于重发
     */
    public static function cacheMessage($data);
    
    public static function updateToUid($uid, $data): void;
    
    /**
     * 对客户端发送添加 好友/群聊 的命令
     * @param mixed $uid
     * @param mixed $data
     */
    public static function addToUid($uid, $data): void;
    
    public static function askToUid($uid, $data): void ;
    
    public static function askToClient($clientId, $data): void;
    
    /**
     * 发送消息给指定客户端
     * @param mixed $uid
     * @param mixed $data
     */
    public static function msgToUid($uid, $data): void;
    
    /**
     * 发送消息盒子提醒到客户端
     * @param mixed $clientId
     * @param mixed $data
     */
    public static function msgToClient($clientId, $data): void;
    
    /**
     * 发送消息到群聊
     * @param mixed $uid
     * @param mixed $data
     */
    public static function msgToGroup($group, $message): void;
    
    
}