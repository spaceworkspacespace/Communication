<?php
namespace app\im\util;

use app\im\model\TRedisLock;
use app\im\model\TRedisDao;

class RedisCacheDriverImpl extends \think\cache\driver\Redis implements IRedisCacheDriver {
    use TRedisLock ;
    use TRedisDao;
}