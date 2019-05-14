<?php
namespace app\im\service;

interface IContactService {
    
    /**
     * 将两个用户添加为好友
     * @param mixed $id1
     * @param mixed $fgId1 分组 id
     * @param mixed $id2
     * @param mixed $fgId2 分组 id
     */
    public function addFriend($id1, $fgId1, $id2, $fgId2);
    
    /**
     * 添加好友的请求
     * @param mixed $sender
     * @param mixed $friendGroupId
     * @param mixed $receiver
     * @param string $content
     * @param mixed $ip
     */
    public function addFriendAsk($sender, $friendGroupId, $receiver, $content, $ip=null) ;
    
    /**
     * 创建一个分组
     * @param mixed $userId 用户 id
     * @param string $groupName 分组名称
     * @return \think\Collection 插入的分组信息
     */
    public function createFriendGroup($userId, $groupName): array;
    
    /**
     * 创建一个群聊
     * @param mixed $creator 创建者的 id
     * @param string $groupName 群聊名称
     * @param string $pic 群聊图片的访问地址
     * @param string $desc 群聊描述
     * @return void
     * @exception \app\im\exception\OperationFailureException 当创建失败时抛出.
     */
    public function createGroup($creator, string $groupName, string $pic, string $desc);
    
    /**
     * 删除一个好友
     * @param mixed $userId
     * @param mixed $userId2
     */
    public function deleteFriend($userId, $userId2); 
    
    /**
     * 删除一个好友分组
     * @param mixed $userId 用户 id
     * @param mixed $fgId 分组 id
     * @param mixed $reserve 当分组下存在联系人, 转移的分组.
     */
    public function deleteFriendGroup($userId, $fgId, $reserve=null) ;
    
    /**
     * 将群聊中的用户退出
     * @param mixed $userId
     * @param mixed $gid
     * @param mixed $uid
     */
    public function deleteGroupMember($userId, $gid, $uid);
    
    /**
     * 获取用户的联系人以及分组
     * @param mixed $userId
     * @return array
     */
    public function getFriendAndGroup($userId): array;
    
    /**
     * 通过条件查询群聊
     * @param mixed $condition 可以是 id 或关键字
     * @param number $pageNo
     * @param number $pageSize
     * @return array
     */
    public function getGroupByCondition($condition, $pageNo=1, $pageSize=50): array;
    
    /**
     * 获取指定用户的群聊信息
     * @param mixed $userId
     * @param boolean $include 是否包含群聊成员的信息
     * @return array
     */
    public function getGroupByUser($userId, $include = false): array;
    
    /**
     * 加入群聊
     * @param mixed $userId
     * @param mixed $groupId
     */
    public function joinGroup($userId, $groupId, $helloMsg=null) ;
    
    /**
     * 加入群聊的申请
     * @param mixed $sender
     * @param mixed $groupId
     * @param mixed $content
     * @param mixed $ip
     */
    public function joinGroupAsk($sender, $groupId, $content, $ip = null) ;
    
    /**
     * 更新好友信息
     * @param mixed $userId 用户
     * @param array $friend 好友的属性 { id, group, alias }
     * @return array 更新后的信息
     */
    public function updateFriend($userId, $friend) ;
    
    /**
     * 更新群聊成员信息
     * @param mixed $uid
     * @param mixed $gid
     * @param mixed $member 
     * @return mixed 修改后的成员信息
     */
    public function updateGroupMember($uid, $gid, $member) ;
    
}