<?php
namespace app\im\model;

interface IMessageModel {
    const TYPE_GENERAL = 0;
    const TYPE_FRIEND_ASK = 1;
    const TYPE_FRIEND_ASK_REFUSE = 12;
    const TYPE_FRIEND_BE_REMOVED = 16;
    const TYPE_GROUP_ASK = 2;
    const TYPE_GROUP_ASK_REFUSE = 13;
    const TYPE_GROUP_INVITE = 3;
    const TYPE_GROUP_INVITE_REFUSE = 15;
    const TYPE_GROUPMEMBER_LEAVE = 23;
    const TYPE_GROUPMEMBER_REMOVE = 21;
    const TYPE_GROUPMEMBER_BE_REMOVED = 22;
    
    /**
     * 创建消息
     * @param mixed $data 消息数据
     * @param mixed[] $receivers 接收者的 id
     * @return array 填充 id 后的消息数据
     */
    public function createMessage($data, $receivers) ;
    
    /**
     * 重新设置消息的 associated 字段.
     * @param array $messages
     * @return array 设置 associated 字段后的消息信息
     */
    public function fillMessageAssociated($messages);
    
    /**
     * 通过消息 id 获取消息数据
     * @param mixed ...$mid
     * @return array 消息数据数组
     */
    public function getMessageById(...$mid) ;
    
    /**
     * 根据用户查询数据, 不包括相关信息
     * @param mixed $userId
     * @param number $pageNo
     * @param number $pageSize
     * @return array 消息列表
     */
    public function getMessageByUser($userId, $pageNo=1, $pageSize=150);
    
    /**
     * 根据用户查询消息 id
     * @param mixed $userId
     * @param number $pageNo
     * @param number $pageSize
     * @return array 包含 id 的数组
     */
    public function getMessageIdAndTypeByUser($userId, $pageNo=1, $pageSize=150);
    
    /**
     * 根据用户和消息类型查询组合好的数据, 包括相关信息
     * @param mixed $userId
     * @param mixed $type
     * @param number $pageNo
     * @param number $pageSize
     * @return array 消息列表
     */
    public function getMessageByUserWithType($userId, $type, $pageNo=1, $pageSize=150);
    
    /**
     * 通过消息 id 获取消息数据, 字段同数据库字段名
     * @param mixed ...$mid
     */
    public function getOriginMessageById(...$mid) ;
    
    /**
     * 查询用户的未读消息数量
     * @param mixed $userId
     * @return int
     */
    public function getUnreadMsgCountByUser($userId) ;
    
    /**
     * 判断消息是否属于用户
     * @param mixed $userId
     * @param mixed $msgId
     * @return boolean
     */
    public function hasMessage($userId, $msgId) ;
    
    /**
     * 更新消息
     * @param mixed $userId
     * @param mixed $mid
     * @param mixed $data
     */
    public function updateMessageById($userId, $mid, $data) ;
    
    /**
     * 更新用户的所有消息为以读
     * @param mixed $userId
     * @return boolean 是否成功
     */
    public function updateMessageReadByUser($userId);
}