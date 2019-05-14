<?php
namespace app\im\model;

interface IUserModel {
    /**
     * 通过用户 id 获取用户信息
     * @param mixed ...$userId
     * @return array 用户信息
     */
    public function getUserById(...$userId) ;
}