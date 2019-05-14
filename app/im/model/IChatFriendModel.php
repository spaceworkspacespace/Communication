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
}