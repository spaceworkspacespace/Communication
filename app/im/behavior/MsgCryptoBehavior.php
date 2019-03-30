<?php
namespace app\im\behavior;

use app\im\service\SecurityService;

class MsgCryptoBehavior {
    public static function run(&$params) {
        // im_log("debug", "开始加密. ", $params);
        $type = explode("-", $params["id"]);
        
        if (count($type) < 2) {
            return;
        }
        
        switch (trim($type[0])) {
            default:
                $params = $params["payload"];
                break;
            case "u":
                $params["payload"] = base64_encode(SecurityService::getInstance()
                    ->encryptWithUserId(json_encode($params["payload"]), $type[1]));
                break;
            case "g":
//                 im_log("debug", "开始加密群聊信息.");
//                 im_log("debug", json_encode($params["payload"]));
                $params["payload"] = base64_encode(SecurityService::getInstance()
                    ->encryptWithGroupId(json_encode($params["payload"]), $type[1]));
//                 im_log("debug", "加密群聊信息: ", "groupId: ", $type[1], " key: ", 
//                         SecurityService::getInstance()->getGroupKey($type[1]));
//                 im_log("debug", $params["payload"]);
                break;
        }
        $params = json_encode($params);
    }
}