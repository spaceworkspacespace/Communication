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
     * 用户删除了一条消息, 标识不可见
     * @param mixed $userId
     * @param mixed $cid
     * @param mixed $type
     */
    public function hiddenMessage($userId, $cid, $type); 
    
    /**
     * 更新用户收到的消息
     * @param mixed $userId
     * @param mixed $sign
     */
    public function messageFeedback($userId, $sign) ;
    
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
     * 将消息设为已读
     * @param mixed $userId
     * @param array $cids
     * @param integer $type
     */
    public function readMessage($userId, $cids, $type);
    
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