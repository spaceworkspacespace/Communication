<?php

namespace app\im\service;

interface IUserService {
    /**
     * 检查用户是否在线
     * @param mixed $userId 用户 的  id
     * @return array 修改后的属性
     */
    public function isOnline($userId): bool;
    
    /**
     * 更新用户信息
     * @param mixed $userId 用户 id
     * @param mixed $data 要更新的信息
     * @return bool
     */
    public function updateUser($userId, $data);
    
    
    
}