<?php
namespace app\im\service;


interface IGatewayService {
    public const MESSAGE_TYPE = "SEND";
    public const ASK_TYPE = "ASK";
    public const UPDATE_TYPE = "UPDATE";
    public const ADD_TYPE = "ADD";
    public const COMMUNICATION_ASK_TYPE = "COMMUNICATION-ASK";
    public const TYPE_FRIEND_REMOVE = 16;
    public const TYPE_GROUP_REMOVE = 22;
    
    /**
     * 暂存消息记录到缓存, 用于重发
     */
    public  function cacheMessage($data);
    
    public  function updateToUid($uid, $data): void;
    
    /**
     * 对客户端发送添加 好友/群聊 的命令
     * @param mixed $uid
     * @param mixed $data
     */
    public function addToUid($uid, $data): void;
    
    public function askToUid($uid, $data): void ;
    
    public function askToClient($clientId, $data): void;
    
    /**
     * 发送消息给指定客户端
     * @param mixed $uid
     * @param mixed $data
     */
    public function msgToUid($uid, $data): void;
    
    /**
     * 发送消息盒子提醒到客户端
     * @param mixed $clientId
     * @param mixed $data
     */
    public function msgToClient($clientId, $data): void;
    
    /**
     * 发送消息到群聊
     * @param mixed $uid
     * @param mixed $data
     */
    public function msgToGroup($group, $message): void;
    
    /**
     * 发送消息到客户端
     * @param mixed $clientId 客户端 id
     * @param mixed $data 发送的数据
     * @param string $type 消息类型
     * @param array<string> $cids 消息 id
     * @param integer $resend 重发次数
     */
    public function sendToClient($clientId, $data, $type, $cids=[], $resend=3) ;
    
    /**
     * 发送消息给用户
     * @param mixed $userId 用户 id
     * @param mixed $data 发送的数据
     * @param string $type 消息类型
     * @param array<string> $cids 消息 id
     * @param integer $resend 重发次数
     */
    public function sendToUser($userId, $data, $type, $cids=[], $resend=3) ;
    
    /**
     * 发送消息到群聊
     * @param mixed $groupId 群聊 id
     * @param mixed $data 发送的数据
     * @param string $type 消息类型
     * @param array<string> $cids 消息 id
     * @param integer $resend 重发次数
     */
    public function sendToGroup($groupId, $data, $type, $cids=[], $resend=3) ;
}