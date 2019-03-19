<?php
namespace app\im\util;


interface ISocketLogDriver {
    
    /**
     * 构造函数
     * @param array $config 缓存参数
     * @access public
     */
    public function __construct(array $config = []);
    
    /**
     * 日志写入接口
     * @access public
     * @param  array    $log 日志信息
     * @param  bool     $append 是否追加请求信息
     * @return bool
     */
    public  function save(array $log = [], $append = false): bool;
}