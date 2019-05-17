<?php
namespace app\im\model;

interface IUserModel {
    /**
     * 通过用户 id 获取用户信息
     * @param mixed ...$userId
     * @return array 用户信息
     */
    public function getUserById(...$userId) ;
    
    /**
     * 查询我的好友信息
     * @param int $id 我的id
     * @param int $no 页码
     * @param int $count 每页显示行数
     */
    public function getMyFriendInfo($id, $no, $count) ;
    
    /**
     * 根据id或者名字模糊查询
     * @param string $keyword 关键字
     * @param int $id 联系人id
     * @param int $no 页码
     * @param int $count 每页显示行数
     */
    public function getFriendByIdOrName($keyword, $no, $count);
    
    /**
     * 根据id查询联系人
     * @param int $id 联系人id
     */
    public function getFriendById($id) ;
}