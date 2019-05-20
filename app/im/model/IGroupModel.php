<?php
namespace app\im\model;

interface IGroupModel {
    
    /**
     * 创建一个群聊
     * @param mixed $userId
     * @param mixed $data { groupname, description?,  avatar }
     */
    public function createGroup($userId, $data) ;
    
    /**
     * 删除指定群聊中的成员
     * @param mixed $gid
     * @param mixed ...$uid
     */
    public function deleteGroupMemberById($gid, ...$uid) ;
    
    /**
     * 通过群聊的名称判断是否存在
     * @param string $name
     * @return boolean
     */
    public function existsGroupByName($name) ;
    
    /**
     * 获取群聊中的管理员的 id
     * @param mixed $gid
     * @return array 用户 id 的数组
     */
    public function getGroupAdminIds($gid);
    
    /**
     * 通过群聊 id 获取群聊信息
     * @param mixed $groupId
     * @return array 
     */
    public function getGroupById(...$groupId);
    
    /**
     * 查询群聊人数
     * @param mixed $groupId
     * @return int 人数
     */
    public function getGroupMemberCount($groupId);
    
    /**
     * 通过用户信息获取 im_groups 表数据, 字段同数据库字段名
     * @param mixed $userId
     * @param mixed $groupId
     * @param mixed $fields 要查询的字段
     * @return mixed 好友关系信息
     */
    public function getOriginGroupByUser($userId, $groupId, $fields="*") ;
    
    /**
     * 判断用户是否在群聊中
     * @param mixed $uid
     * @param mixed $gid
     * @return boolean 用户是否在群聊中
     */
    public function inGroup($uid, $gid) ;
    
    /**
     * 设置一个群聊关系
     * @param mixed $userId
     * @param mixed $groupId
     * @return boolean 是否成功
     */
    public function setGroup($userId, $groupId);
    
    /**
     * 更新群聊的信息
     * @param mixed $gid
     * @param mixed $data
     */
    public function updateGroup($gid, $data);
}