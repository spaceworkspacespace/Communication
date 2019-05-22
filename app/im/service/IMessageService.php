<?php
namespace app\im\service;

interface IMessageService {
    /**
     * 查询用户的消息通知
     * @param mixed $userId
     * @param number $pageNo
     * @param number $pageCount
     */
    public function getMessage($userId, $pageNo=1, $pageSize=150) ;
   
    /**
     * 对消息进行消极的响应, 如拒绝请求
     * @param mixed $userId
     * @param mixed $msgId
     * @param mixed $args 额外的参数
     * @return boolean 是否成功
     */
    public function negativeHandle($userId, $msgId, $args) ;
    
    /**
     * 对消息进行积极的响应, 如同意请求
     * @param mixed $userId
     * @param mixed $msgId
     * @param mixed $args 额外的参数
     * @return boolean 是否成功
     */
    public function positiveHandle($userId, $msgId, $args) ;
    
    /**
     * 消息删除
     * @param int $id 消息id
     */
    public function deleteIndex($id) ;
    
    /**
     * 消息已读处理
     * @param int $id 消息id
     */
    public function postFeedBack($id) ;
}