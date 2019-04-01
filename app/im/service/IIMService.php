<?php
namespace app\im\service;

interface IIMService {
    const SQL_DATE_FORMAT = "Y-m-d H:i:s";
    
    /**
     * 创建一个分组
     * @param mixed $userId 用户 id
     * @param string $groupName 分组名称
     * @return \think\Collection 插入的分组信息
     */
    public function createFriendGroup($userId, $groupName): array;
    
    /**
     * 通过用户 id 获取用户基本信息等. 为 layerIM 适配的初始化数据.
     * @param mixed $userId
     */
    public function init($userId) ;
    
    /**
     * 查找用户所有的分组信息.
     * @param mixed $userId
     * @return array
     */
    public function findOwnGroups($userId): array;
    
    /**
     * 通过用户 id 查询用户好友信息
     * @param mixed $userId
     * @return array
     */
    public function findOwnFriends($userId): array;
    
    /**
     * 通过用户 id 查询用户的所有联系人分组
     * @param mixed $userId
     * @return array
     */
    public function getOwnFriendGroups($userId): array ;
    
    /**
     * 通过用户 id 查询用户 im 扩展信息.
     * @param mixed $userId
     * @return array
     */
    public function getUserById($userId): array;
    
    /**
     * 通过用户的 id (精准查找) 或名称 (模糊查找) 查找用户
     * @param mixed $key
     * @return array
     */
    public function findFriends($key): array;
    
    /**
     * 通过群聊的名称查询群组
     * @param string $name 查找关键字
     * @param boolean $exact 是否精确查找
     * @return array
     * 
     */
    public function getGroupByName($name, $exact = true): array;
    
    /***
     * 通过群聊的 id 查询群组
     * @param number $id
     * @return array
     */
    public function getGroupById($id): array ;
    
    /**
     * 通过群组的 id (精准查找) 或名称 (模糊查找) 查找群组
     * @param mixed $key
     * @return array
     */
    public function findGroups($key): array;
    
    /**
     * 创建一个群聊
     * @param mixed $creator 创建者的 id
     * @param string $groupName 群聊名称
     * @param string $pic 群聊图片的访问地址
     * @param string $desc 群聊描述
     * @return void 
     * @exception \app\im\exception\OperationFailureException 当创建失败时抛出.
     */
    public function createGroup($creator, string $groupName, string $pic, string $desc): void;
    
    /**
     * 添加好友的请求
     * @param mixed $sender 发送者的 id
     * @param mixed $friendGroupId 发送者设置的分组
     * @param mixed $receiver 接收者的 id
     * @param string $content 发送的消息内容
     * @param string$ip 发送者的 ip 地址
     * @exception \app\im\exception\OperationFailureException 当操作失败时抛出.
     */
    public function linkFriendMsg($sender, $friendGroupId, $receiver, $content, $ip=null): void;
    
  
    /**
     * 加入群聊的请求
     * @param mixed $sender 发送者的 id
     * @param mixed $groupId 群组 id
     * @param string $content 消息内容
     * @param string $ip 发送者的 ip 地址
     * @exception \app\im\exception\OperationFailureException 当操作失败时抛出.
     */
    public function linkGroupMsg($sender, $groupId, $content, $ip=null): void;
    
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
     * 更新用户信息
     * @param mixed $userId 用户 id
     * @param mixed $data 要更新的信息
     * @return bool
     */
    public function updateUser($userId, $data): bool;
}