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
     * 查询自己的权限
     * @param int $id 我的id
     * @param int $gid 群聊id
     */
    public function queryMyPermi($id, $gid) ;
    
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
    
    /**
     * 群聊人数+1或者-1
     * @param int $gid 群聊id
     * @param integer $str 为空表示逻辑失败,0表示群聊人数-1|1表示群聊人数+1
     */
    public function GroupsCount($gid, $str) ;
    
    /**
     * 退出群聊
     * @param int $gid 群聊id
     * @param array $user 我的信息
     */
    public function deleteMyGroup($gid, $user) ;
    
    /**
     * 查询该群所有管理员id
     * @param int $gid 群聊id
     */
    public function queryGroupAdminById($gid) ;
    
    /**
     * 邀请加入群聊
     * @param int $gid 群聊id
     * @param array $user 我的信息
     * @param int $uid 用户id
     */
    public function postGroupMember($gid, $uid, $user) ;
    
    /**
     * 修改群聊
     * @param int $gid 群聊 id
     * @param string $name 群聊名称
     * @param string $desc 群聊简介
     * @param string $avatar 群聊图像地址
     * @param int $admin 管理者 id
     */
    public function putGroup($gid, $name, $desc, $avatar, $admin) ;
    
    /**
     * 解散群聊
     * @param int $gid 群聊id
     */
    public function deleteGroup($gid) ;
    
    /**
     * 删除群聊
     * @param int $gid
     */
    public function deleteGroups($gid) ;
    
    /**
     * 查询群聊是否解散
     * @param int $gid 群聊id
     */
    public function queryGroupDissolve($gid) ;
    
    /**
     * 查询群聊名称是否已存在
     * @param string $groupName 群聊名称
     */
    public function getGroupByName($groupName) ;
    
    /**
     * 查询已经提交解散的群聊信息
     */
    public function queryDeleteGroup() ;
}