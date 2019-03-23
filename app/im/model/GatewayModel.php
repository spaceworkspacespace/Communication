<?php

namespace app\im\model;

interface GatewayModel {
    
    /**
     * 发送消息给客户端
     * @param mixed $uid
     * @param mixed $data
     */
    public static function chatToUid($uid, $data): void;
    
    /**
     * 发送消息到群聊
     * @param mixed $uid
     * @param mixed $data
     */
    public static function chatToGroup($uid, $data): void;
    
    /**
     * 发送消息盒子提醒到客户端
     * @param mixed $clientId
     * @param mixed $data
     */
    public static function msgToUid($uid, $data): void;
}