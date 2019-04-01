<?php
namespace app\im\service;


interface IChatService {
    public const CHAT_FRIEND = 1;
    public const CHAT_GROUP = 2;
    
    /**
     * 获取未读的信息
     * @param mixed $userId 用户 id
     * @param mixed $contactId 联系人 id
     * @param integer $type 查询的消息类型.
     * @return \think\Collection 
     */
    public function getUnreadMessage($userId, $contactId, $type): \think\Collection;
    
    /**
     * 读取群组的聊天记录
     * @param mixed $groupId
     */
    public function readChatGroup($groupId, int $pageNo=0, int $pageSize=100);
    
    /**
     * 读取好友的聊天记录
     * @param mixed $userId
     */
    public function readChatUser($Id, $userId, int $pageNo, int $pageSize): \think\Collection;
    
    /**
     * 发送聊天信息给用户
     * @param mixed $fromId
     * @param mixed $toId
     * @param string $content
     * @return bool
     */
    public function sendToUser($fromId, $toId, $content);
    
    /**
     * 发送聊天信息给群聊
     * @param mixed $fromId
     * @param mixed $toId
     * @param string $content
     * @return bool
     */
    public function sendToGroup($fromId, $toId, $content);
}