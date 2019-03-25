<?php
namespace app\im\behavior;

use app\im\service\SecurityService;

class MsgCryptoBehavior {
    public static function run(&$params) {
        $params = SecurityService::getInstance()
            ->encrypt($params);
    }
}