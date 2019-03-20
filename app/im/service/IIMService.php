<?php
namespace app\im\service;

interface IIMService {
    
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
    public function findUser($userId): array;
    
    /**
     * 通过用户的 id (精准查找) 或名称 (模糊查找) 查找用户
     * @param mixed $key
     * @return array
     */
    public function findFriends($key): array;
    
    /**
     * 通过群组的 id (精准查找) 或名称 (模糊查找) 查找用户
     * @param mixed $key
     * @return array
     */
    public function findGroups($key): array;
}