<?php
namespace app\im\service;


interface IChatService {
    public const CHAT_FRIEND = 1;
    public const CHAT_GROUP = 2;
    public const CHAT_ALL = 0;
    
    /**
     * 获取用户聊天信息
     * @param mixed $userId
     * @param number $pageNo
     * @param number $pageSize
     * @param string $chatType
     * @param mixed $id
     */
    public function getMessage($userId, $pageNo=0, $pageSize=50, $chatType = null, $id = null);
    
    /**
     * 获取未读消息
     * @param mixed $userId
     * @param number $pageNo
     * @param number $pageSize
     * @param string $chatType
     * @param mixed $id
     */
    public function getUnreadMessage($userId, $pageNo = 1, $pageSize = 50, $chatType = null, $id = null);
    
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
    
    /**
     * 请求与好友进行通话
     * @param mixed $userId
     * @param mixed $userId2
     * @param "video" | "voice" $callType
     * @return bool
     */
    public function requestCallWithFriend($userId, $userId2, $callType);
    
    /**
     * 请求进行群聊通话
     * @param mixed $userId
     * @param mixed $groupId
     * @param "video" | "voice" $callType
     * @return bool
     */
    public function requestCallWithGroup($userId, $groupId, $callType);
    
    /**
     *进行应答
     *@param string $sign
     *@param boolean $replay
     *@param boolean $unread
     *@return bool
     */
    public function requestCallReply($userId,$sign,$replay,$unread);
    
    /**
     *用户接听
     *@param boolean $replay
     *@param array $userdata
     *@return bool
     */
    public function requestCallGroupReply($userId,$replay,$userdata,$sign);
    
    /**
     *加入群聊
     *@param boolean $replay
     *@param array $unread
     *@return bool
     */
    public function requestCallUserReply($userId, $replay, $userdata, $sign);
    
    /**
     *用户交换描述
     *@param mixed $userId
     *@param string $sign
     *@param string $description
     *@return bool
     */
    public function requestCallUserExchange($userId,$sign,$description);
    
    /**
     *群聊用户交换描述
     *@param mixed $userId
     *@param mixed $GroupId
     *@param array $usersData
     *@return bool
     */
    public function requestCallGroupExchange($userId,$GroupId,$usersData);
    
    /**
     * 用户交换ice
     */
    public function requestCallUserExchangeIce($userId, $sign, $ice) ;
    
    /**
     * 群聊交换ice
     * @param int $userId
     * @param int $sign
     * @param string $ice
     * @param array $userData
     */    
    public function requestCallGroupExchangeIce($userId, $groupId, $call) ;
    
    
    /**
     *挂断通话
     *@param mixed $userId
     *@param string $sign
     *@param boolean $success
     *@return bool
     */
    public function requestCallComplete($sign,$success);
    
    /**
     * 用户退出聊天
     *@param array $userdata
     *@return bool
     */
    public function requestCallUserComplete($userdata);
    
    /**
     * 退出群聊
     *@param array $userdata
     *@return bool
     */
    public function requestCallGroupComplete($userId, $sign, $userdata);
    
    /**
     * 挂断
     * @param int $userId
     * @param string $sign
     */
    public function requestFinish($userId, $sign) ;
    
    /**
     * 通话结束
     * @param int $client_id1
     * @param int $client_id2
     */
    public function callOver($client_id1 = null, $client_id2 = null, $groupId = null, $sign = null) ;
    
}