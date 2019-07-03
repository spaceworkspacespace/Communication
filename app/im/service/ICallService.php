<?php
namespace app\im\service;

interface ICallService {
    
    public const CALL_VOICE = "voice";
    public const CALL_VIDEO = "video";
    
    /**
     * 通话已经结束, 做清理工作.
     * @param integer $userId
     * @param string $sign
     * @return array 与当前用户断开连接的用户的 id.
     */
    function callFinish($userId, $sign);
    
    /**
     * 开始建立两个用户之间的连接
     * @param integer $userId
     * @param integer $userId2
     * @param string $sign
     * @param string $desc
     * @param string $ice
     * @return mixed
     */
    function establish($userId, $userId2, $sign, $desc=null, $ice=null);
    
    // 用户是否处于通话中
    function isCalling($userId);
    
    /**
     * 是否能进行连接
     * @param integer $userId
     * @param integer $groupId
     * @return bool
     */
    function isAvailable($userId, $groupId=null);
    
    /**
     * 加入到会话中
     * @param number $userId
     * @param number $groupId
     * @param "video" | "voice" $callType
     * @param string | null $sign
     * @return mixed 通话的详情信息
     */
    function joinChat($userId, $groupId, $callType, $sign = null);
    
    /**
     * 进行通话请求
     * @param integer $userId 发起者 id
     * @param integer $userId2 接收者 id
     * @param integer $callType 通话类型.
     * @param integer $groupId 群聊 id
     * @return false | string 失败 | sign
     */
    function pushCallRequest($userId, $userId2, $callType, $sign=null, $groupId=null);
}