<?php
namespace app\im\behavior;

use app\im\service\SecurityService;

class MsgCryptoBehavior {
    public static function run(&$params) {
        $type = explode("-", $params["id"]);
        
        if (count($type) < 2) {
            return;
        }
        
        switch ($type[0]) {
            default:
                $params = $params["payload"];
                break;
            case "u":
                $params["payload"] = base64_encode(SecurityService::getInstance()
                    ->encryptWithUserId(json_encode($params["payload"]), $type[1]));
                break;
            case "g":
                $params["payload"] = base64_encode(SecurityService::getInstance()
                    ->encryptWithGroupId(json_encode($params["payload"]), $type[1]));
                break;
        }
        $params = json_encode($params);
    }
}