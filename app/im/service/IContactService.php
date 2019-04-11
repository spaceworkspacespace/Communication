<?php
namespace app\im\service;

interface IContactService {
    
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
    public function createGroup($creator, string $groupName, string $pic, string $desc): void;
    
}