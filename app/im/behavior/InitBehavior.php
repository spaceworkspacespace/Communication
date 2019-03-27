<?php
namespace app\im\behavior;

use app\im\service\SecurityService;

class InitBehavior {
//     public static keysUpdate
    public static function run(&$params) {
        self::decryptBody();
//         $_POST["name"] = 123;
    }
    
    /**
     * 如果是加密的 body, 则解密到 $_POST 中.
     */
    public static function decryptBody() {
        // 获取请求头标识
        $encrypted = isset($_SERVER["HTTP_X_GATEWAY_ENCRYPT"])? 
            (bool)$_SERVER["HTTP_X_GATEWAY_ENCRYPT"]: false;
        // 获取内容类型
        $contentType = isset($_SERVER["CONTENT_TYPE"])?
            $_SERVER["CONTENT_TYPE"]: "text/plain";
        $contentType = trim(explode(";", $contentType)[0]);

        if ($encrypted) {
            var_dump($encrypted);
            var_dump($contentType);
            var_dump(SecurityService::getInstance()->getPublicKey());
            
            // 进行解密
            $base64Str = file_get_contents('php://input');
            var_dump($base64Str);
            $bodyEncrypt = base64_decode($base64Str);
            var_dump($bodyEncrypt);
            if (!$bodyEncrypt) return;
            $body = SecurityService::getInstance()->decrypt($bodyEncrypt);
            // 转换为对象并赋值到 post.
            var_dump($body);
            $post = [];
            switch ($contentType) {
                default:
                case "text/plain":
                    // 应用还没完全启动, 日志都没得.
//                     im_log("error", "body 解密, 意外的 content-type: $contentType, body: $body");
                    break;
                case "application/x-www-form-urlencoded":
                    parse_str($body, $post);
                    break;
                case "application/json": 
                    $post = json_decode($body);
                    break;
            }
            // 转换成功, 赋值给 $_POST
            var_dump($post);
            if (is_array($post)) {
                foreach ($post as $k => $v) {
                    $_POST[$k]=$v;
                }
            }
        }
    }
    
}