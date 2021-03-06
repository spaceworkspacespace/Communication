<?php
namespace app\im\model;

interface IChatFriendModel {
    /**
     * 获取聊天中消息 id 的最大值
     * @param mixed $userId1
     * @param mixed $userId2
     * @return int 最大的消息 id
     */
    public function getMaxIdByUser($userId1, $userId2) ; 
    
    /**
     * 通话失败后插入系统消息
     * @param int $userId1 接收者的id
     * @param int $userId2 发送者的id
     * @param string $content 内容
     */
    public function addInfo($userId1, $userId2, $content) ;
}