<?php
namespace app\im\model;

interface IFriendModel {
    
    /**
     * 分组人数变动
     * @param int $gid 分组id
     * @param int $str 0为-1||1为+1
     */
    public function FriendGroupCount($gid, $str);
    
    /**
     * 创建一个好友分组
     * @param mixed $userId
     * @param mixed $data
     * @return mixed 分组信息
     */
    public function createFriendGroup($userId, $data) ;
    
    /**
     * 创建多个分组
     * @param array $data 分组数据数组 { user_id, group_name, priority?, member_count?, create_time? }
     * @return array 填充了 id 的分组信息
     */
    public function createFriendGroupMultiple($data) ;
    
    /**
     * 通过用户的 id 删除好友
     * @param array $uid1 用户 id 组 1
     * @param array $uid2 用户 id 组 2
     * @return int 删除数量
     */
    public function deleteFriendById($uid1, $uid2);
    
    /**
     * 修改分组信息
     * @param int $id 分组id
     * @param string $name 新的名字
     */
    public function putFriendGroup($id, $name);
    
    /**
     * 通过分组的 id 删除分组
     * @param array ...$ids
     */
    public function deleteFriendGroupById(...$ids);
    
    /**
     * 获取用户分组, 如果没有这样的分组就创建.
     * @param mixed $userId 用户 id
     * @param mixed ...$name
     * @return array 分组信息的列表
     */
    public function determineFriendGroupByName($userId, ...$name);
    
    /**
     * 获取指定的好友信息
     * @param mixed $userId
     * @param mixed ...$uid
     * @return array 包含好友信息的分组列表
     */
    public function getFriendAndGroupById($userId, ...$uid) ;
    
    /**
     * 通过好友分组的 id 获取分组信息
     * @param mixed ...$fgId
     */
    public function getFriendGroupById(...$fgId) ;
    
    /**
     * 通过用户信息获取 im_friends 表数据, 字段同数据库字段名
     * @param mixed $userId1
     * @param mixed $userId2
     * @param mixed $fields 要查询的字段
     * @return mixed 好友关系信息
     */
    public function getOriginFriendByUser($userId1, $userId2, $fields="*") ;
    
    /**
     * 判断两个用户是否为好友
     * @param mixed $userId
     * @param mixed $userId2
     * @return boolean 是否为好友关系 true 是, false 否
     */
    public function isFriend($userId, $userId2) ;
    
    /**
     * 将两个用户互相设为好友
     * @param mixed $uid
     * @param mixed $fgId
     * @param mixed $uid1
     * @param mixed $fgId1
     * @return boolean 是否设置成功
     */
    public function setFriend($uid, $fgId, $uUserName, $uid1, $fgId1, $uUserName1);
    
    /**
     * 更新好友信息
     * @param mixed $userId 用户 id
     * @param array $data 好友的属性 { contact_id }
     * @return boolean|number|string
     */
    public function updateFriend($userId, $data) ;
    
    /**
     * 更新用户某个分组下的好友的信息
     * @param mixed $userId
     * @param mixed $fgId
     * @param mixed $data { group_id }
     * @return int 改变的行数
     */
    public function updateFriendByFriendGroup($userId, $fgId, $data) ;
    
    /**
     * 更新分组信息
     * @param mixed $fgId
     * @param mixed $data
     * @return array 更新成功的信息, 不是完整所有的信息.
     */
    public function updateFriendGroup($fgId, $data) ;
    
    /**
     * 将好友更换分组
     * @param int $contact 好友id
     * @param int $group 分组id
     * @param array $user 登陆人信息
     */
    public function putFriend($contact, $group, $user) ;
    
    /**
     * 根据好友id查询所在分组
     * @param int $id 好友id
     */
    public function queryFriendGroupByFriendId($id, $user) ;
}