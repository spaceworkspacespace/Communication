<?php
namespace app\im\util;

use app\im\model\TRedisLock;
use app\im\model\TRedisDao;
use app\im\model\RedisModel;

class RedisCacheDriverImpl extends \think\cache\driver\Redis implements IRedisCacheDriver {
    use TRedisLock ;
    use TRedisDao;
    
    public function __call($method, $args) {
        // $passArgs = $args;
        // array_push($passArgs, $this->handler());
        return RedisModel::$method(...$args);
    }
}