<?php
namespace app\im\model;

trait TRedisDao {
    /**
     * \Redis 类型的类引用
     * @var \ReflectionClass
     */
    protected $refHandle = null;
    
    /**
     * @var \ReflectionMethod
     */
    protected $refRawCommand = null;
    
    public abstract function handler();
    
    public function del($key) {
        return $this->handler()->rawCommand("DEL", $key);
    }
    
    public function exec() {
        return $this->handler()->rawCommand("EXEC");
    }
    /**
     * @return \ReflectionClass
     */
    protected function getRefHandle() {
        if (is_null($this->refHandle)) $this->refHandle = new \ReflectionClass($this->handler());
        return $this->refHandle;
    }
    
    /**
     * @return \ReflectionMethod
     */
    protected function getRefRawCommand() {
        if (is_null($this->refRawCommand)) $this->refRawCommand = $this->getRefHandle()->getMethod("rawCommand");
        return $this->refRawCommand;
    }
    
    public function hdel($key, $field) {
        return $this->handler()->rawCommand("HDEL", $key, $field);
    }
    
    public function hexists($key, $field) {
        return $this->handler()->rawCommand("HEXISTS", $key, $field);
    }
    
    public function hget($key, $field) {
        return $this->handler()->rawCommand("HGET", $key, $field);
    }
    
    public function hgetJson($key, $field) {
        $value = $this->hget($key, $field);
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }
    
    public function hkeys($key, $cache = null) {
        if ($cache === null) $cache = $this->handler();
        return $cache->rawCommand("HKEYS", $key);
    }
    
    public function hlen($key) {
        return $this->handler()->rawCommand("HLEN", $key);
    }
    
    public function hvals($key) {
        return $this->handler()->rawCommand("HVALS", $key);
    }
    
    protected function invokeRawCommand() {
        if (is_null($this->refRawCommand)) $this->refRawCommand = $this->getRefHandle()->getMethod("rawCommand");
        $this->refRawCommand->invokeArgs($this->handler(), func_get_args());
    }
    
    public function multi() {
        return $this->handler()->rawCommand("MULTI");
    }
    
    public function watch(...$args)  {
        array_unshift($args, "WATCH");
        return $this->getRefRawCommand()->invokeArgs($this->handler(), $args);
    }
}