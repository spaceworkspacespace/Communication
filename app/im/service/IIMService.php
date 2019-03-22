<?php
namespace app\im\service;

interface IIMService {
    const SQL_DATE_FORMAT = "Y-m-d H:i:s";
    
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
     * @param mixed $receiver 接收者的 id
     * @param string $content 发送的消息内容
     * @param string$ip 发送者的 ip 地址
     */
    public function linkFriendMsg($sender, $receiver, $content, $ip=null): void;
    
  
    /**
     * 加入群聊的请求
     * @param mixed $sender 发送者的 id
     * @param mixed $groupId 群组 id
     * @param string $content 消息内容
     * @param string $ip 发送者的 ip 地址
     */
    public function linkGroupMsg($sender, $groupId, $content, $ip=null): void;
}