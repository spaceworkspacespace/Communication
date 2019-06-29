<?php

namespace app\im\model;

trait TRedisLock {
    protected $lockedNames = [];
    
    public abstract function handler();
    
    /**
     * 判断当前是否拥有指定名称的锁
     *
     * @param mixed $name
     */
    public function isLocking($name)
    {
        if (isset($this->lockedNames[$name])) {
            return (string) $this->lockedNames[$name] == (string) $this->handler()->rawCommand("GET", "Lock:$name");
        }
        return false;
    }
    
    /**
     * 加锁
     * @param string $name 锁的标识名
     * @param int 获取锁失败时的等待超时时间(秒), 在此时间之内会一直尝试获取锁直到超时. 为 0 表示失败后直接返回不等待
     * @param int 当前锁的最大生存时间(秒), 必须大于 0 . 如果超过生存时间后锁仍未被释放, 则系统会自动将其强制释放
     * @param int 获取锁失败后挂起再试的时间间隔(微秒)
     */
    public function lock($name, $timeout = 0, $expire = 15, $waitIntervalUs = 100000)
    {
        if (empty($name))
            return false;
            
            $timeout = (int) $timeout;
            $expire = max((int) $expire, 5);
            $now = microtime(true);
            $timeoutAt = $now + $timeout;
            $expireAt = $now + $expire;
            
            $redisKey = "Lock:$name";
            while (true) {
                $result = $this->handler()->rawCommand("SETNX", $redisKey, (string)$expireAt);
                
                if ($result != false) {
                    // 对$redisKey设置生存时间
                    $this->handler()->rawCommand("EXPIRE", $redisKey, $expire);
                    // 将最大生存时刻记录在一个数组里面
                    $this->lockedNames[$name] = $expireAt;
                    return true;
                }
                
                // 以秒为单位，返回$redisKey 的剩余生存时间
                $ttl = $this->handler()->rawCommand("TTL", $redisKey);
                // TTL 小于 0 表示 key 上没有设置生存时间(key 不会不存在, 因为前面 setnx 会自动创建)
                // 如果出现这种情况, 那就是进程在某个实例 setnx 成功后 crash 导致紧跟着的 expire 没有被调用. 这时可以直接设置 expire 并把锁纳为己用
                if ($ttl < 0) {
                    $this->handler()->rawCommand("SET", $redisKey, (string) $expireAt, $expire);
                    $this->lockedNames[$name] = $expireAt;
                    return true;
                }
                
                // 设置了不等待或者已超时
                if ($timeout <= 0 || microtime(true) > $timeoutAt)
                    break;
                    
                    // 挂起一段时间再试
                    usleep($waitIntervalUs);
            }
            
            return false;
    }
    
    /**
     * 释放锁
     *
     * @param
     *            string 锁的标识名
     */
    public function unlock($name)
    {
        if ($this->isLocking($name)) {
            if ($this->handler()->rawCommand("DEL", "Lock:$name")) {
                unset($this->lockedNames[$name]);
                return true;
            }
        }
        return false;
    }
}