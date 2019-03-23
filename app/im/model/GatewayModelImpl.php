<?php
namespace app\im\model;

use GatewayClient\Gateway;

class GatewayModelImpl implements GatewayModel {
    public $cache = null;
    
    public static function initialize() {
        Gateway::$registerAddress = config("gateway.remote");
        self::$cache = cache(config("cache.redis"));
        
//         Gateway::sendT
    }
    
    
        
    public static function msgToUid($uid, $data): void {
        
    }
    
    public static function chatToGroup($uid, $data): void
    {}

    public static function chatToUid($uid, $data): void
    {}

}
GatewayModelImpl::initialize();