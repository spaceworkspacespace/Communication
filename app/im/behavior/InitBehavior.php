<?php
namespace app\im\behavior;

use app\im\service\SecurityService;
use think\Response;
use GatewayClient\Gateway;
use think\exception\HttpResponseException;
use app\im\util\Jwt;

class InitBehavior {
    public const forceAjaxUrl = [
        "/test/req",
        ["/im/comm/picture", "POST"],
        ["/im/comm/file", "POST"],
    ];
    

    public static function appInit(&$params) {
        session_start();
        static::_decryptBody();
        static::_forceAjax();
        static::checkJwt();
        $_SESSION["x_in_transaction"] = false;
//         var_dump($_SESSION);
        // 测试设置跨域头
//         var_dump($_GET["_origin"]);
//         header("Access-Control-Allow-Origin:".$_GET["_origin"]);
    }
    
    public static function moduleInit(&$params) {
        if (APP_DEBUG) {
            // 将 OPTIONS 请求设置为正常返回值.
            // 解决跨域情况下返回为 404.
            $method = strtoupper(getenv("REQUEST_METHOD"));
            if ($method === "OPTIONS") {
                throw new HttpResponseException(Response::create([], config("default_ajax_return")));
            }
        }
    }
    
    public static function actionBegin(&$params) {
        Gateway::$registerAddress = config("gateway.remote");
    }
    
    public static function _forceAjax() {
        
        // 获取请求地址和请求方法
        ($url=getenv("HTTP_X_REWRITE_URL"))? $url
            : ($url=getenv("REQUEST_URI"))? $url
            : ($url=getenv("ORIG_PATH_INFO"))? $url
            : ($url=getenv("SCRIPT_NAME"))? $url
            : $url="";
        $method = getenv("REQUEST_METHOD");
        $url = strtolower($url);
        $method = strtoupper($method);        
//         var_dump($url);
//         var_dump($method);
        function setAjax($method) {
            $varName = "_ajax";
            switch($method) {
                case "GET":
                    $_GET[$varName] = true;
                    break;
                case "POST":
                    $_POST[$varName] = true;
                    break;
            }
        }
        
        foreach(static::forceAjaxUrl as $rule) {
            if (is_string($rule)) {
//                 var_dump($url);
//                 var_dump($rule);
//                 var_dump($url !== $rule);
                if ($url !== $rule) continue;
                setAjax($method);
                return;
            }
            if (is_array($rule) && count($rule) > 1) {
//                 var_dump($rule[1] !== $method);
                if ($rule[1] !== $method) continue;
//                 var_dump($rule[1] !== $method);
                if ($rule[0] !== $url) continue;
                setAjax($method);
                return;
            }
        }
        
        
        
//         var_dump(getenv("REQUEST_METHOD"));
//         var_dump(getenv("SCRIPT_NAME"));
//         var_dump(getenv("PATH_INFO"));
//         var_dump(getenv("REQUEST_URI"));
//         var_dump(getenv("PATH_TRANSLATED"));
//         var_dump(getenv("HTTP_X_REWRITE_URL"));
//         var_dump(getenv("REQUEST_URI"));
//         var_dump(getenv("ORIG_PATH_INFO"));
//         $url = getenv("HTTP_X_REWRITE_URL")? getenv("HTTP_X_REWRITE_URL")
//             : getenv("REQUEST_URI")? getenv("REQUEST_URI")
//             : getenv("ORIG_PATH_INFO")? getenv("ORIG_PATH_INFO")
//             : getenv("SCRIPT_NAME")? getenv("SCRIPT_NAME")
//             : "";
    }
    
    /**
     * 如果是加密的 body, 则解密到 $_POST 中.
     */
    public static function _decryptBody() {
        // 获取请求头标识
        $encrypted = isset($_SERVER["HTTP_X_GATEWAY_ENCRYPT"])? 
            (bool)$_SERVER["HTTP_X_GATEWAY_ENCRYPT"]: false;
        // 获取内容类型
        $contentType = isset($_SERVER["CONTENT_TYPE"])?
            $_SERVER["CONTENT_TYPE"]: "text/plain";
        $contentType = trim(explode(";", $contentType)[0]);

//             var_dump($encrypted);
//             var_dump($contentType);  
        if ($encrypted) {
            // 判断 session 获取用户 id.
//             session_start();
            if (!isset($_SESSION["think"]["user"])) return; 
            $uid = $_SESSION["think"]["user"]["id"];
            if (!$uid) return;
            
            // 进行解密
            $base64Str = file_get_contents('php://input');
//             var_dump($base64Str);
            $bodyEncrypt = base64_decode($base64Str);
//             var_dump($bodyEncrypt);
            if (!$bodyEncrypt) return;
            $body = SecurityService::getInstance()->decryptWithUserId($bodyEncrypt, $uid);
            // 转换为对象并赋值到 post.
            // var_dump($body);
            $post = [];
            switch ($contentType) {
                default:
                case "text/plain":
                    // 应用还没完全启动, 日志都没得.
                    // im_log("error", "body 解密, 意外的 content-type: $contentType, body: $body");
                    break;
                case "application/x-www-form-urlencoded":
                    parse_str($body, $post);
                    break;
                case "application/json": 
                    $post = json_decode($body);
                    break;
            }
            // 转换成功, 赋值给 $_POST
//             var_dump($post);
            if (is_array($post)) {
                foreach ($post as $k => $v) {
                    $_POST[$k]=$v;
                }
            }
        }
    }

    public static function checkJwt(){
        $jwt = new Jwt();
        if(array_key_exists('HTTP_X_AUTH', $_SERVER)){
            $token = $_SERVER['HTTP_X_AUTH'];
            if($getPayload = $jwt->verifyToken($token)){ //验证token是否有效
                if(is_array($getPayload)){
                    session('user', $getPayload['user']);
                }else{
                    Header("Location:".cmf_url("#/index"));
                }
            }
        }
    }
    
}