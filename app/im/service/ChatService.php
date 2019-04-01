<?php
namespace app\im\service;


interface ChatService {
    
    /**
     * 发送聊天信息给用户
     * @param mixed $fromId
     * @param mixed $toId
     * @param string $content
     * @return bool
     */
    public function sendToUser($fromId, $toId, $content): bool;
    
    /**
     * 发送聊天信息给群聊
     * @param mixed $fromId
     * @param mixed $toId
     * @param string $content
     * @return bool
     */
    public function sendToGroup($fromId, $toId, $content): bool;
}