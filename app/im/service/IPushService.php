<?php

namespace app\im\service;

interface IPushService {
    /**
     * 向用户推送所有可推送的信息.
     * @param mixed $uid 用户 id
     * @return bool
     */
    public function pushAll($uid): bool;
    
    /**
     * 消息盒子提醒.
     * @param mixed $uid 用户 id
     * @return bool true 如果有新的消息且推送成功, 否则 false.
     */
    public function pushMsgBoxNotification($uid): bool;
    
    /**
     * 推送未读消息
     * @param mixed $uid 用户 id
     * @param mixed $contactId 联系人 id, 如果为 null, 将推送用户所有联系人的未读信息.
     * @param integer $type
     * @return bool
     */
    public function pushUnreadMessage($uid,$contactId=null, $type=IChatService::CHAT_FRIEND): bool;
}