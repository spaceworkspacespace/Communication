<?php
namespace app\im\model;

interface IChatGroupModel {
    /**
     * 获取群聊中消息 id 的最大值
     * @param mixed $gid
     * @return int 最大的消息 id
     */
    public function getMaxIdByGroup($gid) ; 
    
    /**
     * 插入系统信息
     * @param int $gid 群聊id
     * @param string $content 内容
     */
    public function addGroupInfo($gid, $content) ;
}